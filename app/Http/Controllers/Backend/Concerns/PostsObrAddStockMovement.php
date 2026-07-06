<?php

namespace App\Http\Controllers\Backend\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

trait PostsObrAddStockMovement
{
    /**
     * @param  array<string, mixed>  $m
     * @return array{ok: bool, message: string}
     */
    protected function postObrAddStockMovement(array $m, ?string &$cachedToken): array
    {
        $base = rtrim((string) config('app.guzzle_test_url'), '/');
        if ($base === '') {
            return ['ok' => false, 'message' => 'URL du service OBR non configuree (guzzle_test_url).'];
        }

        try {
            $login = function () use ($base): ?string {
                $loginResponse = Http::post($base . '/ebms_api/login/', [
                    'username' => config('app.obr_test_username'),
                    'password' => config('app.obr_test_pwd'),
                ]);
                $loginJson = $loginResponse->json() ?? [];

                return $loginJson['result']['token'] ?? null;
            };

            if ($cachedToken === null || $cachedToken === '') {
                $cachedToken = $login();
            }
            if (empty($cachedToken)) {
                return ['ok' => false, 'message' => 'Connexion au service OBR impossible (token manquant).'];
            }

            $movementUrl = $base . '/ebms_api/AddStockMovement';
            $payload = [
                'system_or_device_id' => config('app.obr_test_username'),
                'item_code' => $m['item_code'] ?? '',
                'item_designation' => $m['item_designation'] ?? '',
                'item_quantity' => $m['item_quantity'] ?? 0,
                'item_measurement_unit' => ($m['item_measurement_unit'] ?? 'PCS') ?: 'PCS',
                'item_cost_price' => $m['item_purchase_or_sale_price'] ?? ($m['item_cost_price'] ?? 0),
                'item_purchase_or_sale_currency' => $m['item_purchase_or_sale_currency'] ?? 'BIF',
                'item_movement_type' => $m['item_movement_type'] ?? '',
                'item_movement_invoice_ref' => $m['item_movement_invoice_ref'] ?? '',
                'item_movement_description' => $m['item_movement_description'] ?? '',
                'item_movement_date' => $m['item_movement_date'] ?? Carbon::now()->toDateString(),
            ];

            $send = function (string $token) use ($movementUrl, $payload) {
                return Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->post($movementUrl, $payload);
            };

            $resp = $send($cachedToken);

            if (method_exists($resp, 'unauthorized') && ($resp->unauthorized() || $resp->forbidden())) {
                $cachedToken = $login();
                if (!empty($cachedToken)) {
                    $resp = $send($cachedToken);
                }
            } elseif (method_exists($resp, 'status') && in_array((int) $resp->status(), [401, 403], true)) {
                $cachedToken = $login();
                if (!empty($cachedToken)) {
                    $resp = $send($cachedToken);
                }
            }

            $json = $resp->json() ?? [];
            $ok = $json['success'] ?? false;

            if (!$ok) {
                $rawMsg = (string) ($json['msg'] ?? '');
                if (trim($rawMsg) === '' && method_exists($resp, 'body')) {
                    $rawMsg = (string) $resp->body();
                }
                if (trim($rawMsg) === '') {
                    $rawMsg = 'Transmission stock OBR refusee.';
                }

                if ($this->isObrAlreadyRegisteredMessage($rawMsg)) {
                    return ['ok' => true, 'message' => ''];
                }

                return ['ok' => false, 'message' => $this->sanitizeObrAddStockMovementError($rawMsg)];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de la transmission a l\'OBR. Veuillez reessayer plus tard.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    protected function sanitizeObrAddStockMovementError(string $raw): string
    {
        $lower = strtolower($raw);
        if (strpos($lower, 'sqlstate') !== false
            || strpos($lower, 'syntax error') !== false
            || strpos($lower, 'access violation') !== false
            || strpos($lower, 'command denied') !== false
        ) {
            return 'Transmission a l\'OBR refusee (droits ou configuration cote OBR). Veuillez contacter l\'administrateur OBR.';
        }
        $raw = trim($raw);

        return $raw !== '' ? $raw : 'Transmission a l\'OBR refusee.';
    }

    protected function isObrAlreadyRegisteredMessage(string $raw): bool
    {
        $lower = strtolower(trim($raw));
        if ($lower === '') return false;

        if (strpos($lower, 'mouvement de stock') !== false
            && (strpos($lower, 'deja ete enregistre') !== false || strpos($lower, 'déjà été enregistré') !== false || strpos($lower, 'deja enregistre') !== false || strpos($lower, 'déjà enregistré') !== false)
        ) {
            return true;
        }

        return strpos($lower, 'movement') !== false
            && strpos($lower, 'already') !== false
            && strpos($lower, 'registered') !== false;
    }

    /**
     * Envoie chaque ligne d'inventaire a l'OBR (AddStockMovement) avant toute mise a jour locale.
     * Type de mouvement : EI (entree inventaire). Quantite : nouvelle quantite comptee (new_quantity).
     * Unite : PCS si l'article n'a pas d'unite acheteur dans le modele Item.
     *
     * @param  iterable<int, \Illuminate\Database\Eloquent\Model>  $details
     */
    protected function sendStoreInventoryEiMovementsToObr(iterable $details, string $inventoryNo, string $defaultMovementDate): ?string
    {
        $obrToken = null;

        foreach ($details as $data) {
            $newQty = (float) ($data->new_quantity ?? 0);
            if (abs($newQty) < 1e-9) {
                continue;
            }

            $item = $data->item ?? null;
            if (!$item) {
                return 'Article introuvable pour une ligne d\'inventaire (item_id manquant ou invalide).';
            }

            $code = trim((string) ($item->code ?? ''));
            if ($code === '') {
                return 'Code article OBR manquant pour : ' . ($item->name ?? ('#' . ($data->item_id ?? '')));
            }

            $price = (float) ($data->new_purchase_price ?? $data->new_cump ?? 0);
            $unit = trim((string) ($item->purchase_unit ?? ''));
            if ($unit === '') {
                $unit = 'PCS';
            }

            $lineDate = $defaultMovementDate;
            if (!empty($data->date)) {
                $lineDate = Carbon::parse($data->date)->toDateString();
            }

            $desc = trim((string) ($data->description ?? ''));
            if ($desc === '') {
                $desc = 'Inventaire ' . $inventoryNo . ' (' . ($data->code_store ?? '') . ')';
            }

            $obr = [
                'item_code' => $code,
                'item_designation' => (string) ($item->name ?? ''),
                'item_quantity' => $newQty,
                'item_measurement_unit' => $unit,
                'item_purchase_or_sale_price' => $price,
                'item_purchase_or_sale_currency' => 'BIF',
                'item_movement_type' => 'EI',
                'item_movement_invoice_ref' => $inventoryNo,
                'item_movement_description' => $desc,
                'item_movement_date' => $lineDate,
            ];

            $result = $this->postObrAddStockMovement($obr, $obrToken);
            if (!$result['ok']) {
                return $result['message'];
            }
        }

        return null;
    }
}

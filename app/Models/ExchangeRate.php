<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'origin_currency_id',
        'destination_currency_id',
        'rate',
        'date',
        'description',
        'active'
    ];

    protected $dates = ['date'];

    /**
     * Get the origin currency
     */
    public function originCurrency()
    {
        return $this->belongsTo('App\Models\Currency', 'origin_currency_id');
    }

    /**
     * Get the destination currency
     */
    public function destinationCurrency()
    {
        return $this->belongsTo('App\Models\Currency', 'destination_currency_id');
    }

    /**
     * Message affiché quand aucun taux BIF/USD n'est disponible pour la date du document.
     */
    public static function missingBifUsdRateMessage(): string
    {
        return 'Aucun taux de change BIF/USD actif n\'a été trouvé pour la date de ce document (ni à une date antérieure). Veuillez configurer un taux dans le module Taux de change.';
    }

    /**
     * Identifiants des devises BIF et USD (noms ou symboles, insensible à la casse).
     *
     * @return array{0: int|null, 1: int|null} [bif_id, usd_id]
     */
    public static function resolveBifAndUsdCurrencyIds(): array
    {
        $usdId = Currency::query()
            ->where(function ($q) {
                $q->whereRaw('UPPER(TRIM(IFNULL(currency_name, ""))) LIKE ?', ['%USD%'])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(symbol, ""))) LIKE ?', ['%USD%']);
            })
            ->orderBy('id')
            ->value('id');

        $bifId = Currency::query()
            ->where(function ($q) {
                $q->whereRaw('UPPER(TRIM(IFNULL(currency_name, ""))) LIKE ?', ['%BIF%'])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(currency_name, ""))) LIKE ?', ['%FBU%'])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(symbol, ""))) LIKE ?', ['%BIF%'])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(symbol, ""))) LIKE ?', ['%FBU%']);
            })
            ->orderBy('id')
            ->value('id');

        return [$bifId, $usdId];
    }

    /**
     * Nombre de BIF pour 1 USD (montant USD = montant BIF / cette valeur).
     * Utilise le dernier taux actif avec date <= $date (même logique que getRate côté USD→BIF, avec repli BIF→USD).
     *
     * @param  \Carbon\Carbon|string|null  $date
     * @return array{bif_per_usd: float, rate_date: string}|null
     */
    public static function bifPerUsdForDate($date): ?array
    {
        if ($date === null || $date === '') {
            return null;
        }

        $ymd = $date instanceof Carbon
            ? $date->format('Y-m-d')
            : Carbon::parse($date)->format('Y-m-d');

        [$bifId, $usdId] = static::resolveBifAndUsdCurrencyIds();
        if (!$bifId || !$usdId || (int) $bifId === (int) $usdId) {
            return null;
        }

        $usdToBif = static::query()
            ->where('active', 1)
            ->where('date', '<=', $ymd)
            ->where('origin_currency_id', $usdId)
            ->where('destination_currency_id', $bifId)
            ->orderBy('date', 'desc')
            ->first();

        if ($usdToBif && (float) $usdToBif->rate > 0) {
            return [
                'bif_per_usd' => (float) $usdToBif->rate,
                'rate_date' => $usdToBif->date instanceof Carbon
                    ? $usdToBif->date->format('Y-m-d')
                    : Carbon::parse($usdToBif->date)->format('Y-m-d'),
            ];
        }

        $bifToUsd = static::query()
            ->where('active', 1)
            ->where('date', '<=', $ymd)
            ->where('origin_currency_id', $bifId)
            ->where('destination_currency_id', $usdId)
            ->orderBy('date', 'desc')
            ->first();

        if (!$bifToUsd || (float) $bifToUsd->rate <= 0) {
            return null;
        }

        $r = (float) $bifToUsd->rate;
        $rateDate = $bifToUsd->date instanceof Carbon
            ? $bifToUsd->date->format('Y-m-d')
            : Carbon::parse($bifToUsd->date)->format('Y-m-d');

        // Taux "USD par 1 BIF" (petit décimal) → BIF par USD = 1 / r
        if ($r < 1.0) {
            return [
                'bif_per_usd' => 1 / $r,
                'rate_date' => $rateDate,
            ];
        }

        // Sinon : r interprété comme "BIF par 1 USD" sur la ligne BIF→USD (ex. division 2 920 000 / 2 920)
        return [
            'bif_per_usd' => $r,
            'rate_date' => $rateDate,
        ];
    }
}


<?php

namespace App\Services;

use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Centralise les calculs du registre des immobilisations
 * et des plans d'amortissement prévisionnels.
 *
 * Délègue le plan linéaire à {@see DepreciationService}.
 */
class AmortissementService
{
    /** @var DepreciationService */
    protected $depreciation;

    public function __construct(DepreciationService $depreciation)
    {
        $this->depreciation = $depreciation;
    }

    /**
     * Registre des immobilisations actives (articles amortissables).
     *
     * @return Collection|array[]
     */
    public function buildRegister(?int $asOfYear = null): Collection
    {
        $asOfYear = $asOfYear ?: (int) date('Y');

        return Material::query()
            ->with(['materialCategory', 'immobilizationAccount', 'account'])
            ->where('is_amortizable', true)
            ->orderBy('code')
            ->orderBy('name')
            ->get()
            ->map(function (Material $material) use ($asOfYear) {
                return $this->mapMaterial($material, $asOfYear);
            })
            ->filter()
            ->values();
    }

    /**
     * Plan d'amortissement d'un matériel (années / base / annuité / cumul / VNC).
     *
     * @return array
     */
    public function planFor(Material $material): array
    {
        return $this->depreciation->generateLinearSchedule($material);
    }

    /**
     * @return array
     */
    public function mapMaterial(Material $material, int $asOfYear): array
    {
        try {
            $schedule = $this->depreciation->generateLinearSchedule($material);
        } catch (InvalidArgumentException $e) {
            $valeur = (float) ($material->depreciation_base ?? $material->purchase_price ?? 0);
            $schedule = [
                'base_amortissable' => $valeur,
                'taux' => $material->duration_years
                    ? round(100 / max(1, (int) $material->duration_years), 6)
                    : 0,
                'duration_years' => (int) ($material->duration_years ?? 0),
                'service_start_date' => null,
                'amortissements_cumules' => 0.0,
                'vnc' => $valeur,
                'progression_pct' => 0.0,
                'schedule' => [],
            ];
        }

        $valeurOrigine = (float) ($material->depreciation_base ?? $material->purchase_price ?? 0);
        if ($valeurOrigine <= 0 && !empty($schedule['base_amortissable'])) {
            $valeurOrigine = (float) $schedule['base_amortissable'];
        }

        $acquisitionDate = $material->service_start_date
            ?: ($material->created_at ? Carbon::parse($material->created_at) : null);

        $planRows = [];
        foreach ($schedule['schedule'] ?? [] as $row) {
            $planRows[] = [
                'year' => (int) $row['year'],
                'base' => (float) ($row['base_amortissable'] ?? 0),
                'annuite' => (float) ($row['dotation'] ?? $row['charge'] ?? 0),
                'cumul' => (float) ($row['cumulative'] ?? 0),
                'vnc' => (float) ($row['vnc'] ?? 0),
                'days' => (int) ($row['days'] ?? 0),
                'is_prorata' => !empty($row['is_prorata']),
                'is_current_year' => ((int) $row['year'] === $asOfYear),
            ];
        }

        return [
            'id' => $material->id,
            'code' => $material->code,
            'designation' => $material->name,
            'categorie' => optional($material->materialCategory)->name ?: '—',
            'date_acquisition' => $acquisitionDate ? $acquisitionDate->format('d/m/Y') : '—',
            'date_acquisition_iso' => $acquisitionDate ? $acquisitionDate->format('Y-m-d') : null,
            'valeur_origine' => round($valeurOrigine, 2),
            'mode' => 'Linéaire',
            'duree' => (int) ($schedule['duration_years'] ?? $material->duration_years ?? 0),
            'taux' => (float) ($schedule['taux'] ?? 0),
            'amortissements_cumules' => round((float) ($schedule['amortissements_cumules'] ?? 0), 2),
            'vnc' => round((float) ($schedule['vnc'] ?? $valeurOrigine), 2),
            'en_service' => !empty($schedule['service_start_date']),
            'compte_immo' => $material->immobilizationAccount
                ? ($material->immobilizationAccount->account_number . ' - ' . $material->immobilizationAccount->account_name)
                : '—',
            'plan' => $planRows,
        ];
    }
}

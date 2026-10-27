<?php

namespace App\Exports\Immobilisations;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Excel du registre des immobilisations.
 * Structure recommandée comptable :
 *  - Onglet 1 « Registre » : synthèse + totaux (=SUM)
 *  - Onglet 2 « Plans amortissement » : cascade plan par plan
 */
class ImmobilisationsExport implements WithMultipleSheets
{
    /** @var Collection */
    protected $rows;

    /** @var string */
    protected $exportedAt;

    public function __construct(Collection $rows, ?string $exportedAt = null)
    {
        $this->rows = $rows;
        $this->exportedAt = $exportedAt ?: now()->format('d/m/Y H:i');
    }

    public function sheets(): array
    {
        return [
            new ImmobilisationsRegisterSheet($this->rows, $this->exportedAt),
            new ImmobilisationsPlansSheet($this->rows),
        ];
    }
}

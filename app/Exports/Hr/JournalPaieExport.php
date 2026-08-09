<?php

namespace App\Exports\Hr;

use Carbon\Carbon;
use App\Models\HrPaiement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JournalPaieExport implements FromCollection, WithMapping, WithHeadings
{
    protected $code;
    protected $companyId;

    public function __construct($code = null, $companyId = null)
    {
        $this->code = $code ?: request()->input('code');
        $this->companyId = $companyId ?: request()->input('company_id');
    }

    public function collection()
    {
        $query = HrPaiement::with(['employe.fonction', 'employe.grade'])
            ->where('code', $this->code)
            ->where('employe_id', '!=', '');

        if (!empty($this->companyId)) {
            $query->where('company_id', $this->companyId);
        }

        return $query->orderBy('id')->get();
    }

    public function map($data): array
    {
        $remuneration_brute = (float)$data->somme_salaire_base
            + (float)$data->allocation_familiale
            + (float)$data->indemnite_logement
            + (float)$data->indemnite_deplacement
            + (float)$data->prime_fonction;

        if ($remuneration_brute < 450000) {
            $inss_pension = ($remuneration_brute * 6) / 100;
            $inss_risque = 2400;
        } else {
            $inss_pension = 27000;
            $inss_risque = 2400;
        }

        $total_deductions = (float)$data->somme_cotisation_inss
            + (float)$data->assurance_maladie_employe
            + (float)$data->somme_impot
            + (float)$data->retenue_pret
            + (float)$data->soins_medicaux
            + (float)$data->autre_retenue;
        $net_a_payer = $remuneration_brute - $total_deductions;

        $employe = $data->employe;
        $birthdate = $employe && $employe->birthdate
            ? Carbon::parse($employe->birthdate)->format('d/m/Y')
            : '';
        $dateEmbauche = $employe && $employe->date_debut
            ? Carbon::parse($employe->date_debut)->format('d/m/Y')
            : '';
        $anciennete = $this->formatAnciennete(
            $employe->date_debut ?? null,
            $data->date_fin ?? $data->date_debut ?? null
        );

        return [
            $data->id,
            Carbon::parse($data->date_debut)->format('m/Y'),
            $employe->matricule_no ?? '',
            $employe->firstname ?? '',
            $employe->lastname ?? '',
            $employe->numero_compte ?? '',
            optional(optional($employe)->fonction)->name ?? '',
            optional(optional($employe)->grade)->name ?? '',
            $birthdate,
            $dateEmbauche,
            $anciennete,
            (float)$data->somme_salaire_base,
            (float)$data->allocation_familiale,
            (float)$data->indemnite_logement,
            (float)$data->indemnite_deplacement,
            (float)$data->prime_fonction,
            $remuneration_brute,
            $inss_pension,
            $inss_risque,
            (float)$data->somme_cotisation_inss,
            (float)$data->inss_employeur,
            (float)$data->assurance_maladie_employe,
            (float)$data->assurance_maladie_employeur,
            (float)$data->somme_impot,
            (float)$data->retenue_pret,
            (float)$data->soins_medicaux,
            (float)$data->autre_retenue,
            $total_deductions,
            $net_a_payer,
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Mois/Annee',
            'Matricule',
            'Nom',
            'Prenom',
            'No Compte',
            'Position',
            'Niveau d etude (grade)',
            'Date de naissance',
            'Date d embauche',
            'Anciennete',
            'Salaire de base',
            'Allocation Familiale',
            'Indemnite de logement',
            'Indemnite de deplacement',
            'Prime de fonction',
            'Remuneration brute',
            'INSS Pension',
            'INSS Risque',
            'INSS Employe',
            'INSS Employeur',
            'Assurance Maladie Employe',
            'Assurance Maladie Employeur',
            'IRE Retenu',
            'Retenue Pret',
            'Soins medicaux',
            'Autres retenues',
            'Total Retenue',
            'Salaire Net',
        ];
    }

    protected function formatAnciennete($dateEmbauche, $referenceDate = null): string
    {
        if (!$dateEmbauche) {
            return '';
        }

        $start = Carbon::parse($dateEmbauche)->startOfDay();
        $end = $referenceDate
            ? Carbon::parse($referenceDate)->startOfDay()
            : Carbon::now()->startOfDay();

        if ($end->lt($start)) {
            return '';
        }

        $years = $start->diffInYears($end);
        $months = $start->copy()->addYears($years)->diffInMonths($end);

        $parts = [];
        if ($years > 0) {
            $parts[] = $years.' an'.($years > 1 ? 's' : '');
        }
        if ($months > 0 || $years === 0) {
            $parts[] = $months.' mois';
        }

        return implode(' ', $parts);
    }
}

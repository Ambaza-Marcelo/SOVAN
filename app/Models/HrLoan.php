<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLoan extends Model
{
    protected $table = 'hr_loans';

    protected $fillable = [
        'date',
        'amount_loan',
        'amount_to_pay_1m',
        'amount_to_pay_2m',
        'amount_to_pay_3m',
        'created_by',
        'description',
        'validated_by',
        'confirmed_by',
        'approuved_by',
        'nbre_tranche',
        'employe_id',
        'status',
        'flag1',
        'flag2',
    ];

    public function employe()
    {
        return $this->belongsTo('App\Models\HrEmploye');
    }

    /**
     * Statuts de prêt encore « actifs » pour la règle métier : une nouvelle avance
     * est refusée tant qu’un prêt dans ces statuts n’est pas totalement soldé.
     * (0 en attente, 1 validé, 2 confirmé, 3 approuvé / retenues paie, 4 demande sortie fonds créée)
     */
    public static function activeStatuses(): array
    {
        return [0, 1, 2, 3, 4];
    }

    /**
     * Aligne la logique avec PaiementController / JournalPaieController (flag1, flag2, nbre_tranche).
     */
    public function isFullyPaid(): bool
    {
        if ($this->flag2 === '2' || (int) $this->flag2 === 2) {
            return true;
        }

        $nb = (float) ($this->nbre_tranche ?? 3);
        $flag1On = $this->flag1 === '1' || (int) $this->flag1 === 1;
        $flag2On = $this->flag2 === '1' || (int) $this->flag2 === 1;
        $m2 = (float) ($this->amount_to_pay_2m ?? 0);
        $m3 = (float) ($this->amount_to_pay_3m ?? 0);

        if ($nb <= 1) {
            return $flag1On;
        }

        if ($nb == 2) {
            if ($m2 <= 0) {
                return $flag1On;
            }

            return $flag1On && $flag2On;
        }

        return false;
    }

    /**
     * L’employé a-t-il au moins une avance non totalement payée parmi les prêts « actifs » ?
     */
    public static function hasOutstandingLoan(int $employeId, ?int $excludeLoanId = null): bool
    {
        $query = static::query()
            ->where('employe_id', $employeId)
            ->whereIn('status', static::activeStatuses());

        if ($excludeLoanId !== null) {
            $query->where('id', '!=', $excludeLoanId);
        }

        foreach ($query->get() as $loan) {
            if (!$loan->isFullyPaid()) {
                return true;
            }
        }

        return false;
    }
}

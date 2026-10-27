<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountExercise;
use RuntimeException;

/**
 * Garde-fou : refuse les écritures sur un exercice verrouillé (clôture étape 4).
 */
class AccountingPeriodGuard
{
    public function isLocked(?string $fiscalYear): bool
    {
        if ($fiscalYear === null || $fiscalYear === '') {
            return false;
        }

        $exercise = AccountExercise::where('fiscal_year', (string) $fiscalYear)->first();

        return $exercise ? $exercise->isLocked() : false;
    }

    /**
     * @throws RuntimeException
     */
    public function assertWritable(?string $fiscalYear): void
    {
        if ($this->isLocked($fiscalYear)) {
            throw new RuntimeException(
                'Exercice '.$fiscalYear.' verrouillé : aucune écriture comptable n\'est autorisée.'
            );
        }
    }

    public static function check(?string $fiscalYear): void
    {
        (new static())->assertWritable($fiscalYear);
    }
}

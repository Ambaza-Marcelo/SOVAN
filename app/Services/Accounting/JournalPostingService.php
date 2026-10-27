<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountJournalEntryDetail;
use Illuminate\Support\Facades\Schema;

/**
 * Point unique d'insertion des écritures comptables :
 * - vérifie période / exercice
 * - estampille exercise_id, fiscal_year, period_id
 */
class JournalPostingService
{
    /** @var bool */
    protected static $bypassLock = false;

    /** @var PeriodLockService */
    protected $periodLock;

    public function __construct(PeriodLockService $periodLock)
    {
        $this->periodLock = $periodLock;
    }

    /**
     * Exécute un callback sans contrôle de période (ex: pièce de clôture).
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function withoutLock(callable $callback)
    {
        $previous = self::$bypassLock;
        self::$bypassLock = true;
        try {
            return $callback();
        } finally {
            self::$bypassLock = $previous;
        }
    }

    public static function isBypassingLock(): bool
    {
        return self::$bypassLock;
    }

    /**
     * Insert écritures avec garde-fou + métadonnées d'exercice.
     *
     * @param  array  $rows
     * @return bool
     */
    public function insert(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        // Normaliser : une seule ligne associative
        if ($this->isAssociative($rows)) {
            $rows = [$rows];
        }

        $hasPeriodId = Schema::hasColumn('account_journal_entry_details', 'period_id');
        $hasExerciseId = Schema::hasColumn('account_journal_entry_details', 'exercise_id');
        $hasFiscalYear = Schema::hasColumn('account_journal_entry_details', 'fiscal_year');

        $stamped = [];
        foreach ($rows as $row) {
            $date = $row['date'] ?? null;
            if ($date && !self::$bypassLock) {
                $this->periodLock->assertDateWritable($date);
            }

            if ($date) {
                $meta = $this->periodLock->stampMeta($date);
                if ($hasExerciseId && empty($row['exercise_id'])) {
                    $row['exercise_id'] = $meta['exercise_id'];
                }
                if ($hasFiscalYear && empty($row['fiscal_year'])) {
                    $row['fiscal_year'] = $meta['fiscal_year'];
                }
                if ($hasPeriodId && empty($row['period_id'])) {
                    $row['period_id'] = $meta['period_id'];
                }
            }

            // Ne pas envoyer period_id si la colonne n'existe pas encore
            if (!$hasPeriodId) {
                unset($row['period_id']);
            }

            $stamped[] = $row;
        }

        return AccountJournalEntryDetail::query()->getQuery()->insert($stamped);
    }

    protected function isAssociative(array $rows): bool
    {
        if ($rows === []) {
            return false;
        }

        return array_keys($rows) !== range(0, count($rows) - 1);
    }
}

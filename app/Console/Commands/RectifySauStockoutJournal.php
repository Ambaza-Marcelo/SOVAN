<?php

namespace App\Console\Commands;

use App\Services\Stock\SauJournalRectificationService;
use Illuminate\Console\Command;

class RectifySauStockoutJournal extends Command
{
    protected $signature = 'stock:rectifier-sau
                            {--document= : Limiter à un numéro de bon de sortie}
                            {--apply : Appliquer la rectification (sinon simple aperçu)}';

    protected $description = 'Réimpute en place les débits des sorties SAU/SN passés sur un compte d\'ajustement global vers le compte de chaque article';

    public function handle(SauJournalRectificationService $service): int
    {
        $documentNo = $this->option('document') ?: null;
        $plans = $service->plan($documentNo);

        if (empty($plans)) {
            $this->info('Aucune écriture à rectifier.');

            return 0;
        }

        $accountIds = [];
        foreach ($plans as $plan) {
            foreach ($plan['lines'] as $line) {
                $accountIds[] = $line['generic_account_id'];
                foreach ($line['allocations'] as $allocation) {
                    $accountIds[] = $allocation['account_id'];
                }
            }
        }
        $labels = $service->accountLabels($accountIds);

        $rows = [];
        foreach ($plans as $plan) {
            foreach ($plan['lines'] as $line) {
                $targets = array_map(function ($allocation) use ($labels) {
                    return ($labels[$allocation['account_id']] ?? $allocation['account_id'])
                        . ' : ' . number_format($allocation['amount'], 2, ',', ' ');
                }, $line['allocations']);

                $rows[] = [
                    $plan['module_label'],
                    $plan['document_no'],
                    $line['line_id'],
                    $labels[$line['generic_account_id']] ?? $line['generic_account_id'],
                    number_format($line['amount'], 2, ',', ' '),
                    implode("\n", $targets),
                    $plan['status'] === SauJournalRectificationService::STATUS_READY ? 'prêt' : implode(' | ', $plan['reasons']),
                ];
            }
        }

        $this->table(['Module', 'Bon', 'Ligne', 'Compte global', 'Montant', 'Nouvelle imputation', 'État'], $rows);

        if (!$this->option('apply')) {
            $this->comment('Aperçu uniquement. Relancer avec --apply pour rectifier.');

            return 0;
        }

        $summary = $service->apply($documentNo);
        $this->info(sprintf(
            'Pièces rectifiées : %d — lignes réimputées : %d — pièces ignorées : %d',
            $summary['applied'],
            $summary['lines'],
            $summary['skipped']
        ));
        foreach ($summary['errors'] as $error) {
            $this->error($error);
        }

        return empty($summary['errors']) ? 0 : 1;
    }
}

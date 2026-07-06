<?php

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

trait HandlesStockDocumentApproval
{
    protected function normalizeStockDocumentStatus($status): int
    {
        return (int) $status;
    }

    protected function rollbackStockTransactionIfActive(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardStockDocumentValidate(Model $document, int $requiredStatus = 1)
    {
        $status = $this->normalizeStockDocumentStatus($document->status ?? 0);

        if ($status === 4) {
            session()->flash('info', 'Ce document est déjà approuvé.');

            return back();
        }

        if ($status === 2 || $status === 3) {
            session()->flash('info', 'Ce document est déjà validé ou confirmé.');

            return back();
        }

        if ($status === -1) {
            session()->flash('error', 'Ce document a été rejeté.');

            return back();
        }

        if ($status !== $requiredStatus) {
            session()->flash('error', 'Ce document ne peut pas être validé dans son état actuel.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardStockDocumentConfirm(Model $document, int $requiredStatus = 2)
    {
        $status = $this->normalizeStockDocumentStatus($document->status ?? 0);

        if ($status === 4) {
            session()->flash('info', 'Ce document est déjà approuvé.');

            return back();
        }

        if ($status === 3) {
            session()->flash('info', 'Ce document est déjà confirmé.');

            return back();
        }

        if ($status === -1) {
            session()->flash('error', 'Ce document a été rejeté.');

            return back();
        }

        if ($status !== $requiredStatus) {
            session()->flash('error', 'Ce document doit être validé avant d\'être confirmé.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardStockDocumentApprove(Model $document, int $requiredStatus = 3, int $approvedStatus = 4)
    {
        $status = $this->normalizeStockDocumentStatus($document->status ?? 0);

        if ($status === $approvedStatus) {
            session()->flash('info', 'Ce document est déjà approuvé.');

            return back();
        }

        if ($status === -1) {
            session()->flash('error', 'Ce document a été rejeté.');

            return back();
        }

        if ($status !== $requiredStatus) {
            session()->flash('error', 'Ce document doit être confirmé avant d\'être approuvé.');

            return back();
        }

        return null;
    }

    /**
     * Production orders use status 1 (validated) → 2 (confirmed) → 4 (approved).
     *
     * @return RedirectResponse|null
     */
    protected function guardProductionOrderValidate(Model $document)
    {
        $status = (string) ($document->status ?? '');

        if ($status === '4') {
            session()->flash('info', 'Cet ordre de production est déjà approuvé.');

            return back();
        }

        if (in_array($status, ['1', '2'], true)) {
            session()->flash('info', 'Cet ordre de production est déjà validé ou confirmé.');

            return back();
        }

        if ($status === '-1') {
            session()->flash('error', 'Cet ordre de production a été rejeté.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardProductionOrderConfirm(Model $document)
    {
        $status = (string) ($document->status ?? '');

        if ($status === '4') {
            session()->flash('info', 'Cet ordre de production est déjà approuvé.');

            return back();
        }

        if ($status === '2') {
            session()->flash('info', 'Cet ordre de production est déjà confirmé.');

            return back();
        }

        if ($status !== '1') {
            session()->flash('error', 'Cet ordre de production doit être validé avant d\'être confirmé.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardProductionOrderApprove(Model $document)
    {
        $status = (string) ($document->status ?? '');

        if ($status === '4') {
            session()->flash('info', 'Cet ordre de production est déjà approuvé.');

            return back();
        }

        if ($status !== '2') {
            session()->flash('error', 'Cet ordre de production doit être confirmé avant d\'être approuvé.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardInventoryValidate(Model $inventory)
    {
        $status = $this->normalizeStockDocumentStatus($inventory->status ?? 0);

        if ($status === 2) {
            session()->flash('error', 'Cet inventaire est déjà validé.');

            return back();
        }

        if ($status === 1) {
            session()->flash('error', 'Cet inventaire a été rejeté.');

            return back();
        }

        return null;
    }

    /**
     * @return RedirectResponse|null
     */
    protected function guardDeliveryNoteApprove(Model $deliveryNote)
    {
        if ((string) ($deliveryNote->flag2 ?? '') === '1') {
            session()->flash('info', 'Le stock de ce bon de livraison a déjà été appliqué.');

            return back();
        }

        if (!in_array((string) ($deliveryNote->etat ?? ''), ['1', '2'], true)) {
            session()->flash('error', 'Ce bon de livraison doit être validé et confirmé avant d\'être approuvé.');

            return back();
        }

        return null;
    }

    /**
     * Atomically mark a document as approved; returns false if another request won the race.
     */
    protected function markStockDocumentApproved(string $modelClass, string $column, $value, string $approvedBy, int $requiredStatus = 3, int $approvedStatus = 4): bool
    {
        $updated = $modelClass::where($column, $value)
            ->where('status', $requiredStatus)
            ->update([
                'status' => $approvedStatus,
                'approuved_by' => $approvedBy,
            ]);

        return $updated > 0;
    }
}

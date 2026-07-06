<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les factures d'avoir (notes de crédit) doivent toujours porter le type OBR « FA »,
 * pas « FN » (facture normale) ni « NC » (ancien libellé interne).
 */
class FixNoteCreditsObrTypeFa extends Migration
{
    public function up()
    {
        DB::table('note_credits')->where('invoice_type', '!=', 'FA')->update([
            'invoice_type' => 'FA',
            'type_facture' => 'FA',
        ]);
        DB::table('note_credits')->whereNull('invoice_type')->update([
            'invoice_type' => 'FA',
            'type_facture' => 'FA',
        ]);

        DB::table('note_credit_details')->where('invoice_type', '!=', 'FA')->update([
            'invoice_type' => 'FA',
            'type_facture' => 'FA',
        ]);
        DB::table('note_credit_details')->whereNull('invoice_type')->update([
            'invoice_type' => 'FA',
            'type_facture' => 'FA',
        ]);
    }

    public function down()
    {
    }
}

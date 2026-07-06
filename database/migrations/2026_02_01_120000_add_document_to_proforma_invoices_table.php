<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentToProformaInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_invoices', 'document')) {
                $table->string('document')->nullable()->after('document_no');
            }
        });
    }

    public function down()
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropColumn('document');
        });
    }
}

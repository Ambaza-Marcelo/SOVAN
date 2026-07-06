<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountIdToInvoicesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('invoices', 'account_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('client_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('invoices', 'account_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }
    }
}

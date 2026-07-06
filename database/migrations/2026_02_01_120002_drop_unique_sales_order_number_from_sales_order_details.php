<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueSalesOrderNumberFromSalesOrderDetails extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropUnique(['sales_order_number']);
        });
    }

    public function down()
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->unique('sales_order_number');
        });
    }
}

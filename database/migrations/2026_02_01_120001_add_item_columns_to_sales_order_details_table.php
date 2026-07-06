<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemColumnsToSalesOrderDetailsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }
        Schema::table('sales_order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_details', 'item_designation')) {
                $table->string('item_designation')->nullable()->after('sales_order_identifier');
            }
            if (!Schema::hasColumn('sales_order_details', 'item_quantity')) {
                $table->double('item_quantity')->nullable()->after('item_designation');
            }
            if (!Schema::hasColumn('sales_order_details', 'item_price')) {
                $table->double('item_price')->nullable()->after('item_quantity');
            }
            if (!Schema::hasColumn('sales_order_details', 'item_price_nvat')) {
                $table->double('item_price_nvat')->nullable()->after('item_price');
            }
            if (!Schema::hasColumn('sales_order_details', 'vat')) {
                $table->double('vat')->nullable()->after('item_price_nvat');
            }
            if (!Schema::hasColumn('sales_order_details', 'item_price_wvat')) {
                $table->double('item_price_wvat')->nullable()->after('vat');
            }
            if (!Schema::hasColumn('sales_order_details', 'item_total_amount')) {
                $table->double('item_total_amount')->nullable()->after('item_price_wvat');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropColumn([
                'item_designation', 'item_quantity', 'item_price',
                'item_price_nvat', 'vat', 'item_price_wvat', 'item_total_amount'
            ]);
        });
    }
}

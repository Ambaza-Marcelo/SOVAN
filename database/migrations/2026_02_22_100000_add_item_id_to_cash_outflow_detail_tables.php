<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemIdToCashOutflowDetailTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cash_outflow_request_details')) {
            Schema::table('cash_outflow_request_details', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_outflow_request_details', 'item_id')) {
                    $table->unsignedBigInteger('item_id')->nullable()->after('material_id');
                    $table->foreign('item_id')->references('id')->on('items')->onUpdate('cascade')->onDelete('cascade');
                }
            });
        }
        if (Schema::hasTable('cash_outflow_details')) {
            Schema::table('cash_outflow_details', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_outflow_details', 'item_id')) {
                    $table->unsignedBigInteger('item_id')->nullable()->after('material_id');
                    $table->foreign('item_id')->references('id')->on('items')->onUpdate('cascade')->onDelete('cascade');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cash_outflow_request_details') && Schema::hasColumn('cash_outflow_request_details', 'item_id')) {
            Schema::table('cash_outflow_request_details', function (Blueprint $table) {
                $table->dropForeign(['item_id']);
            });
        }
        if (Schema::hasTable('cash_outflow_details') && Schema::hasColumn('cash_outflow_details', 'item_id')) {
            Schema::table('cash_outflow_details', function (Blueprint $table) {
                $table->dropForeign(['item_id']);
            });
        }
    }
}

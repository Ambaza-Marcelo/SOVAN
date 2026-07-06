<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unique('name');
            $table->unique('code');
            $table->string('specification')->nullable(true);
            $table->string('code_store')->nullable(true);
            $table->string('quantity')->nullable(true);
            $table->string('unit')->nullable(true);
            $table->string('purchase_price')->nullable(true);
            $table->string('vat')->nullable(true);
            $table->string('item_ct')->nullable(true);
            $table->string('item_tl')->nullable(true);
            $table->string('cost_price')->nullable(true);
            $table->string('selling_price')->nullable(true);
            $table->string('cump')->nullable(true);
            $table->string('threshold_quantity')->nullable(true);
            $table->string('expiration_date')->nullable(true);
            $table->string('status')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->bigInteger('rmcategory_id')->unsigned()->nullable(true);
            $table->foreign('rmcategory_id')
                    ->references('id')
                    ->on('raw_material_categories')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('rm_measurement_id')->unsigned()->nullable(true);
            $table->foreign('rm_measurement_id')
                    ->references('id')
                    ->on('raw_material_measurements')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('default_account_id')->unsigned()->nullable(true);
            $table->foreign('default_account_id')
                    ->references('id')
                    ->on('account_accounts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('raw_materials');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

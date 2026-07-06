<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_details', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('specification')->nullable(true);
            $table->double('vat')->nullable(true);
            $table->double('item_ct')->nullable(true);
            $table->double('item_tl')->nullable(true);
            $table->string('code_store')->nullable(true);
            $table->double('quantity')->nullable(true);
            $table->double('selling_price')->nullable(true);
            $table->double('cump')->nullable(true);
            $table->string('taux_marge')->nullable(true);
            $table->string('taux_reduction')->nullable(true);
            $table->string('taux_majoration')->nullable(true);
            $table->double('threshold_quantity')->nullable(true);
            $table->string('expiration_date')->nullable(true);
            $table->string('status')->nullable(true);
            $table->string('store_type')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('image')->nullable(true);
            $table->bigInteger('r_material_id')->unsigned()->nullable(true);
            $table->foreign('r_material_id')
                    ->references('id')
                    ->on('raw_materials')
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
        Schema::dropIfExists('item_details');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_unit')->nullable(true);
            $table->unique('purchase_unit');
            $table->string('stockout_unit')->nullable(true);
            $table->unique('stockout_unit');
            $table->string('production_unit')->nullable(true);
            $table->unique('production_unit');
            $table->double('equivalent')->nullable(true);
            $table->double('sub_equivalent')->nullable(true);
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
        Schema::dropIfExists('raw_material_measurements');
    }
}

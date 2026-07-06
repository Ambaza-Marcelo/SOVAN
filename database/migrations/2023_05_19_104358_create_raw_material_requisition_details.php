<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialRequisitionDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_requisition_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('quantity_requisitioned')->nullable(true);
            $table->string('quantity_received')->nullable(true);
            $table->string('price')->nullable(true);
            $table->string('cump')->nullable(true);
            $table->string('requisition_no');
            $table->string('production_plan_number')->nullable(true);
            $table->string('requisition_signature')->nullable(true);
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('total_value_requisitioned')->nullable(true);
            $table->string('total_value_received')->nullable(true);
            $table->string('type_store')->nullable(true);
            $table->string('code_store')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('status')->default('1');
            $table->string('flag1')->default(0);
            $table->string('flag2')->default(0);
            $table->bigInteger('r_material_id')->unsigned();
            $table->foreign('r_material_id')
                    ->references('id')
                    ->on('raw_materials')
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
        Schema::dropIfExists('raw_material_requisition_details');
    }
}

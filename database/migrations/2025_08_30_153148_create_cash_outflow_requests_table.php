<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashOutflowRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_outflow_requests', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('cash_outflow_request_no');
            $table->unique('cash_outflow_request_no');
            $table->string('cash_outflow_request_signature')->nullable(true);
            $table->string('purchase_no')->nullable(true);
            $table->string('asker')->nullable(true);
            $table->text('description')->nullable(true);
            $table->text('rejected_motif')->nullable(true);
            $table->string('created_by')->nullable(true);
            $table->string('updated_by')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->string('rejected_by')->nullable(true);
            $table->string('reseted_by')->nullable(true);
            $table->string('status')->default('0');
            $table->string('flag1')->default(0);
            $table->string('flag2')->default(0);
            $table->string('vat_supplier_payer')->nullable(true);
            $table->double('vat_rate')->default(0);
            $table->bigInteger('supplier_id')->unsigned()->nullable(true);
            $table->foreign('supplier_id')
                    ->references('id')
                    ->on('suppliers')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('cost_center_id')->unsigned()->nullable(true);
            $table->foreign('cost_center_id')
                    ->references('id')
                    ->on('account_cost_centers')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('employe_id')->unsigned()->nullable(true);
            $table->foreign('employe_id')
                    ->references('id')
                    ->on('hr_employes')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('currency_id')->unsigned()->nullable(true);
            $table->foreign('currency_id')
                    ->references('id')
                    ->on('currencies')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('loan_id')->unsigned()->nullable(true);
            $table->foreign('loan_id')
                    ->references('id')
                    ->on('hr_loans')
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
        Schema::dropIfExists('cash_outflow_requests');
    }
}

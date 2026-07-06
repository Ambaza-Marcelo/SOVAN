<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_loans', function (Blueprint $table) {
            $table->id();
            $table->string('date')->nullable(true);
            $table->double('amount_loan')->nullable(true);
            $table->double('amount_to_pay_1m')->nullable(true);
            $table->double('amount_to_pay_2m')->nullable(true);
            $table->double('amount_to_pay_3m')->nullable(true);
            $table->string('status')->default(0);
            $table->string('flag1')->default(0);
            $table->string('flag2')->default(0);
            $table->string('created_by')->nullable(true);
            $table->text('description')->nullable(true);
            $table->string('validated_by')->nullable(true);
            $table->string('confirmed_by')->nullable(true);
            $table->string('approuved_by')->nullable(true);
            $table->double('nbre_tranche')->default(3);
            $table->bigInteger('employe_id')->unsigned()->nullable(true);
            $table->foreign('employe_id')
                    ->references('id')
                    ->on('hr_employes')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('company_id')->unsigned()->nullable(true);
            $table->foreign('company_id')
                    ->references('id')
                    ->on('hr_companies')
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
        Schema::dropIfExists('hr_loans');
    }
}

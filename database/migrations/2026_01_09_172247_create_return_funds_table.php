<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnFundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('return_funds', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('return_no');
            $table->unique('return_no');
            $table->string('cash_outflow_no')->nullable(true);
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
            $table->double('total_amount_picked')->nullable(true);
            $table->double('total_amount_returned')->nullable(true);
            $table->string('return_signature')->nullable(true);
            $table->bigInteger('employe_id')->unsigned()->nullable(true);
            $table->foreign('employe_id')
                    ->references('id')
                    ->on('hr_employes')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->bigInteger('account_id')->unsigned()->nullable(true);
            $table->foreign('account_id')
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
        Schema::dropIfExists('return_funds');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountCostCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(true);
            $table->unique('name');
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
        Schema::dropIfExists('account_cost_centers');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('currency_name')->nullable(true);
            $table->string('symbol')->nullable(true);
            $table->double('rounding')->nullable(true);
            $table->double('decimal_places')->nullable(true);
            $table->boolean('active')->default(0);
            $table->string('position')->nullable(true);
            $table->string('currency_unit_label')->nullable(true);
            $table->string('currency_subunit_label')->nullable(true);
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
        Schema::dropIfExists('currencies');
    }
}

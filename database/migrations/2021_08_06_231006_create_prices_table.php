<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('service')->nullable();
            $table->string('description')->nullable();
            $table->string('region')->nullable();
            $table->string('instanceType')->nullable();  // m5.xlarge
            $table->string('termType')->nullable();      // OnDemand or RI
            $table->binary('convertible')->nullable();   // true
            $table->integer('term')->nullable();         // 1 year
            $table->string('currency')->nullable();
            $table->double('pricePerUnit',12,4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prices');
    }
}

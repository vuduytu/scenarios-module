<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScenarioZipcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scenario_zipcodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable();
            $table->uuid('scenario_id')->nullable(false);
            $table->foreign('scenario_id')->references('id')->on('scenarios')
                ->onDelete('cascade');
            $table->json('zipcodes')->nullable();
            $table->string('path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scenario_zipcodes');
    }
}

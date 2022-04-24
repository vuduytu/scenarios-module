<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScenarioModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scenario_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->nullable();
            $table->uuid('scenario_id')->nullable(false);
            $table->foreign('scenario_id')->references('id')->on('scenarios')
                ->onDelete('cascade');
            $table->integer('backwardCompatibleModelVersion')->nullable();
            $table->json('meta')->nullable();
            $table->string('modelVersion')->nullable();
            $table->string('releaseVersion')->nullable();
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
        Schema::dropIfExists('scenario_models');
    }
}

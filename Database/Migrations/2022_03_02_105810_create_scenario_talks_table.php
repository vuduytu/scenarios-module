<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScenarioTalksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scenario_talks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->nullable();
            $table->uuid('scenario_id')->nullable(false);
            $table->foreign('scenario_id')->references('id')->on('scenarios')
                ->onDelete('cascade');
            $table->json('params')->nullable();
            $table->string('dataId')->nullable();
            $table->string('dataType')->nullable();
            $table->string('displayName')->nullable();
            $table->string('startMessage')->nullable();
            $table->integer('numberOfMessage')->nullable();
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
        Schema::dropIfExists('scenario_talks');
    }
}

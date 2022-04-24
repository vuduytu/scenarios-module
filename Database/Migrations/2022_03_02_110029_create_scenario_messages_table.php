<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScenarioMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scenario_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->nullable();
            $table->uuid('scenario_id')->nullable(false);
            $table->foreign('scenario_id')->references('id')->on('scenarios')
                ->onDelete('cascade');
            $table->uuid('scenario_talk_id')->nullable(false);
            $table->foreign('scenario_talk_id')->references('id')->on('scenario_talks')
                ->onDelete('cascade');
            $table->json('params')->nullable();
            $table->string('dataId')->nullable();
            $table->string('dataType')->nullable();
            $table->string('nameLBD')->nullable();
            $table->json('originalLBD')->nullable();
            $table->json('userInput')->nullable();
            $table->string('previewIcon')->nullable();
            $table->string('previewType')->nullable();
            $table->string('previewValue')->nullable();
            $table->string('talk')->nullable();
            $table->boolean('expandNote')->nullable();
            $table->boolean('newMessage')->nullable();
            $table->json('messages')->nullable();
            $table->boolean('is_extension')->nullable();
            $table->integer('generation')->nullable();
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
        Schema::dropIfExists('scenario_messages');
    }
}

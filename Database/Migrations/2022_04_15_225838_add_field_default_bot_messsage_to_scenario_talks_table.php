<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldDefaultBotMesssageToScenarioTalksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scenario_talks', function (Blueprint $table) {
            $table->boolean('special_talk_msg')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scenario_talks', function (Blueprint $table) {
            $table->dropColumn('special_talk_msg');
        });
    }
}

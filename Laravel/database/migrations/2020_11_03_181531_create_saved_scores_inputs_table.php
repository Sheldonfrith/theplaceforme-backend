<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedScoresInputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saved_scores_inputs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->nullable()->default(null);// user provided name for this saved scores input
            $table->text('domain');// the domain where the request to /scores came from (ex. localhost, theplacefor.me, etc.); 
            $table->text('description')->nullable()->default(null);// optional user provided description for this saved score input
            $table->json('object');// the json object sent as request body to /scores POST endpoint format [{},{},{}...]
            $table->text('user_id')->nullable()->default(null);// necessary for client apps to be able to restrict who has access to which saved requests
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saved_scores_inputs');
    }
}

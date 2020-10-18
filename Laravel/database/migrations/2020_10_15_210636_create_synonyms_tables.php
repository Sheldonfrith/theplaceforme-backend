<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Facades\App\Models\Country;

class CreateSynonymsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $str = file_get_contents('master-jsons/CountrySynonyms.json',true);
        $countries = json_decode($str, true);
        foreach ($countries as $primary_name => $synonyms){
            //for each country in the master list
            // if synonyms is a list with one or more items
            if (is_array($synonyms) && !empty($synonyms)){
                //get name of synonyms table for this country
                $synonyms_table = Country::convertAttribute('primary_name',$primary_name,'synonyms_table');
                //create the table
                Schema::create($synonyms_table, function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->text('synonym');
                });
                //seed the table
                 //for each synonym, add it as a new row
                 foreach ($synonyms as $synonym){
                    DB::table($synonyms_table)->insert(['synonym'=>$synonym]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $countries = App\Models\Country::all();
        foreach ($countries as $country){
        //for each country in the master list
        Schema::dropIfExists($country->synonyms_table);
        
        }
    }
}

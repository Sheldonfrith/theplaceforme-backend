<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Facades\App\Models\Country;


class CreateDependentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $str = file_get_contents('master-jsons/CountryDependents.json',true);
        $countries = json_decode($str, true);
        foreach ($countries as $primary_name => $dependents){
            //for each country in the master list
            // if synonyms is a list with one or more items
            if (is_array($dependents) && !empty($dependents)){
                //get dependents table name
                $dependents_table = Country::convertAttribute('primary_name',$primary_name,'dependents_table');
                //create the dependents table
                Schema::create($dependents_table, function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->text('dependent');
                });
                //seed the dependents table
                //for each dependent, add it as a new row
                foreach ($dependents as $dependent){
                    DB::table($dependents_table)->insert(['dependent'=>$dependent]);
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
        Schema::dropIfExists($country->dependents_table);
        
        }
    }
}

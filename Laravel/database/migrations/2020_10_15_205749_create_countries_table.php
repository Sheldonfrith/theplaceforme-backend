<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Country;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('alpha_three_code');
            $table->text('alpha_two_code')->nullable();
            $table->text('numeric_code')->nullable();
            $table->text('primary_name');
            $table->text('synonyms_table')->nullable()->default(null);//name of table that holds the synonyms
            $table->text('dependents_table')->nullable()->default(null);// name of table that holds the dependents
        });

    //!SEED Immediately so can be used by subsequent migrations
    $str = file_get_contents('master-jsons/CountriesMaster.json',true);
    $countries = json_decode($str, true);

    //[0] is name, [1] is alpha 2, [2] isalpha 3, [3]is numeric
    foreach ($countries as $list){
        $alpha_three_code = $list[2];
        $alpha_two_code = $list[1];
        $numeric_code = $list[3];
        $primary_name = $list[0];

        Country::create([
         'primary_name'=>$primary_name,
         'alpha_two_code'=>$alpha_two_code,
         'alpha_three_code'=>$alpha_three_code,
         'numeric_code'=>$numeric_code,
         'synonyms_table'=>$alpha_three_code.'_synonyms',
         'dependents_table'=>$alpha_three_code.'_dependents',
         ]);
    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
    }
}

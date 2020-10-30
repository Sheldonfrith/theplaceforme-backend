<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('long_name');
            $table->text('data_type');
            $table->double('max_value')->nullable()->default(null);
            $table->double('min_value')->nullable()->default(null);
            $table->text('source_link')->nullable()->default(null);
            $table->text('source_description')->nullable()->default(null);
            $table->text('unit_description');
            $table->text('notes')->nullable()->default(null);
            $table->text('category');
            $table->json('distribution_map');
            $table->double('missing_data_percentage');
            //for each country in the master list...
            $countries = App\Models\Country::all();
            foreach ($countries as $country){
                $table->double($country->alpha_three_code,16,4)->nullable()->default(null);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datasets');
    }
}

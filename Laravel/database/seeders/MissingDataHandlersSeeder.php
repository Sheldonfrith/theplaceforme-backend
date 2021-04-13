<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MissingDataHandlersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $itemsToInsert = [
        'average'=> [
            'formattedName' => 'Average',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the average score of all the countries that did have data.',
        ],
        'median'=> [
            'formattedName' => 'Median',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the middle-most score of all countries that did have data.'
        ],
        'mostFrequent'=>[
            'formattedName' => 'Most Frequent',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the most frequently occuring score of all countries that did have data.'
        ],
        'worseThanPercentage'=>[
            'formattedName' => 'Worse-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score worse than X percent of all countries that did have data.'
        ],
        'betterThanPercentage'=>[
            'formattedName'=> 'Better-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score better than X percent of all countries that did have data.'
        ],
        'specificScore'=>[
            'formattedName' => 'Specific Score',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score of X'
        ],
        'specificValue'=>[
            'formattedName' => 'Specific Value',
            'requiresInput' => true,
            'description' => 'Countries with missing data will be treated as if they did have data and that data was equal to X.'
        ]];
        
        forEach($itemsToInsert as $method_name => $object){
            DB::table('missing_data_handlers')->insert([
                'method_name'=>$method_name,
                'method_name_formatted'=>$object['formattedName'],
                'requires_input' => $object['requiresInput'],
                'input_type_required' => ($object['requiresInput'])?'number':null,
                'description' => $object['description']
            ]);
        };
    }
}

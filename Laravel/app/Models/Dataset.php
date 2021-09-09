<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Events\DatasetSubmitted;
use Illuminate\Support\Facades\Log;
trait DatasetFillable
{
    public function getFillable()
    {

        
        $countryCodes = Country::select('alpha_three_code')->get()->toArray();
        
        $formattedCountryCodes = array_map(function($value){
            return $value['alpha_three_code'];
        },$countryCodes);
         //  * The attributes that are mass assignable.
        return array_merge($this->fillableMetaFields, $formattedCountryCodes);
    }
}

class Dataset extends Model
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use DatasetFillable;//override the getFillable() function

    protected static function boot(){
        parent::boot();
        static::saving(function($model){
            $arrayWithOnlyCountryData = $model->getWithoutAnyMetadata();
            $deNullified = array_diff($arrayWithOnlyCountryData, array(null));
            [$min, $max] = $model->getMinAndMaxValues($deNullified,$model->data_type);
            $model->min_value = $min;
            $model->max_value = $max;
            $model->distribution_map = $model->getDistributionMap($deNullified, $min, $max);
        });
    }

    protected function getMinAndMaxValues($dataset, $data_type){
        if ($data_type==='float' || $data_type==='double' || $data_type==='integer'){
            $min = min($dataset);
            $max = max($dataset);
        } elseif($data_type==='boolean') {
            $min = false;
            $max = true;
        } else {
            $min = null;
            $max = null;
        }
        return [$min, $max];
    }
    protected function getDistributionMap($deNullified, $min, $max){
        $distributionMap = array_fill(0,101,0);
        foreach ($deNullified as $currentVal){
            $index = null;
            $index = (int) round(($currentVal-$min)*100.0/($max-$min)); //index is the percentage of the value relative to the dataset range rounded to nearest integer
            $distributionMap[$index] ++;
        } 
        return $distributionMap;
    }

    //Constants for the model 
    protected $unfillableMetaFields = [
            'id',
            'created_at',
            'updated_at',
        ];
    protected $fillableMetaFields = [
            'long_name',
            'data_type',
            'max_value',
            'min_value',
            'source_link',
            'source_description',
            'notes',
            'unit_description',
            'category',
            'distribution_map',
            'missing_data_percentage',
        ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'saved' => DatasetSubmitted::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'max_value' => null,
        'min_value' => null,
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'distribution_map' => 'array'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * ACCESSORS, currently this is how I'm doing calculated fields like distribution map
     */

    public function getWithoutAnyMetadata()
    {
        Log::info('getting woithout any metadata');
        $returnDataset = $this->toArray();
        $metaFieldNames = array_merge($this->unfillableMetaFields, $this->fillableMetaFields);
        foreach ($metaFieldNames as $fieldName) {
            unset($returnDataset[$fieldName]);
        }
        return $returnDataset;
    }
}

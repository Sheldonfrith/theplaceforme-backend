<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

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
        'saved' => UserSaved::class,
        'deleted' => UserDeleted::class,
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

    public function getWithoutAnyMetadata()
    {
        $returnDataset = clone ($this);
        $metaFieldNames = array_merge($this->unfillableMetaFields, $this->fillableMetaFields);
        foreach ($metaFieldNames as $fieldName) {
            unset($returnDataset[$fieldName]);
        }
        return $returnDataset;
    }
}

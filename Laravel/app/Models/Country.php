<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Country extends Model
{
    use HasApiTokens;
    use HasFactory;

     /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'alpha_three_code',
        'alpha_two_code',
        'numeric_code',
        'primary_name',
        'synonyms_table',
        'dependents_table'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];
    
    public function convertAttribute(String $inputAttributeName, String $inputAttributeVal, String $nameOfAttributeToGet){
        $attributeToGetVal = $this->where($inputAttributeName,'=',$inputAttributeVal)->value($nameOfAttributeToGet);
        return $attributeToGetVal;
    }
}

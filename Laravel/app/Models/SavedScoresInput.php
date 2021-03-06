<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Http\Requests\PostScoresRequest;
class SavedScoresInput extends Model
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
        'name',
        'description',
        'object',
        'domain',
        'user_id',
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
        'object' => 'array'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];
    
    public function saveRequest(PostScoresRequest $request){
        SavedScoresInput::create([
            'domain' => $request->root(),
            'name' => $request->query('name',null),
            'description' => $request->query('description',null),
            'user_id' =>$request->query('user_id',null),
            'object' => $request->json()->all(),
        ]);
    }
}

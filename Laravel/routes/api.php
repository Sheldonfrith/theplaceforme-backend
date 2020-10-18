<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DatasetsController;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\ScoresController;

//index page
Route::get('/',function(){
    return 'nothing here';
});
//resources
Route::resource('datasets',DatasetsController::class);
Route::resource('countries',CountriesController::class);
//get scores
Route::post('/scores',[ScoresController::class,'getScores'])->name('get-scores');

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('/tokens',function(){
//     return Inertia\Inertia::render('API/Index');
// })->name('api-tokens');
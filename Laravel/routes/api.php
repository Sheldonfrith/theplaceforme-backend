<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;

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
    return Redirect::to('https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation');
});
//resources
Route::resource('datasets',DatasetsController::class);
Route::resource('countries',CountriesController::class);
//get scores
Route::post('/scores',[ScoresController::class,'getScores'])->name('get-scores');
Route::get('/missing-data-handler-methods',[ScoresController::class,'getMissingDataHandlerMethods'])->name('get-missing-data-handler-methods');
Route::get('/categories',[DatasetsController::class,'listPossibleCategories'])->name('get-categories');

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('/tokens',function(){
//     return Inertia\Inertia::render('API/Index');
// })->name('api-tokens');
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Resources\Countries;
use App\Http\Resources\DatasetsMeta;
use App\Http\Resources\MissingDataHandlers;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //prevent wrapping of resource collections with "data" field 
        Countries::withoutWrapping();
        MissingDataHandlers::withoutWrapping();
        DatasetsMeta::withoutWrapping();
    }
}

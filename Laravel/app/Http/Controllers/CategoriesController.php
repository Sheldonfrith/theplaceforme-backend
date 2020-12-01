<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoriesController extends Controller
{
   public function getAllowedNames (){
    return response()->json(config('constants.categories.allowed_names', 200));
   }
}

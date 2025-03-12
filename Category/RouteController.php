<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\LMS\Category;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class RouteController extends Controller
{
    public static function moduleRoute()
    {

        Route::get('/category/{id?}', [CategoryController::class, 'get']);
        Route::post('/category', [CategoryController::class, 'post']);
        Route::put('/category/{id}', [CategoryController::class, 'put']);
        Route::delete('/category/{id}', [CategoryController::class, 'delete']);

    }
}

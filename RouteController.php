<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\SystemName;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class RouteController extends Controller
{
    public static function registerRoutes()
    {

        //rename system-name the system name and ApiController to Module API Controller
        Route::prefix('lms')->group(function () {
            Route::get('/course', [ApiController::class, 'get']); //rename  'api' based on module name ex. /module-name
            Route::post('/api', [ApiController::class, 'post']); //rename  'api' based on module name ex. /module-name
            Route::put('/api/{id}', [ApiController::class, 'put']); //rename  'api' based on module name ex. /module-name/{id}
            Route::delete('/api/{id}', [ApiController::class, 'delete']); //rename  'api' based on module name ex. /module-name{id}
            Route::post('/api/{id}', [ApiController::class,'upload']); //rename  'api' based on module name ex. /module-name{id}
            // Add other routes for other ApiController as needed
        });

    }
}

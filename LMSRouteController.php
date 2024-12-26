<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\LMS;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LMS\Course\RouteController as CourseRouteController;

class LMSRouteController extends Controller
{
    public static function registerRoutes()
    {

        //rename system-name the system name and ApiController to Module API Controller
        Route::prefix('lms')->group(function () {

            CourseRouteController::moduleRoute();
            // Add other routes for other ApiController as needed
        });

    }
}

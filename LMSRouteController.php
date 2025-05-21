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
use App\Http\Controllers\LMS\Category\RouteController as CategoryRouteController;

class LMSRouteController extends Controller {
    public static function registerRoutes() {
        Route::prefix('lms')->middleware(['jwt', 'user-permission'])->group(function () {
            CourseRouteController::moduleRoute();
        });

        Route::prefix('lms')->group(function () {
            CategoryRouteController::moduleRouteOpen();
        });
    }
}

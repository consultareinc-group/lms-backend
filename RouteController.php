<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\LMS;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class RouteController extends Controller
{
    public static function registerRoutes()
    {

        //rename system-name the system name and CourseController to Module API Controller
        Route::prefix('lms')->group(function () {
            Route::get('/course/{id?}', [CourseController::class, 'get']); //rename  'api' based on module name ex. /module-name
            Route::post('/course', [CourseController::class, 'post']); //rename  'api' based on module name ex. /module-name
            Route::put('/course/{id}', [CourseController::class, 'put']); //rename  'api' based on module name ex. /module-name/{id}
            Route::delete('/course/{id}', [CourseController::class, 'delete']); //rename  'api' based on module name ex. /module-name{id}

            Route::get('/quiz/{id?}', [CourseController::class, 'getQuiz']); //rename  'api' based on module name ex. /module-name
            Route::post('/quiz', [CourseController::class, 'postQuiz']); //rename  'api' based on module name ex. /module-name
            Route::put('/quiz/{id}', [CourseController::class, 'putQuiz']); //rename  'api' based on module name ex. /module-name/{id}
            Route::delete('/quiz/{id}', [CourseController::class, 'deleteQuiz']); //rename  'api' based on module name ex. /module-name{id}
        });

    }
}

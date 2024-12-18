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
            Route::get('/course/{id?}', [CourseController::class, 'getCourse']);
            Route::post('/course', [CourseController::class, 'postCourse']);
            Route::put('/course/{id}', [CourseController::class, 'putCourse']);
            Route::delete('/course/{id}', [CourseController::class, 'deleteCourse']);

            Route::get('/quiz/{id?}', [CourseController::class, 'getQuiz']);
            Route::post('/quiz', [CourseController::class, 'postQuiz']);
            Route::put('/quiz/{id}', [CourseController::class, 'putQuiz']);
            Route::delete('/quiz/{id}', [CourseController::class, 'deleteQuiz']);

            Route::get('/question/{id?}', [CourseController::class, 'getQuestion']);
            Route::post('/question', [CourseController::class, 'postQuestion']);
            Route::put('/question/{id}', [CourseController::class, 'putQuestion']);
            Route::delete('/question/{id}', [CourseController::class, 'deleteQuestion']);
        });

    }
}

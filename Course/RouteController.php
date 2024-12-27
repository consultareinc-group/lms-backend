<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\LMS\Course;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class RouteController extends Controller
{
    public static function moduleRoute()
    {

        Route::get('/course/{id?}', [CourseController::class, 'get']);
        Route::post('/course', [CourseController::class, 'post']);
        Route::put('/course/{id}', [CourseController::class, 'put']);
        Route::delete('/course/{id}', [CourseController::class, 'delete']);

        Route::get('/quiz/{id?}', [QuizController::class, 'get']);
        Route::post('/quiz', [QuizController::class, 'post']);
        Route::put('/quiz/{id}', [QuizController::class, 'put']);
        Route::delete('/quiz/{id}', [QuizController::class, 'delete']);

        Route::get('/question/{id?}', [QuestionController::class, 'get']);
        Route::post('/question', [QuestionController::class, 'post']);
        Route::put('/question/{id}', [QuestionController::class, 'put']);
        Route::delete('/question/{id}', [QuestionController::class, 'delete']);

    }

    public static function moduleRouteOpen()
    {

        Route::get('/course/{id?}', [ApiController::class, 'getCourse']);
        Route::get('/quiz/{id?}', [ApiController::class, 'getQuiz']);
        Route::get('/quiz_by_course/{course_id}', action: [ApiController::class, 'getQuizByCourse']);
        Route::get('/questions/{quiz_id}', action: [ApiController::class, 'getQuestionsByQuiz']);
        Route::get('/logs/{id}', [ApiController::class, 'getLogs']);
        Route::post('/logs', [ApiController::class, 'postLogs']);
        Route::post('/answers', [ApiController::class, 'checkAnswers']);

    }
}

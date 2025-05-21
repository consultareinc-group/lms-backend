<?php

/**
 *
 * replace the SystemName based on the Folder
 *
 */

namespace App\Http\Controllers\LMS\Course;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class RouteController extends Controller {
    public static function moduleRoute() {

        // Course Routes
        Route::get('/course/{id?}', [CourseController::class, 'get']);
        Route::post('/course', [CourseController::class, 'post']);
        Route::put('/course/{id}', [CourseController::class, 'put']);
        Route::delete('/course/{id}', [CourseController::class, 'delete']);

        // Quiz Routes
        Route::get('/quiz/{id?}', [QuizController::class, 'get']);
        Route::post('/quiz', [QuizController::class, 'post']);
        Route::put('/quiz/{id}', [QuizController::class, 'put']);
        Route::delete('/quiz/{id}', [QuizController::class, 'delete']);

        // Question Routes
        Route::get('/question/{id?}', [QuestionController::class, 'get']);
        Route::post('/question', [QuestionController::class, 'post']);
        Route::put('/question/{id}', [QuestionController::class, 'put']);
        Route::delete('/question/{id}', [QuestionController::class, 'delete']);

        // Examinee Routes
        Route::get('/examinee/course/{id?}', [ApiController::class, 'getCourse']);
        Route::get('/examinee/quiz/{id?}', [ApiController::class, 'getQuiz']);
        Route::get('/examinee/quiz_by_course/{course_id}', action: [ApiController::class, 'getQuizByCourse']);
        Route::get('/examinee/questions/{quiz_id}', action: [ApiController::class, 'getQuestionsByQuiz']);
        Route::post('/examinee/answers', [ApiController::class, 'checkAnswers']);

        // Logs Routes
        Route::get('/examinee/logs/{id?}', [LogsController::class, 'get']);
        Route::post('/examinee/logs', [LogsController::class, 'post']);
    }
}

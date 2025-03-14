<?php

/**
 *
 * replace the SystemName based on the Folder
 *
 */

namespace App\Http\Controllers\LMS\Course;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Carbon\Carbon;

/**
 *
 * replace the ApiController based on the module name + ApiController ex. moduleNameApiController
 *
 */
class ApiController extends Controller
{

    protected $response;
    protected $db;
    public function __construct(Request $request)
    {
        $this->response = new ResponseHelper($request);
        /**
         *
         *  Rename lms based on preferred database on database.php
         *
         */
        $this->db = DB::connection("lms");
    }

    /**
     *
     * modify accepted parameters
     *
     * */
    protected $accepted_parameters = [
        "id",
        "course_name",
        "course_description",
        "video_link",
        "status"
    ];

    protected $logs_accepted_parameters = [
        "id",
        "quiz_id",
        "first_name",
        "last_name",
        "middle_name",
        "suffix",
        "phone",
        "company",
        "email",
        "status",
        "date_time_completed"
    ];

    /**
     *
     * modify required fields based on accepted parameters
     *
     * */
    protected $required_fields = [
        "course_name",
        "course_description",
        "video_link",
        "status"
    ];

    protected $logs_required_fields = [
        "quiz_id",
        "first_name",
        "last_name",
        "phone",
        "company",
        "email",
    ];

    /**
     *
     * modify response column
     *
     * */
    protected $course_response_column = [
        "id",
        "course_name",
        "course_description",
        "number_of_quizzes",
        "video_link",
        "status",
        "date_time_added",
        "date_time_updated",
        "is_deleted"
    ];

    protected $quiz_response_column = [
        'id',
        'quiz_name',
        'passing_percentage',
        'number_of_questions',
        'date_time_added',
        'date_time_updated',
        'is_deleted'
    ];

    protected $question_response_column = [
        'id',
        'question_text',
        'date_time_added',
        'date_time_updated',
        'marks',
        'is_deleted'
    ];

    protected $choice_response_column = [
        'id',
        'choice_text',
        'explanation',
        'is_correct'
    ];

    protected $logs_response_column = [
        "id",
        "quiz_id",
        "first_name",
        "last_name",
        "middle_name",
        "suffix",
        "phone",
        "company",
        "email",
        "status",
        "date_time_completed"
    ];

    protected $response_columns = [];

    /**
     *
     * modify table name
     *
     * */
    protected $table_courses = 'lms_courses';

    //Get courses
    public function getCourse(Request $params, $id = null)
    {
        try {
            //This section is intended for fetching specific course record
            if ($id) {
                $this->course_response_column = ["cr.id", "ct.category_name", "cr.course_name", "cr.status", "cr.video_link", "cr.course_description", "cr.date_time_added", "cr.date_time_updated"];
                $query_result = $this->db->table($this->table_courses. " as cr")->select($this->course_response_column)->leftJoin("lms_categories as ct", "ct.id", "=", "cr.category_id")->where('cr.id', $id)->where('cr.status',  1)->first();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->course_response_column = ["cr.id", "ct.category_name", "cr.course_name", "cr.video_link", "cr.course_description", "cr.status", "cr.date_time_added"];
                $query_result = $this->db->table($this->table_courses. " as cr")->select($this->course_response_column)->leftJoin("lms_categories as ct", "ct.id", "=", "cr.category_id")->where('cr.is_deleted', 0)->where('cr.status',  1)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('cr.id', 'desc')->get();
            }

             // This section is intended for searching published courses
             if ($params->has('search_keyword')) {
                $this->course_response_column = ["cr.id", "ct.category_name", "cr.course_name", "cr.video_link", "cr.course_description", "cr.status", "cr.date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $category_id = $params->query('category_id');
                $query_result = $this->db->table($this->table_courses. " as cr")
                ->select($this->course_response_column)
                ->leftJoin("lms_categories as ct", "ct.id", "=", "cr.category_id")
                ->where('cr.status',  1)
                ->where('cr.is_deleted',0)
                ->where(function ($query) use ($keyword, $category_id) {
                    if (!empty($category_id)) {
                        if (!empty($keyword)) {
                            $query->where('cr.course_name', 'like', '%' . $keyword . '%')
                            ->orWhere('cr.course_description', 'like', '%' . $keyword . '%');
                        }
                        $query->where('cr.category_id', $category_id);

                    } else {
                        $query->where('cr.id', 'like', '%' . $keyword . '%')
                          ->orWhere('cr.course_name', 'like', '%' . $keyword . '%')
                          ->orWhere('cr.course_description', 'like', '%' . $keyword . '%')
                          ->orWhere('ct.category_name', 'like', '%' . $keyword . '%');
                    }
                })
                ->reorder('cr.id', 'desc')
                ->get();
            }

            $this->response_columns = ["id", "category_name", "course_name", "status", "video_link", "course_description", "date_time_added", "date_time_updated"];

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Get quiz
    public function getQuiz(Request $request, $id = null)
    {
        try {
            $query_result = $this->db->table("lms_quizzes")->select($this->quiz_response_column)->select($this->quiz_response_column)->get();
            if ($id) {
                $query_result = $this->db->table("lms_quizzes")->select($this->quiz_response_column)->where('id', $id)->get();
            }

            return $this->response->buildApiResponse($query_result, $this->quiz_response_column);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Get quiz by course_id
    public function getQuizByCourse(Request $request, $course_id = null)
    {
        try {
            if ($course_id) {
                $query_result = $this->db->table("lms_quizzes")->select($this->quiz_response_column)->where('course_id', $course_id)->get();
            }

            return $this->response->buildApiResponse($query_result, response_column: $this->quiz_response_column);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Get questions by quiz_id
    public function getQuestionsByQuiz(Request $request, $quiz_id = null)
    {
        try {
            if ($quiz_id) {
                // get questions along with their choices
                $query_result = $this->db->table("lms_questions as q")
                    ->select('q.id as question_id', 'q.question_text', 'q.date_time_added', 'q.date_time_updated', 'q.marks', 'q.is_deleted')
                    ->where('q.quiz_id', $quiz_id)
                    ->get()
                    ->map(function ($question) {
                        // Add choices for each question
                        $question->choices = $this->db->table("lms_choices")
                            ->select($this->choice_response_column)
                            ->where('question_id', $question->question_id)
                            ->get();
                        return $question;
                    });
            }

            return response()->json($query_result, 200);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Get logs
    public function getLogs(Request $request, $id = null)
    {
        try {
            $query_result = $this->db->table("lms_logs")->select($this->logs_response_column)->select($this->logs_response_column)->get();
            if ($id) {
                $query_result = $this->db->table("lms_logs")->select($this->logs_response_column)->where('id', $id)->get();
            }
            // if ($request->query('search_keyword')) {

            //     $search = $request->query('search_keyword');
            // }/

            return $this->response->buildApiResponse($query_result, $this->logs_response_column);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Add user information
    public function postLogs(Request $request)
    {

        $request = $request->all();

        if (!empty($request)) {
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->logs_accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        // Collect missing required fields
        $missing_fields = [];
        foreach ($this->logs_required_fields as $field) {
            if (!array_key_exists($field, $request) || empty($request[$field])) {
                $missing_fields[] = $field;
            }
        }

        // Check if there are missing fields and return them in the response
        if (!empty($missing_fields)) {
            return response()->json([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields,
            ], 400);
        }

        try {
            $this->db->beginTransaction();

            $request["date_time_completed"] = Carbon::now();

            if (isset($request["id"])) {
                unset($request["id"]);
            }
            $id = $this->db->table("lms_logs")->insertGetId($request);
            if ($id) {
                // $request["id"] = $id;
                $query_result = $this->db->table("lms_logs")->where("id", $id)->get();
                $this->db->commit();
                return $this->response->buildApiResponse($query_result, $this->logs_response_column);
            } else {
                $this->db->rollBack();
                return $this->response->errorResponse("Saving failed.");
            }
        } catch (QueryException $e) {
            return $this->response->errorResponse($e);
        }
    }


    //Check answers
    public function checkAnswers(Request $request)
    {
        $userAnswers = $request->input('answers'); // Frontend JSON payload
        $quizId = $request->input('quiz_id');
        // extract all question_id from the user answers through array_keys function
        $questionIds = array_keys($userAnswers);

        try {
            // Fetch passing percentage for the quiz
            $quiz = $this->db->table('lms_quizzes')
                ->select('passing_percentage')
                ->where('id', $quizId)
                ->first();

            $passingPercentage = (float) str_replace('%', '', $quiz->passing_percentage);

            // Fetch correct choices for the provided questions
            $questionsWithChoices = $this->db->table('lms_choices as c')
                ->select('c.question_id', 'c.id as choice_id', 'c.is_correct')
                ->whereIn('c.question_id', $questionIds)
                ->where('c.is_correct', 1)
                ->get();

            // maps question_id to its correct choice_id
            $correctChoices = [];
            foreach ($questionsWithChoices as $choice) {
                $correctChoices[$choice->question_id][] = $choice->choice_id;
            }

            // Calculate score

            // counts the total number of questions answered
            $totalQuestions = count($questionIds);

            $correctCount = 0;

            // loops through the user's answers and increments the correctCount if it matches the correct answer
            foreach ($userAnswers as $questionId => $selectedChoiceIds) {
                if (!isset($correctChoices[$questionId])) {
                    continue;
                }

                $selectedChoiceIds = (array) $selectedChoiceIds; // Ensure selected choices are treated as an array
                $correctAnswersForQuestion = $correctChoices[$questionId];

                // Check if any selected choice matches correct answers
                foreach ($selectedChoiceIds as $selectedChoiceId) {
                    if (in_array($selectedChoiceId, $correctAnswersForQuestion)) {
                        $correctCount++;
                        break; // Count the question as correct if any selected choice matches
                    }
                }
            }

            // calculates the score as percentage
            $score = ($correctCount / $totalQuestions) * 100;
            $status = $score >= $passingPercentage ? 'passed' : 'failed';

            return response()->json([
                'passing_percentage' => $passingPercentage,
                'score' => round($score, 2), // round score to 2 decimal places
                'status' => $status,
            ], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

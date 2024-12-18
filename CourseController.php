<?php

/**
 *
 * replace the SystemName based on the Folder
 *
*/
namespace App\Http\Controllers\LMS;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;

/**
 *
 * replace the ApiController based on the module name + ApiController ex. moduleNameApiController
 *
*/
class CourseController extends Controller
{

    protected $response;
    protected $db;
    public function __construct(Request $request)
    {
        $this->response = new ResponseHelper($request);
        /**
         *
         *  Rename system_database_connection based on preferred database on database.php
         *
        */
        $this->db = DB::connection("system_database_connection");
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
        "status",
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
        "status",
    ];

    /**
     *
     * modify response column
     *
     * */
    protected $response_columns = [
        "id",
        "course_name",
        "course_description",
        "number_of_quizzes",
        "video_link",
        "status",
        "date_time_added",
        "date_time_updated",
        "is_deleted",
    ];

    /**
     *
     * modify table name
     *
     * */
    protected $table_courses = 'lms_courses';
    protected $table_quizzes = 'lms_quizzes';
    protected $table_questions = 'lms_questions';



    public function getCourse(Request $params, $id = null){

        try{

            $query_result = null;

            //This section is intended for fetching specific course record
            if ($id) {
                $this->response_columns = ["id", "course_name", "status", "video_link", "course_description", "date_time_added", "date_time_updated"];
                $query_result = $this->db->table($this->table_courses)->select($this->response_columns)->where('id',$id)->get();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["id", "course_name", "status", "date_time_added"];
                $query_result = $this->db->table($this->table_courses)->select($this->response_columns)->where('is_deleted', '=', 0)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["id", "course_name", "status", "date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_courses)
                ->select($this->response_columns)
                ->where('is_deleted', '=', 0)
                ->where(function ($query) use ($keyword) {
                    $query->where('id', 'like', '%' . $keyword . '%')
                          ->orWhere('course_name', 'like', '%' . $keyword . '%')
                          ->orWhere('date_time_added', 'like', '%' . $keyword . '%');
                })
                ->get();
            }

            return $this->response->buildApiResponse($query_result, $this->response_columns);

        }
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function postCourse(Request $request){


        $request = $request->all();

        if(!empty($request)){
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        //check if the required fields are filled and has values
        foreach ($this->required_fields as $field) {
            if (!array_key_exists($field, $request)) {
                if(empty($request[$field])){
                    return $this->response->requiredFieldMissingResponse();
                }

            }
        }

        try{

            $this->db->beginTransaction();

            //insert to table
            $request['date_time_added'] = date('Y-m-d H:i:s');
            $request['id'] = $this->db->table($this->table_courses)->insertGetId($request);

            if($request['id']) {
                $this->db->commit();
                return $this->response->buildApiResponse($request, $this->response_columns);
            } else{
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }
        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }

    }

    public function putCourse(Request $request, $id){

        $request = $request->all();

        $this->accepted_parameters = [
            "id",
            "course_name",
            "course_description",
            "video_link",
            "status",
        ];

        if(empty($request)){
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        //check if the Ids matches
        if($request['id'] != $id){
            return $this->response->errorResponse("Ids Dont Match");
        }

        $this->required_fields = [
            "id",
            "course_name",
            "course_description",
            "video_link",
            "status",
        ];

        //check if the required fields are filled and has values
        foreach ($this->required_fields as $field) {
            if (!array_key_exists($field, $request)) {
                if(empty($request[$field])){
                    return $this->response->requiredFieldMissingResponse();
                }
            }
        }

        try{
            $this->db->beginTransaction();

            if($this->db->table($this->table_courses)->where('id', $request['id'])->update($request)){
                $this->db->commit();
                return $this->response->buildApiResponse($request, $this->response_columns);
            } else{
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }

        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function deleteCourse(Request $request, $id){


         //check if the id is numeric and has value
        if (empty($id) && !is_numeric($id)) {
            return $this->response->errorResponse("Invalid Request");
        }
        $request = $request->all();

        if(!isset($request['id']) || empty($request['id']) || !is_numeric($request['id'])){
            //if id is not set in $request, empty or non numeric
            return $this->response->invalidParameterResponse();
        }
        if($request['id'] != $id){
            //if ids doesnt match
            return $this->response->errorResponse("ID doesn't match!");
        }
        try{


            $this->db->beginTransaction();

            if($this->db->table($this->table_courses)->where('id', $request['id'])->update(["is_deleted" => 1])){
                $this->db->commit();
                return $this->response->successResponse("Data has been deleted!");
            } else{
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }
        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function upload(Request $request, $id){


         /**
         *
         * start with other validations here
         *
         * */


        try{

             /**
             *
             *
             * insert your code here
             *
             * can remove this comment after
             *
             *
             * */

        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function getQuiz(Request $params, $id = null){

        try{

            $query_result = null;

            //This section is intended for fetching specific quiz record
            if ($id) {
                $this->response_columns = ["id", "course_id", "quiz_name", "passing_percentage", "date_time_added", "date_time_updated"];
                $query_result = $this->db->table($this->table_quizzes)->select($this->response_columns)->where('id',$id)->get();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["id", "course_id", "quiz_name", "passing_percentage", "date_time_added", "date_time_updated"];
                $query_result = $this->db->table($this->table_quizzes)->select($this->response_columns)->where('is_deleted', '=', 0)->where('course_id', $params->query('course_id'))->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["id", "quiz_name", "date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_quizzes)
                ->select($this->response_columns)
                ->where('is_deleted', '=', 0)
                ->where(function ($query) use ($keyword) {
                    $query->where('id', 'like', '%' . $keyword . '%')
                          ->orWhere('quiz_name', 'like', '%' . $keyword . '%')
                          ->orWhere('date_time_added', 'like', '%' . $keyword . '%');
                })
                ->get();
            }

            return $this->response->buildApiResponse($query_result, $this->response_columns);

        }
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function postQuiz(Request $request){


        $request = $request->all();

        $this->accepted_parameters = [
            "course_id",
            "quiz_name",
            "passing_percentage",
        ];

        if(!empty($request)){
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        $this->required_fields = [
            "course_id",
            "quiz_name",
            "passing_percentage",
        ];

        //check if the required fields are filled and has values
        foreach ($this->required_fields as $field) {
            if (!array_key_exists($field, $request)) {
                if(empty($request[$field])){
                    return $this->response->requiredFieldMissingResponse();
                }

            }
        }

        try{

            $this->db->beginTransaction();

            //insert to table
            $request['date_time_added'] = date('Y-m-d H:i:s');
            $request['id'] = $this->db->table($this->table_quizzes)->insertGetId($request);

            if($request['id']) {
                $this->db->commit();
                $this->response_columns = [
                    "id",
                    "quiz_name",
                    "passing_percentage",
                ];
                return $this->response->buildApiResponse($request, $this->response_columns);
            } else{
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }
        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }

    }

    public function getQuestion(Request $params, $id = null){

        try{

            $query_result = null;

            //This section is intended for fetching specific question record
            if ($id) {
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated"];
                $query_result = $this->db->table($this->table_questions)->select($this->response_columns)->where('id',$id)->get();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["lms_questions.id as id", "question_text", "marks", "date_time_added", "date_time_updated", "lms_choices.choice_text","lms_choices.explanation","lms_choices.is_correct"];
                $query_result = $this->db->table($this->table_questions)->select($this->response_columns)->where('is_deleted', '=', 0)->where('quiz_id', $params->query('quiz_id'))->leftJoin("lms_choices", "{$this->table_questions}.id", '=', "lms_choices.question_id")->offset(trim($params->query('offset'), '"'))->limit(1000)->get();


                // Group choices under each question
                $groupedQuestions = [];
                foreach ($query_result as $row) {
                    $questionId = $row->id;

                    // Initialize the question if not already set
                    if (!isset($groupedQuestions[$questionId])) {
                        $groupedQuestions[$questionId] = [
                            'id' => $row->id,
                            'question_text' => $row->question_text,
                            'marks' => $row->marks,
                            'date_time_added' => $row->date_time_added,
                            'date_time_updated' => $row->date_time_updated,
                            'choices' => []
                        ];
                    }

                    // Add choice information to the question's choices
                    if (!is_null($row->choice_text)) {
                        $groupedQuestions[$questionId]['choices'][] = [
                            'choice_text' => $row->choice_text,
                            'explanation' => $row->explanation,
                            'is_correct' => $row->is_correct
                        ];
                    }
                }

                // Convert grouped data to an array
                $finalResult = array_values($groupedQuestions);
                $query_result = $finalResult;
                // Define new response columns customed for questions and choices
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated", "choices"];
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["lms_questions.id as id", "question_text", "marks", "date_time_added", "date_time_updated", "lms_choices.choice_text","lms_choices.explanation","lms_choices.is_correct"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_questions)->select($this->response_columns)->where('is_deleted', '=', 0)->where('quiz_id', $params->query('quiz_id'))->where(function ($query) use ($keyword) {
                    $query->where('choice_text', 'like', '%' . $keyword . '%')
                          ->orWhere('question_text', 'like', '%' . $keyword . '%')
                          ->orWhere('marks', 'like', '%' . $keyword . '%');
                })->leftJoin("lms_choices", "{$this->table_questions}.id", '=', "lms_choices.question_id")->offset(trim($params->query('offset'), '"'))->limit(1000)->get();


                // Group choices under each question
                $groupedQuestions = [];
                foreach ($query_result as $row) {
                    $questionId = $row->id;

                    // Initialize the question if not already set
                    if (!isset($groupedQuestions[$questionId])) {
                        $groupedQuestions[$questionId] = [
                            'id' => $row->id,
                            'question_text' => $row->question_text,
                            'marks' => $row->marks,
                            'date_time_added' => $row->date_time_added,
                            'date_time_updated' => $row->date_time_updated,
                            'choices' => []
                        ];
                    }

                    // Add choice information to the question's choices
                    if (!is_null($row->choice_text)) {
                        $groupedQuestions[$questionId]['choices'][] = [
                            'choice_text' => $row->choice_text,
                            'explanation' => $row->explanation,
                            'is_correct' => $row->is_correct
                        ];
                    }
                }

                // Convert grouped data to an array
                $finalResult = array_values($groupedQuestions);
                $query_result = $finalResult;
                // Define new response columns customed for questions and choices
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated", "choices"];
            }

            return $this->response->buildApiResponse($query_result, $this->response_columns);

        }
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }
    public function postQuestion(Request $request){


        $request = $request->all();

        $this->accepted_parameters = [
            "quiz_id",
            "questions",
        ];

        if(!empty($request)){
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        $this->required_fields = [
            "quiz_id",
            "questions",
        ];

        //check if the required fields are filled and has values
        foreach ($this->required_fields as $field) {
            if (!array_key_exists($field, $request)) {
                if(empty($request[$field])){
                    return $this->response->requiredFieldMissingResponse();
                }

            }
        }

        try{

            $this->db->beginTransaction();

            foreach($request['questions'] as $question) {


                $questions = [
                    'quiz_id' => $request['quiz_id'],
                    'question_text' => $question['question_text'],
                    'date_time_added' => date('Y-m-d H:i:s')
                ];

                $question_id = $this->db->table($this->table_questions)->insertGetId($questions);
                $request['id'] = $question_id;

                 // Prepare choices for the current question
                $choices = array_map(function ($choice) use ($question_id) {
                    return [
                        'question_id' => $question_id,
                        'choice_text' => $choice['choice_text'],
                        'explanation' => $choice['explanation'],
                        'is_correct' => $choice['is_correct'],
                    ];
                }, $question['choices']);


                $this->db->table('lms_choices')->insert($choices);
            }

            if($request['id']) {
                $this->db->commit();
                $this->response_columns = [
                    "id",
                    "quiz_id",
                    "questions",
                ];
                return $this->response->buildApiResponse($request, $this->response_columns);
            } else{
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }

        }
        catch(QueryException $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }

    }
}

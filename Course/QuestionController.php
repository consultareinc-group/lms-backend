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

/**
 *
 * replace the ApiController based on the module name + ApiController ex. moduleNameApiController
 *
 */
class QuestionController extends Controller {

    protected $response;
    protected $db;
    public function __construct(Request $request) {
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
    protected $accepted_parameters = [];

    /**
     *
     * modify required fields based on accepted parameters
     *
     * */
    protected $required_fields = [];

    /**
     *
     * modify response column
     *
     * */
    protected $response_columns = [];

    /**
     *
     * modify table name
     *
     * */
    protected $table_questions = 'lms_questions';
    protected $table_choices = 'lms_choices';


    public function get(Request $params, $id = null) {

        try {

            $query_result = [];

            //This section is intended for fetching specific question record
            if ($id) {
                $this->response_columns = ["lms_questions.id as id", "question_text", "marks", "date_time_added", "date_time_updated", "lms_choices.id as choice_id", "lms_choices.choice_text", "lms_choices.explanation", "lms_choices.is_correct"];
                $query_result = $this->db->table($this->table_questions)
                    ->select($this->response_columns)
                    ->where('is_deleted', '=', 0)
                    ->where("{$this->table_questions}.id", $id)
                    ->leftJoin("lms_choices", "{$this->table_questions}.id", '=', "lms_choices.question_id")
                    ->get();

                // Group choices under each question
                $groupedQuestions = [];
                foreach ($query_result as $row) {
                    $questionId = (int) $row->id;

                    // Initialize the question if not already set
                    if (!isset($groupedQuestions[$questionId])) {
                        $groupedQuestions[$questionId] = [
                            'id' => $questionId,
                            'question_text' => $row->question_text,
                            'choices' => []
                        ];
                    }

                    // Add choice information to the question's choices
                    if (!is_null($row->choice_text)) {
                        $groupedQuestions[$questionId]['choices'][] = [
                            'id' => isset($row->choice_id) ? (int) $row->choice_id : null,
                            'choice_text' => $row->choice_text,
                            'explanation' => $row->explanation,
                            'is_correct' => isset($row->is_correct) ? (int) $row->is_correct : 0,
                        ];
                    }
                }

                $finalResult = array_values($groupedQuestions);
                $query_result = $finalResult;
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated", "choices"];
            }

            if ($params->has('offset')) {
                $this->response_columns = ["lms_questions.id as id", "question_text", "marks", "date_time_added", "date_time_updated", "lms_choices.choice_text", "lms_choices.explanation", "lms_choices.is_correct"];
                $query_result = $this->db->table($this->table_questions)
                    ->select($this->response_columns)
                    ->where('is_deleted', '=', 0)
                    ->where('quiz_id', $params->query('quiz_id'))
                    ->leftJoin("lms_choices", "{$this->table_questions}.id", '=', "lms_choices.question_id")
                    ->offset(trim($params->query('offset'), '"'))
                    ->limit(1000)
                    ->get();

                $groupedQuestions = [];
                foreach ($query_result as $row) {
                    $questionId = (int) $row->id;

                    if (!isset($groupedQuestions[$questionId])) {
                        $groupedQuestions[$questionId] = [
                            'id' => $questionId,
                            'question_text' => $row->question_text,
                            'date_time_added' => $row->date_time_added,
                            'date_time_updated' => $row->date_time_updated,
                            'choices' => []
                        ];
                    }

                    if (!is_null($row->choice_text)) {
                        $groupedQuestions[$questionId]['choices'][] = [
                            'choice_text' => $row->choice_text,
                            'explanation' => $row->explanation,
                            'is_correct' => isset($row->is_correct) ? (int) $row->is_correct : 0,
                        ];
                    }
                }

                $finalResult = array_values($groupedQuestions);
                $query_result = $finalResult;
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated", "choices"];
            }

            if ($params->has('search_keyword')) {
                $this->response_columns = ["lms_questions.id as id", "question_text", "marks", "date_time_added", "date_time_updated", "lms_choices.choice_text", "lms_choices.explanation", "lms_choices.is_correct"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_questions)
                    ->select($this->response_columns)
                    ->where('is_deleted', '=', 0)
                    ->where('quiz_id', $params->query('quiz_id'))
                    ->where(function ($query) use ($keyword) {
                        $query->where('choice_text', 'like', '%' . $keyword . '%')
                            ->orWhere('question_text', 'like', '%' . $keyword . '%')
                            ->orWhere('marks', 'like', '%' . $keyword . '%');
                    })
                    ->leftJoin("lms_choices", "{$this->table_questions}.id", '=', "lms_choices.question_id")
                    ->offset(trim($params->query('offset'), '"'))
                    ->limit(1000)
                    ->get();

                $groupedQuestions = [];
                foreach ($query_result as $row) {
                    $questionId = (int) $row->id;

                    if (!isset($groupedQuestions[$questionId])) {
                        $groupedQuestions[$questionId] = [
                            'id' => $questionId,
                            'question_text' => $row->question_text,
                            'marks' => isset($row->marks) ? (int) $row->marks : null,
                            'date_time_added' => $row->date_time_added,
                            'date_time_updated' => $row->date_time_updated,
                            'choices' => []
                        ];
                    }

                    if (!is_null($row->choice_text)) {
                        $groupedQuestions[$questionId]['choices'][] = [
                            'choice_text' => $row->choice_text,
                            'explanation' => $row->explanation,
                            'is_correct' => isset($row->is_correct) ? (int) $row->is_correct : 0,
                        ];
                    }
                }

                $finalResult = array_values($groupedQuestions);
                $query_result = $finalResult;
                $this->response_columns = ["id", "question_text", "marks", "date_time_added", "date_time_updated", "choices"];
            }

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException  $e) {
            return $this->response->errorResponse($e);
        }
    }


    public function post(Request $request) {


        $request = $request->all();

        $this->accepted_parameters = [
            "quiz_id",
            "questions",
        ];

        if (!empty($request)) {
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
                if (empty($request[$field])) {
                    return $this->response->requiredFieldMissingResponse();
                }
            }
        }

        try {

            $this->db->beginTransaction();

            foreach ($request['questions'] as $question) {


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

            if ($request['id']) {
                $this->db->commit();
                $this->response_columns = [
                    "id",
                    "quiz_id",
                    "questions",
                ];
                return $this->response->buildApiResponse($request, $this->response_columns);
            } else {
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }
    public function put(Request $request, $id) {


        $request = $request->all();

        $this->accepted_parameters = [
            "id",
            "question_text",
            "choices",
        ];

        if (!empty($request)) {
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        //check if the Ids matches
        if ($request['id'] != $id) {
            return $this->response->errorResponse("Ids Dont Match");
        }

        $this->required_fields = [
            "id",
            "question_text",
            "choices",
        ];

        //check if the required fields are filled and has values
        foreach ($this->required_fields as $field) {
            if (!array_key_exists($field, $request)) {
                if (empty($request[$field])) {
                    return $this->response->requiredFieldMissingResponse();
                }
            }
        }

        try {

            $this->db->beginTransaction();

            // Transform $request['choices'] into the format required for upsert
            // $choicesData = array_map(function ($choice) use ($request) {
            //     return [
            //         'question_id' => $request['id'],
            //         'choice_id' => $choice['id'] ?? null, // Handle cases where 'id' might not exist
            //         'choice_text' => $choice['choice_text'] ?? '',
            //         'explanation' => $choice['explanation'] ?? null,
            //         'is_correct' => $choice['is_correct'] ?? 0,
            //     ];
            // }, $request['choices']);

            $choices = $request['choices'];
            unset($request['choices']);
            // Check if the data hasn't changed.
            $exists = $this->db->table($this->table_questions)->where('id', $id)->where($request)->exists();

            if (!$exists) {
                $result = $this->db->table($this->table_questions)->where('id', $id)->update($request);
                if (!$result) {
                    $this->db->rollback();
                    return $this->response->errorResponse("The question was not saved successfully");
                }
            }

            // delete choices based on question id
            $result = $this->db->table($this->table_choices)->where('question_id', $id)->delete();
            if (!$result) {
                $this->db->rollback();
                return $this->response->errorResponse("Choices were not deleted successfully");
            }

            // Remove the 'id' key from each choices
            foreach ($choices as &$item) {
                $item['question_id'] = $id;
                unset($item['id']);
            }

            // insert fresh choices
            $this->db->table($this->table_choices)->insert($choices);
            $last_id = $this->db->getPdo()->lastInsertId();

            if (!$last_id) {
                $this->db->rollback();
                return $this->response->errorResponse("Choices were not inserted successfully");
            }

            $this->db->commit();
            return $this->response->buildApiResponse($request, $this->response_columns);
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }
}

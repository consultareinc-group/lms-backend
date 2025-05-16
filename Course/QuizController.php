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
class QuizController extends Controller {

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
    protected $table_quizzes = 'lms_quizzes';


    public function get(Request $params, $id = null) {
        try {
            $query_result = null;

            // This section is intended for fetching specific quiz record
            if ($id) {
                $this->response_columns = ["id", "course_id", "quiz_name", "passing_percentage", "date_time_added", "date_time_updated"];
                $result = $this->db->table($this->table_quizzes)->select($this->response_columns)->where('id', $id)->first();

                if ($result) {
                    // Manual casting
                    $result->id = (int) $result->id;
                    $result->course_id = (int) $result->course_id;
                    $result->passing_percentage = (int) $result->passing_percentage;
                }
                $query_result = $result;
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["id", "course_id", "quiz_name", "passing_percentage", "date_time_added", "date_time_updated"];
                $results = $this->db->table($this->table_quizzes)
                    ->select($this->response_columns)
                    ->where('is_deleted', '=', 0)
                    ->where('course_id', $params->query('course_id'))
                    ->offset(trim($params->query('offset'), '"'))
                    ->limit(1000)
                    ->reorder('id', 'desc')
                    ->get();

                // Manual casting for each record
                $results = $results->map(function ($item) {
                    $item->id = (int) $item->id;
                    $item->course_id = (int) $item->course_id;
                    $item->passing_percentage = (int) $item->passing_percentage;
                    return $item;
                });

                $query_result = $results;
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["id", "quiz_name", "date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $results = $this->db->table($this->table_quizzes)
                    ->select($this->response_columns)
                    ->where('is_deleted', '=', 0)
                    ->where(function ($query) use ($keyword) {
                        $query->where('id', 'like', '%' . $keyword . '%')
                            ->orWhere('quiz_name', 'like', '%' . $keyword . '%')
                            ->orWhere('date_time_added', 'like', '%' . $keyword . '%');
                    })
                    ->get();

                // Manual casting for each record (id only, as course_id and passing_percentage not selected here)
                $results = $results->map(function ($item) {
                    $item->id = (int) $item->id;
                    return $item;
                });

                $query_result = $results;
            }

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }


    public function post(Request $request) {


        $request = $request->all();

        $this->accepted_parameters = [
            "course_id",
            "quiz_name",
            "passing_percentage",
        ];

        if (!empty($request)) {
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
                if (empty($request[$field])) {
                    return $this->response->requiredFieldMissingResponse();
                }
            }
        }

        try {

            $this->db->beginTransaction();

            //insert to table
            $request['date_time_added'] = date('Y-m-d H:i:s');
            $request['id'] = $this->db->table($this->table_quizzes)->insertGetId($request);

            if ($request['id']) {
                $this->db->commit();
                $this->response_columns = [
                    "id",
                    "quiz_name",
                    "passing_percentage",
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
            "quiz_name",
            "passing_percentage",
        ];

        if (empty($request)) {
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
            "quiz_name",
            "passing_percentage",
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

            // Check if the data hasn't changed.
            $exists = $this->db->table($this->table_quizzes)->where('id', $id)->where($request)->exists();

            if (!$exists) {
                $result = $this->db->table($this->table_quizzes)->where('id', $request['id'])->update($request);
                if (!$result) {
                    $this->db->rollback();
                    return $this->response->errorResponse("Data Saved Unsuccessfully");
                }
            }

            $this->db->commit();
            return $this->response->buildApiResponse($request, $this->response_columns);
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }
}

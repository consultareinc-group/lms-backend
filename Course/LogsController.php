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
use Illuminate\Support\Collection;
use App\Helpers\ResponseHelper;
use App\Helpers\UserInfoHelper;
use Carbon\Carbon;

/**
 *
 * replace the ApiController based on the module name + ApiController ex. moduleNameApiController
 *
 */
class LogsController extends Controller {

    protected $response;
    protected $lms;
    protected $accounts;
    protected $user_info_helper;

    public function __construct(Request $request) {
        $this->response = new ResponseHelper($request);
        $this->user_info_helper = new UserInfoHelper();

        /**
         *
         *  Rename lms based on preferred database on database.php
         *
         */
        $this->lms = DB::connection("lms");
        $this->accounts = DB::connection("accounts_connection");
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
    protected $table_courses = 'lms_courses';
    protected $table_logs = 'lms_logs';
    protected $table_quizzes = 'lms_quizzes';
    protected $table_user_information = 'user_information';



    public function get(Request $params, $id = null) {
        try {
            $query_result = null;

            if ($id === '0') {
                return $this->response->errorResponse('Certificate not found.');
            }

            // Helper function to cast integers in an object
            $castInts = function ($item) {
                if (!$item) return $item;
                $item->id = isset($item->id) ? (int) $item->id : null;
                $item->user_id = isset($item->user_id) ? (int) $item->user_id : null;
                $item->quiz_id = isset($item->quiz_id) ? (int) $item->quiz_id : null;
                $item->course_id = isset($item->course_id) ? (int) $item->course_id : null;
                return $item;
            };

            // get logs by id
            if ($id) {
                $query_result = $this->lms->table($this->table_logs)
                    ->select([
                        "{$this->table_logs}.*",
                        "{$this->table_user_information}.id as user_id",
                        "{$this->table_user_information}.first_name",
                        "{$this->table_user_information}.last_name",
                        "{$this->table_user_information}.middle_name",
                        "{$this->table_user_information}.suffix_name",
                        "{$this->table_user_information}.email",
                        "{$this->table_quizzes}.id as quiz_id",
                        "{$this->table_quizzes}.quiz_name",
                        "{$this->table_courses}.id as course_id",
                        "{$this->table_courses}.course_name"
                    ])
                    ->leftJoinSub(
                        $this->accounts->table($this->table_user_information),
                        "{$this->table_user_information}",
                        "{$this->table_logs}.user_id",
                        "=",
                        "{$this->table_user_information}.id"
                    )
                    ->leftJoin($this->table_quizzes, "{$this->table_logs}.quiz_id", "=", "{$this->table_quizzes}.id")
                    ->leftJoin($this->table_courses, "{$this->table_quizzes}.course_id", "=", "{$this->table_courses}.id")
                    ->where("{$this->table_logs}.id", $id)
                    ->first();

                if (!$query_result || $query_result->id === 0) {
                    return $this->response->errorResponse("Certificate not found.");
                }

                $user_role = $this->accounts->table($this->table_user_information)
                    ->where('id', $this->user_info_helper->getUserId())
                    ->value('role');

                if ($user_role != 0 && $query_result->user_id != $this->user_info_helper->getUserId()) {
                    return $this->response->errorResponse('Not Authorized.');
                }

                // Cast the single object result
                $query_result = $castInts($query_result);
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $query_result = $this->lms->table($this->table_logs)
                    ->select([
                        "{$this->table_logs}.*",
                        "{$this->table_user_information}.id as user_id",
                        "{$this->table_user_information}.first_name",
                        "{$this->table_user_information}.last_name",
                        "{$this->table_user_information}.middle_name",
                        "{$this->table_user_information}.suffix_name",
                        "{$this->table_user_information}.email",
                        "{$this->table_quizzes}.id as quiz_id",
                        "{$this->table_quizzes}.quiz_name",
                        "{$this->table_courses}.id as course_id",
                        "{$this->table_courses}.course_name"
                    ])
                    ->leftJoinSub(
                        $this->accounts->table($this->table_user_information),
                        "{$this->table_user_information}",
                        "{$this->table_logs}.user_id",
                        "=",
                        "{$this->table_user_information}.id"
                    )
                    ->leftJoin($this->table_quizzes, "{$this->table_logs}.quiz_id", "=", "{$this->table_quizzes}.id")
                    ->leftJoin($this->table_courses, "{$this->table_quizzes}.course_id", "=", "{$this->table_courses}.id")
                    ->offset((int) trim($params->query('offset'), '"'))
                    ->limit(1000)
                    ->orderBy("{$this->table_logs}.id", 'desc')
                    ->get();

                // Cast collection
                $query_result = $query_result->map(function ($item) use ($castInts) {
                    return $castInts($item);
                });
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->lms->table($this->table_logs)
                    ->select([
                        "{$this->table_logs}.*",
                        "{$this->table_user_information}.id as user_id",
                        "{$this->table_user_information}.first_name",
                        "{$this->table_user_information}.last_name",
                        "{$this->table_user_information}.middle_name",
                        "{$this->table_user_information}.suffix_name",
                        "{$this->table_user_information}.email",
                        "{$this->table_quizzes}.id as quiz_id",
                        "{$this->table_quizzes}.quiz_name",
                        "{$this->table_courses}.id as course_id",
                        "{$this->table_courses}.course_name"
                    ])
                    ->leftJoinSub(
                        $this->accounts->table($this->table_user_information),
                        "{$this->table_user_information}",
                        "{$this->table_logs}.user_id",
                        "=",
                        "{$this->table_user_information}.id"
                    )
                    ->leftJoin($this->table_quizzes, "{$this->table_logs}.quiz_id", "=", "{$this->table_quizzes}.id")
                    ->leftJoin($this->table_courses, "{$this->table_quizzes}.course_id", "=", "{$this->table_courses}.id")
                    ->where(function ($query) use ($keyword) {
                        $query
                            ->where("{$this->table_user_information}.first_name", 'LIKE', "%$keyword%")
                            ->orWhere("{$this->table_user_information}.last_name", 'LIKE', "%$keyword%")
                            ->orWhere("{$this->table_user_information}.middle_name", 'LIKE', "%$keyword%")
                            ->orWhere("{$this->table_user_information}.suffix_name", 'LIKE', "%$keyword%")
                            ->orWhere("{$this->table_user_information}.email", 'LIKE', "%$keyword%")
                            ->orWhere("{$this->table_courses}.course_name", 'LIKE', "%$keyword%");
                    })
                    ->orderBy("{$this->table_logs}.id", 'desc')
                    ->get();

                // Cast collection
                $query_result = $query_result->map(function ($item) use ($castInts) {
                    return $castInts($item);
                });
            }

            // if params has user_id
            if ($params->has('user_id')) {
                $user_id = trim($params->query('user_id'), '"');
                $query_result = $this->lms->table($this->table_logs)
                    ->select([
                        "{$this->table_logs}.*",
                        "{$this->table_user_information}.id as user_id",
                        "{$this->table_user_information}.first_name",
                        "{$this->table_user_information}.last_name",
                        "{$this->table_user_information}.middle_name",
                        "{$this->table_user_information}.suffix_name",
                        "{$this->table_user_information}.email",
                        "{$this->table_quizzes}.id as quiz_id",
                        "{$this->table_quizzes}.quiz_name",
                        "{$this->table_courses}.id as course_id",
                        "{$this->table_courses}.course_name"
                    ])
                    ->leftJoinSub(
                        $this->accounts->table($this->table_user_information),
                        "{$this->table_user_information}",
                        "{$this->table_logs}.user_id",
                        "=",
                        "{$this->table_user_information}.id"
                    )
                    ->leftJoin($this->table_quizzes, "{$this->table_logs}.quiz_id", "=", "{$this->table_quizzes}.id")
                    ->leftJoin($this->table_courses, "{$this->table_quizzes}.course_id", "=", "{$this->table_courses}.id")
                    ->where("{$this->table_logs}.user_id", $user_id)
                    ->orderBy("{$this->table_logs}.id", 'desc')
                    ->get();

                if (!$query_result) {
                    return $this->response->errorResponse('No logs found.');
                }

                if ($user_id == 0) {
                    return $this->response->errorResponse('User not found.');
                }

                $user_role = $this->accounts->table($this->table_user_information)
                    ->where('id', $this->user_info_helper->getUserId())
                    ->value('role');

                if ($user_role != 0 && $query_result[0]->user_id != $this->user_info_helper->getUserId()) {
                    return $this->response->errorResponse('Not Authorized.');
                }

                // Cast collection
                $query_result = $query_result->map(function ($item) use ($castInts) {
                    return $castInts($item);
                });
            }

            $this->response_columns = [
                "id",
                "user_id",
                "first_name",
                "last_name",
                "middle_name",
                "suffix_name",
                "email",
                "quiz_id",
                "quiz_name",
                "course_id",
                "course_name",
                "date_time_completed",
            ];

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException $e) {
            return $this->response->errorResponse($e);
        }
    }



    public function post(Request $request) {
        $this->accepted_parameters = [
            "id",
            "quiz_id",
            "user_id",
        ];

        $this->required_fields = [
            "quiz_id",
        ];

        $this->response_columns = [
            "id",
            "quiz_id",
            "user_id",
            "date_time_completed"
        ];

        $request = $request->all();

        if (!empty($request)) {
            foreach ($request as $field => $value) {
                if (!in_array($field, $this->accepted_parameters)) {
                    return $this->response->invalidParameterResponse();
                }
            }
        }

        // Collect missing required fields
        $missing_fields = [];
        foreach ($this->required_fields as $field) {
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
            $this->lms->beginTransaction();

            $request["user_id"] = $this->user_info_helper->getUserId();
            $request["date_time_completed"] = Carbon::now();

            if (isset($request["id"])) {
                unset($request["id"]);
            }
            $id = $this->lms->table("lms_logs")->insertGetId($request);
            if ($id) {
                $query_result = $this->lms->table("lms_logs")->where("id", $id)->get();
                $this->lms->commit();

                // --- Manual casting starts here ---
                $castInts = function ($item) {
                    if (!$item) return $item;
                    $item->id = isset($item->id) ? (int) $item->id : null;
                    $item->quiz_id = isset($item->quiz_id) ? (int) $item->quiz_id : null;
                    $item->user_id = isset($item->user_id) ? (int) $item->user_id : null;
                    return $item;
                };

                if ($query_result instanceof \Illuminate\Support\Collection) {
                    $query_result = $query_result->map(function ($item) use ($castInts) {
                        return $castInts($item);
                    });
                } else {
                    $query_result = $castInts($query_result);
                }
                // --- End manual casting ---

                return $this->response->buildApiResponse($query_result, $this->response_columns);
            } else {
                $this->lms->rollBack();
                return $this->response->errorResponse("Saving failed.");
            }
        } catch (QueryException $e) {
            return $this->response->errorResponse($e);
        }
    }


    public function put(Request $request, $id) {
    }

    public function delete(Request $request, $id) {
    }

    public function upload(Request $request, $id) {


        /**
         *
         * start with other validations here
         *
         * */


        try {

            /**
             *
             *
             * insert your code here
             *
             * can remove this comment after
             *
             *
             * */
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }
}

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
        "quizzes"
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
    protected $response_column = [
        "id",
        "course_name",
        "course_description",
        "number_of_quizzes",
        "video_link",
        "status",
        "date_time_added",
        "date_time_updated",
        "is_deleted",
        "quizzes"
    ];

    /**
     *
     * modify table name
     *
     * */
    protected $table = 'lms_courses';



    public function get(Request $params, $id = null){

        try{

            $query_result = $columns = null;

            //This section is intended for fetching specific course record
            if ($id) {
                $columns = ["id", "course_name", "status", "video_link", "course_description"];
                $query_result = $this->db->table($this->table)->select($columns)->where('id',$id)->get();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $columns = ["id", "course_name", "status", "date_time_added"];
                $query_result = $this->db->table($this->table)->select($columns)->where('is_deleted', '=', 0)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $columns = ["id", "course_name", "status", "date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table)
                ->select($columns)
                ->where('is_deleted', '=', 0)
                ->where(function ($query) use ($keyword) {
                    $query->where('id', 'like', '%' . $keyword . '%')
                          ->orWhere('course_name', 'like', '%' . $keyword . '%')
                          ->orWhere('date_time_added', 'like', '%' . $keyword . '%');
                })
                ->get();
            }

            return $this->response->buildApiResponse($query_result, $columns);

        }
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function post(Request $request){


        $request = $request->all();



        // foreach($request['quizzes'] as $key => $value) {
        //     $request['quizzes'][$key]['course_id'] = 1;
        // }

        // print_r($request);
        // return;



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

            // exclude key name quizzes from course column query insertion
            $quizzes = $request['quizzes'];

            //insert course
            unset($request['quizzes']);
            $request['date_time_added'] = date('Y-m-d H:i:s');
            $request['id'] = $this->db->table($this->table)->insertGetId($request);

            // bind course id to quizzes
            foreach($quizzes as $key => $value) {
                $quizzes[$key]['course_id'] = $request['id'];
            }

            // insert quizzes
            $this->db->table('lms_quizzes')->insert($quizzes);
            $lastId = $this->db->getPdo()->lastInsertId();
            $count = count($quizzes);
            $ids = range($lastId - $count + 1, $lastId);

            // bring back the key name quizzes to include in response column
            $request['quizzes'] = $quizzes;

            // print_r($ids);


            if($request['id']) {
                $this->db->commit();
                return $this->response->buildApiResponse($request, $this->response_column);
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

    public function put(Request $request, $id){

        $request = $request->all();
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

            if($this->db->table($this->table)->where('id', $request['id'])->update($request)){
                $this->db->commit();
                return $this->response->buildApiResponse($request, $this->response_column);
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

    public function delete(Request $request, $id){


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

            if($this->db->table($this->table)->where('id', $request['id'])->update(["is_deleted" => 1])){
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
}

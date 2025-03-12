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
class CourseController extends Controller
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
        "category_id",
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
        "catergory_id",
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
        "category_id",
        "category_name",
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


    public function get(Request $params, $id = null){

        try{

            $query_result = null;

            //This section is intended for fetching specific course record
            if ($id) {
                $columns = ["cr.id", "ct.category_name", "cr.course_name", "cr.status", "cr.video_link", "cr.course_description", "cr.date_time_added", "cr.date_time_updated"];
                $query_result = $this->db->table($this->table_courses. " as cr")->select($columns)->join("lms_categories as ct", "ct.id", "=", "cr.category_id")->where('cr.id',$id)->first();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $columns = ["cr.id", "ct.category_name", "cr.course_name", "cr.video_link", "cr.course_description", "cr.status", "cr.date_time_added"];
                $query_result = $this->db->table($this->table_courses. " as cr")->select($columns)->join("lms_categories as ct", "ct.id", "=", "cr.category_id")->where('cr.is_deleted', '=', 0)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('cr.id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $columns = ["cr.id", "ct.category_name", "cr.course_name", "cr.video_link", "cr.course_description", "cr.status", "cr.date_time_added"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_courses. " as cr")
                ->select($columns)
                ->join("lms_categories as ct", "ct.id", "=", "cr.category_id")
                ->where('cr.is_deleted', '=', 0)
                ->where(function ($query) use ($keyword) {
                    $query->where('cr.id', 'like', '%' . $keyword . '%')
                          ->orWhere('cr.course_name', 'like', '%' . $keyword . '%')
                          ->orWhere('ct.category_name', 'like', '%' . $keyword . '%')
                          ->orWhere('cr.date_time_added', 'like', '%' . $keyword . '%');
                })
                ->get();
            }

            $this->response_columns = [
                "id",
                "category_id",
                "category_name",
                "course_name",
                "course_description",
                "number_of_quizzes",
                "video_link",
                "status",
                "date_time_added",
                "date_time_updated",
                "is_deleted",
            ];

            return $this->response->buildApiResponse($query_result, $this->response_columns);

        }
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function post(Request $request){


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

    public function put(Request $request, $id){

        $request = $request->all();

        $this->accepted_parameters = [
            "id",
            "category_id",
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
            "category_id",
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

            // Check if the data hasn't changed.
            $exists = $this->db->table($this->table_courses)->where('id', $id)->where($request)->exists();

            if (!$exists) {
                $result = $this->db->table($this->table_courses)->where('id', $request['id'])->update($request);
                if (!$result) {
                    $this->db->rollback();
                    return $this->response->errorResponse("Data Saved Unsuccessfully");
                }
            }

            $this->db->commit();
            return $this->response->buildApiResponse($request, $this->response_columns);

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
}

<?php

namespace App\Http\Controllers\LMS\Category;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;

class CategoryController extends Controller
{

    protected $response;
    protected $validation;
    protected $db;
    public function __construct(Request $request)
    {
        $this->response = new ResponseHelper($request);
        $this->validation = new ValidationHelper();

        $this->db = DB::connection("lms");
    }

    protected $accepted_parameters = [];

    protected $required_fields = [];

    protected $response_columns = [];

    protected $table_categories = 'lms_categories';


    public function get(Request $params, $id = null){

        try{

            $query_result = null;

            //This section is intended for fetching specific course record
            if ($id) {
                $this->response_columns = ["id", "category_name", "category_description", "date_time_added"];
                $query_result = $this->db->table($this->table_categories)->select($this->response_columns)->where('id',$id)->first();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["id", "category_name", "category_description"];
                $query_result = $this->db->table($this->table_categories)->select($this->response_columns)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["id", "category_name", "category_description"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_categories)
                ->select($this->response_columns)
                ->where(function ($query) use ($keyword) {
                    $query->where('category_name', 'like', '%' . $keyword . '%')
                          ->orWhere('category_description', 'like', '%' . $keyword . '%');
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

    public function post(Request $request){

        $this->accepted_parameters = [
            "category_name",
            "category_description"
        ];

        $this->required_fields = [
            "category_name"
        ];

        $request = $this->validation->validateRequest($request, $this->accepted_parameters, $this->required_fields);

        //check if the $request has error validation key
        if(isset($request['error_validation'])){
            return $this->response->errorResponse($request['message']);
        }

        try{

            $this->db->beginTransaction();

            $exists = $this->db->table($this->table_categories)->select('category_name')->where('category_name', $request['category_name'])->exists();

            if ($exists) {
                return $this->response->errorResponse("Category Name already exists!");
            }

            //insert to table
            $request['id'] = $this->db->table($this->table_categories)->insertGetId($request);

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

        //check if the Ids matches
        if($request['id'] != $id){
            return $this->response->errorResponse("Ids Dont Match");
        }

        $this->accepted_parameters = [
            "id",
            "category_name",
            "category_description"
        ];

        $this->required_fields = [
            "id",
            "category_name"
        ];

        $request = $this->validation->validateRequest($request, $this->accepted_parameters, $this->required_fields);

        //check if the $request has error validation key
        if(isset($request['error_validation'])){
            return $this->response->errorResponse($request['message']);
        }

        try{
            $this->db->beginTransaction();

            $exists = $this->db->table($this->table_categories)->select('category_name')->where('category_name', $request['category_name'])->exists();

            if ($exists) {
                return $this->response->errorResponse("Category Name already exists!");
            }

            // Check if the data hasn't changed.
            $exists = $this->db->table($this->table_categories)->where('id', $id)->where($request)->exists();

            if (!$exists) {
                $result = $this->db->table($this->table_categories)->where('id', $request['id'])->update($request);
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

            if($this->db->table($this->table_categories)->where('id', $request['id'])->delete()){
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
    }
}

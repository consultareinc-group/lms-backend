<?php

/**
 * 
 * replace the SystemName based on the Folder
 * 
*/
namespace App\Http\Controllers\SystemName;

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
class ApiController extends Controller
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
        "accepted_parameter1",
        "accepted_parameter2",
        
    ];

    /**
     * 
     * modify required fields based on accepted parameters
     * 
     * */
    protected $required_fields = [
        "id",
        "accepted_parameter1", 
    ];

    /**
     * 
     * modify response column
     * 
     * */
    protected $response_column = [
       "id",
       "response_column1",
       "response_column2",
    ];

    /**
     * 
     * modify table name
     * 
     * */
    protected $table = 'table_name';



    public function get(Request $request){
 
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
        catch(QueryException  $e){
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function post(Request $request){
        

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

    public function update(Request $request, $id){


       

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

    public function delete($id){


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
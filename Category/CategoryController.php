<?php

namespace App\Http\Controllers\LMS\Category;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Helpers\FileHelper;

class CategoryController extends Controller {

    protected $response;
    protected $validation;
    protected $file;
    protected $db;
    public function __construct(Request $request) {
        $this->response = new ResponseHelper($request);
        $this->validation = new ValidationHelper();
        $this->file = new FileHelper();
        $this->db = DB::connection("lms");
    }

    protected $accepted_parameters = [];

    protected $required_fields = [];

    protected $response_columns = [];

    protected $table_categories = 'lms_categories';



    public function get(Request $params, $id = null) {

        try {

            $query_result = null;

            //This section is intended for fetching specific course record
            if ($id) {
                $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp", "date_time_added"];
                $query_result = $this->db->table($this->table_categories)->select($this->response_columns)->where('id', $id)->first();
            }

            // This section is intended for pagination
            if ($params->has('offset')) {
                $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp"];
                $query_result = $this->db->table($this->table_categories)->select($this->response_columns)->offset(trim($params->query('offset'), '"'))->limit(1000)->reorder('id', 'desc')->get();
            }

            // This section is intended for table search
            if ($params->has('search_keyword')) {
                $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp"];
                $keyword = trim($params->query('search_keyword'), '"');
                $query_result = $this->db->table($this->table_categories)
                    ->select($this->response_columns)
                    ->where(function ($query) use ($keyword) {
                        $query->where('category_name', 'like', '%' . $keyword . '%')
                            ->orWhere('category_description', 'like', '%' . $keyword . '%');
                    })
                    ->get();
            }

            foreach ($query_result as &$qr) {
                $qr->image_file_base64  = base64_encode(Storage::get('LMS/Categories/' . $qr->image_file_tmp));
            }

            $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp", "image_file_base64", "date_time_added"];

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException  $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function post(Request $request, $id = null) {
        $this->accepted_parameters = [
            "category_name",
            "category_description",
            "image_file"
        ];

        $this->required_fields = [
            "category_name"
        ];

        // Validate request
        $validatedData = $request->validate([
            'category_name' => 'required|string|max:255',
            'category_description' => 'nullable|string',
            'image_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        try {
            $this->db->beginTransaction();

            if ($id) {
                $category = $this->db->table($this->table_categories)->where('id', $id)->first();

                if (!$category) {
                    return $this->response->errorResponse("Category not found.");
                }

                $exists = $this->db->table($this->table_categories)
                    ->where('category_name', $validatedData['category_name'])
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return $this->response->errorResponse("Category Name already exists!");
                }

                if ($request->hasFile('image_file')) {
                    $file = $request->file('image_file');

                    if (!$file->isValid()) {
                        return $this->response->errorResponse("Invalid file upload.");
                    }

                    $generatedFileName = uniqid('img_', true) . '.' . $file->extension();

                    $file->storeAs('LMS/Categories', $generatedFileName);

                    if (!empty($category->image_file_tmp) && Storage::exists('LMS/Categories/' . $category->image_file_tmp)) {
                        Storage::delete('LMS/Categories/' . $category->image_file_tmp);
                    }

                    $validatedData['image_file'] = $file->getClientOriginalName();
                    $validatedData['image_file_tmp'] = $generatedFileName;
                } else {
                    $validatedData['image_file'] = $category->image_file;
                    $validatedData['image_file_tmp'] = $category->image_file_tmp; // Avoid overwriting if not provided
                }

                $this->db->table($this->table_categories)->where('id', $id)->update($validatedData);
            } else {
                $exists = $this->db->table($this->table_categories)
                    ->where('category_name', $validatedData['category_name'])
                    ->exists();

                if ($exists) {
                    return $this->response->errorResponse("Category Name already exists!");
                }

                if ($request->hasFile('image_file')) {
                    $file = $request->file('image_file');

                    if (!$file->isValid()) {
                        return $this->response->errorResponse("Invalid file upload.");
                    }

                    $generatedFileName = uniqid('img_', true) . '.' . $file->extension();

                    $file->storeAs('LMS/Categories', $generatedFileName);

                    $validatedData['image_file'] = $file->getClientOriginalName();
                    $validatedData['image_file_tmp'] = $generatedFileName;
                }

                $id = $this->db->table($this->table_categories)->insertGetId($validatedData);
            }

            $this->db->commit();

            $responseData = [
                'id' => $id,
                'category_name' => $validatedData['category_name'],
                'category_description' => $validatedData['category_description'] ?? null,
                'image_file' => $validatedData['image_file'] ?? null,
                'image_file_tmp' => $validatedData['image_file_tmp'] ?? null,
            ];

            // Only include image_url if image_file_tmp is set
            if (!empty($validatedData['image_file_tmp'])) {
                $responseData['image_url'] = Storage::url('LMS/Categories/' . $validatedData['image_file_tmp']);
            }

            return $this->response->buildApiResponse($responseData, $this->response_columns);
        } catch (QueryException $e) {
            $this->db->rollback();
            return $this->response->errorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->response->errorResponse($e->getMessage());
        }
    }



    public function delete(Request $request, $id) {
        //check if the id is numeric and has value
        if (empty($id) && !is_numeric($id)) {
            return $this->response->errorResponse("Invalid Request");
        }
        $request = $request->all();

        if (!isset($request['id']) || empty($request['id']) || !is_numeric($request['id'])) {
            //if id is not set in $request, empty or non numeric
            return $this->response->invalidParameterResponse();
        }
        if ($request['id'] != $id) {
            //if ids doesnt match
            return $this->response->errorResponse("ID doesn't match!");
        }
        try {


            $this->db->beginTransaction();

            if ($this->db->table($this->table_categories)->where('id', $request['id'])->delete()) {
                $this->db->commit();
                return $this->response->successResponse("Data has been deleted!");
            } else {
                $this->db->rollback();
                return $this->response->errorResponse("Data Saved Unsuccessfully");
            }
        } catch (QueryException $e) {
            // Return validation errors without redirecting
            return $this->response->errorResponse($e);
        }
    }

    public function upload(Request $request, $id) {
    }
}

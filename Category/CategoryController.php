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
            $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp", "date_time_added"];

            if ($id) {
                $id = (int) $id;
                $query_result = $this->db->table($this->table_categories)
                    ->select($this->response_columns)
                    ->where('id', $id)
                    ->first();

                if (!$query_result) {
                    return $this->response->errorResponse("Category not found.");
                }

                // Convert result to an array to maintain consistency
                $query_result = [$query_result];
            } elseif ($params->has('offset')) {
                $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp"];
                $offset = (int) trim($params->query('offset'), '"');
                $query_result = $this->db->table($this->table_categories)
                    ->select($this->response_columns)
                    ->offset($offset)
                    ->limit(1000)
                    ->orderBy('id', 'desc')
                    ->get();
            } elseif ($params->has('search_keyword')) {
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

            if ($query_result) {
                // Helper function to cast and normalize each record
                $castRecord = function ($qr) {
                    $qr->id = isset($qr->id) ? (int) $qr->id : null;
                    $qr->category_name = isset($qr->category_name) ? (string) $qr->category_name : '';
                    $qr->category_description = isset($qr->category_description) ? (string) $qr->category_description : '';
                    $qr->image_file = isset($qr->image_file) ? (string) $qr->image_file : null;
                    $qr->image_file_tmp = isset($qr->image_file_tmp) ? (string) $qr->image_file_tmp : null;
                    $qr->date_time_added = isset($qr->date_time_added) ? (string) $qr->date_time_added : null;

                    if (!empty($qr->image_file_tmp) && Storage::exists('LMS/Categories/' . $qr->image_file_tmp)) {
                        $qr->image_file_base64 = base64_encode(Storage::get('LMS/Categories/' . $qr->image_file_tmp));
                    } else {
                        $qr->image_file_base64 = null;
                    }
                    return $qr;
                };

                // Apply casting for Collection or array of results
                if ($query_result instanceof \Illuminate\Support\Collection) {
                    $query_result = $query_result->map(function ($item) use ($castRecord) {
                        return $castRecord($item);
                    });
                } elseif (is_array($query_result)) {
                    foreach ($query_result as &$qr) {
                        $qr = $castRecord($qr);
                    }
                } else {
                    // Single object result (unlikely here since you converted to array)
                    $query_result = $castRecord($query_result);
                }
            } else {
                return $this->response->errorResponse("No records found.");
            }

            $this->response_columns = ["id", "category_name", "category_description", "image_file", "image_file_tmp", "image_file_base64", "date_time_added"];

            return $this->response->buildApiResponse($query_result, $this->response_columns);
        } catch (QueryException $e) {
            return $this->response->errorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->response->errorResponse($e->getMessage());
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

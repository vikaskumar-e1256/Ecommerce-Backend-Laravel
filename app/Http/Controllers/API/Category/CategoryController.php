<?php

namespace App\Http\Controllers\API\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ApiBaseController;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve all categories from the database
        $categories = Category::all();

        // Check if any categories were found
        if ($categories->isEmpty()) {
            return $this->responseHelper->error('No categories found', Response::HTTP_NOT_FOUND);
        }

        // Return the categories as a JSON response
        return $this->responseHelper->success($categories, 'Categories retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories|max:255',
            'is_active' => 'sometimes|nullable'
        ]);

        if ($validator->fails()) {
            return $this->responseHelper->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $category = Category::create($validator->validated());

            return $this->responseHelper->success($category, 'Category created successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->responseHelper->error('An error occurred while creating the category', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Retrieve the category based on the provided ID
        $category = Category::find($id);

        // Check if the category exists
        if (!$category) {
            return $this->responseHelper->error('Category not found', Response::HTTP_NOT_FOUND);
        }

        // Return the category details as a JSON response
        return $this->responseHelper->success($category, 'Category details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Retrieve the category based on the provided ID
        $category = Category::find($id);

        // Check if the category exists
        if (!$category) {
            return $this->responseHelper->error('Category not found', Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming data with unique validation rules for the "name" field
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'is_active' => 'sometimes|nullable'
        ]);

        if ($validator->fails()) {
            return $this->responseHelper->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update the category with the validated data
        $category->update($validator->validated());

        return $this->responseHelper->success($category, 'Category updated successfully', Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Retrieve the category based on the provided ID
        $category = Category::find($id);

        // Check if the category exists
        if (!$category) {
            return $this->responseHelper->error('Category not found', Response::HTTP_NOT_FOUND);
        }

        // Attempt to delete the category
        try {
            $category->delete();
            return $this->responseHelper->success([], 'Category deleted successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->responseHelper->error('Failed to delete the category', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

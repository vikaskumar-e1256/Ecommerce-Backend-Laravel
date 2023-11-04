<?php

namespace App\Http\Controllers\API\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ApiBaseController;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return $this->responseHelper->success($products, 'Products retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shipping' => 'boolean',
            'category_id' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->responseHelper->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();
            $data['photo'] = ImageUploadService::upload($request->file('photo'), 'products');

            $product = Product::create($data);
            return $this->responseHelper->success($product, 'Product created successfully', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->responseHelper->error('An error occurred while creating the product', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->responseHelper->error('Product not found', Response::HTTP_NOT_FOUND);
        }
        return $this->responseHelper->success($product, 'Product details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->responseHelper->error('Product not found', 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'shipping' => 'boolean',
            'category_id' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->responseHelper->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();
            // Handle the updated photo (image) and store it
            if ($request->hasFile('photo')) {
                $uploadedPhoto = $request->file('photo');
                $photoPath = ImageUploadService::upload($uploadedPhoto, 'products');
                $data['photo'] = $photoPath;

                // Delete the old photo (if it exists) using the service
                ImageUploadService::delete($product->photo);
            }

            $product->update($data);
            return $this->responseHelper->success($product, 'Product updated successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->responseHelper->error('An error occurred while updating the product', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->responseHelper->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $productPhotoPath = $product->photo;

            // Delete the product
            $product->delete();

            // Delete the associated photo using the ImageUploadService or Storage
            ImageUploadService::delete($productPhotoPath); // Use ImageUploadService or Storage

            return $this->responseHelper->success([], 'Product deleted successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->responseHelper->error('An error occurred while deleting the product', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

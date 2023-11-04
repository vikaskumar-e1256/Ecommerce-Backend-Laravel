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
     * sell / arrival
     * get products based on sold param.
     * by sell = /products?sortBy=sold&order=desc&limit=4
     * by arrival = /products?sortBy=created_at&order=desc&limit=4
     * if no param are comming, then return all products
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortBy = $request->input('sortBy', 'id'); // Default to sorting by 'id' if not specified
        $order = $request->input('order', 'asc'); // Default to ascending order if not specified
        $limit = $request->input('limit', null); // No default limit if not specified

        $query = Product::query();

        // Sort the products
        $query->orderBy($sortBy, $order);

        // Limit the number of results
        if ($limit !== null) {
            $query->take($limit);
        }

        $products = $query->get();
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

    public function relatedProducts(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->responseHelper->error('Product not found', Response::HTTP_NOT_FOUND);
        }

        // Get the category_id of the current product
        $categoryId = $product->category_id;

        $limit = $request->input('limit', null); // Get the 'limit' query parameter

        // Build the query to find related products
        $query = Product::where('category_id', $categoryId)
        ->where('id', '!=', $id); // Exclude the current product

        // Limit the number of results if 'limit' is provided
        if ($limit !== null) {
            $query->take($limit);
        }

        $relatedProducts = $query->get();

        return $this->responseHelper->success($relatedProducts, 'Related products retrieved successfully', Response::HTTP_OK);
    }

    /**
     * list products by search
     * we will implement product search in react frontend
     * we will show categories in checkbox and price range in radio buttons
     * as the user clicks on those checkbox and radio buttons
     * we will make api request and show the products to users based on what he wants
     */
    public function listBySearch(Request $request)
    {
        $order = $request->input('order', 'desc');
        $sortBy = $request->input('sortBy', '_id');
        $limit = $request->input('limit', 100);
        $skip = $request->input('skip', 0);

        // Prepare an array to hold the search criteria
        $searchCriteria = [];

        $filters = $request->input('filters', []);

        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                if ($key === 'price') {
                    // Translate the price range to a range of values
                    $priceRange = explode('-', $value);
                    if (count($priceRange) === 2) {
                        $searchCriteria[] = [
                            'key' => 'price',
                            'operator' => '>=',
                            'value' => $priceRange[0],
                        ];
                        $searchCriteria[] = [
                            'key' => 'price',
                            'operator' => '<=',
                            'value' => $priceRange[1],
                        ];
                    }
                } else {
                    $searchCriteria[] = [
                        'key' => $key,
                        'operator' => '=',
                        'value' => $value,
                    ];
                }
            }
        }

        $query = Product::select('*')
            ->with('category')
            ->orderBy($sortBy, $order)
            ->skip($skip)
            ->take($limit);

        foreach ($searchCriteria as $criteria) {
            $query->where($criteria['key'], $criteria['operator'], $criteria['value']);
        }

        $products = $query->get();

        return response()->json([
            'size' => count($products),
            'data' => $products,
        ]);
    }
    // public function searchProducts(Request $request)
    // {
    //     // Get selected category IDs from the request (assuming they are sent as an array)
    //     $selectedCategories = $request->input('categories', []);

    //     // Get selected price range from the request
    //     $selectedPriceRange = $request->input('price_range');

    //     // Start building the query to fetch products
    //     $query = Product::query();

    //     // Filter products based on selected categories (if any)
    //     if (!empty($selectedCategories)) {
    //         $query->whereIn('category_id', $selectedCategories);
    //     }

    //     // Filter products based on selected price range (if any)
    //     if ($selectedPriceRange === 'low') {
    //         $query->where('price', '<', 50); // Adjust the price range as needed
    //     } elseif ($selectedPriceRange === 'medium') {
    //         $query->whereBetween('price', [50, 100]);
    //     } elseif ($selectedPriceRange === 'high') {
    //         $query->where('price', '>', 100); // Adjust the price range as needed
    //     }

    //     // Retrieve the filtered products
    //     $products = $query->get();

    //     return $this->responseHelper->success($products, 'Products retrieved based on search criteria', Response::HTTP_OK);
    // }
}

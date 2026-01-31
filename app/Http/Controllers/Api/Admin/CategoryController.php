<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Traits\FileUploadTrait; // Import the Trait
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    // Use the Trait inside the controller
    use FileUploadTrait;

    /**
     * Get Category Tree
     */
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->with('children.children')
            ->latest()
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Get Flat List for Dropdowns
     */
    public function list()
    {
        return response()->json(Category::select('id', 'name')->get());
    }

    /**
     * Store new Category
     */
    public function store(StoreCategoryRequest $request)
    {
        if (!auth()->user()->can('category.create')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        // Usage of Trait: Upload new image
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadFile($request->file('image'), 'categories');
        }

        $category = Category::create($data);

        return new CategoryResource($category);
    }

    /**
     * Show single category
     */
    public function show($id)
    {
        $category = Category::with('children')->findOrFail($id);
        return new CategoryResource($category);
    }

    /**
     * Update Category
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        if (!auth()->user()->can('category.edit')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = Category::findOrFail($id);
        $data = $request->validated();

        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        // Usage of Trait: Upload new image AND delete old image automatically
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadFile($request->file('image'), 'categories', $category->image);
        }

        $category->update($data);

        return new CategoryResource($category);
    }

    /**
     * Delete Category
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('category.delete')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = Category::findOrFail($id);

        try {
            // 1. Attempt to delete from Database first
            $category->delete();

            // 2. If DB delete is successful, ONLY THEN delete the image
            // (This prevents image deletion if DB fails)
            if ($category->image) {
                $this->deleteFile($category->image);
            }

            return response()->json(['message' => 'Category deleted successfully']);

        } catch (QueryException $e) {
            // Error Code 23000 means Integrity Constraint Violation
            // (Happens when Foreign Key fails: like having children or products)
            if ($e->getCode() == "23000") {
                return response()->json([
                    'message' => 'Cannot delete this category because it has sub-categories or products linked to it. Please delete them first.'
                ], 422); // 422 Unprocessable Entity
            }

            // Return generic server error for other issues
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

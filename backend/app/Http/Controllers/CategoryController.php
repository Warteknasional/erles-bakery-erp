<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('products')->get();

        return $this->success(
            CategoryResource::collection($categories),
            'Daftar kategori berhasil diambil.'
        );
    }

    /**
     * Store a newly created category.
     */
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $data['slug'] = $slug;
        }

        $category = Category::create($data);

        return $this->success(
            new CategoryResource($category),
            'Kategori berhasil ditambahkan.',
            201
        );
    }

    /**
     * Display the specified category by ID or slug.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $category = is_numeric($idOrSlug)
            ? Category::withCount('products')->find($idOrSlug)
            : Category::withCount('products')->where('slug', $idOrSlug)->first();

        if (! $category) {
            return $this->error('Kategori tidak ditemukan.', 404);
        }

        return $this->success(
            new CategoryResource($category),
            'Detail kategori berhasil diambil.'
        );
    }

    /**
     * Update the specified category.
     */
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && empty($data['slug']) && $data['name'] !== $category->name) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return $this->success(
            new CategoryResource($category),
            'Kategori berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->error('Kategori tidak dapat dihapus karena masih digunakan oleh produk.', 422);
        }

        $category->delete();

        return $this->success(null, 'Kategori berhasil dihapus.');
    }
}

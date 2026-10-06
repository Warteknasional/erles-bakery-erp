<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of products with filtering, searching, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // Search by keyword (name or description)
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        } elseif (!$request->user()) {
            // By default, public visitors only see active products
            $query->where('is_active', true);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'id');
        $sortOrder = $request->query('sort_order', 'asc');
        if (in_array($sortBy, ['id', 'nama', 'harga', 'stok', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        // Pagination or full list
        if ($request->boolean('all')) {
            $products = $query->get();
            return $this->success(ProductResource::collection($products), 'Daftar produk berhasil diambil.');
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($item) => new ProductResource($item)),
            'Daftar produk berhasil diambil.'
        );
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-generate slug
        $baseSlug = Str::slug($data['nama']);
        $slug = $baseSlug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $data['slug'] = $slug;
        $data['stok'] = $data['stok'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        if (!empty($data['category_id']) && empty($data['kategori'])) {
            $cat = \App\Models\Category::find($data['category_id']);
            if ($cat) {
                $data['kategori'] = $cat->name;
            }
        } elseif (!empty($data['kategori']) && empty($data['category_id'])) {
            $cat = \App\Models\Category::firstOrCreate(
                ['name' => $data['kategori']],
                ['slug' => Str::slug($data['kategori'])]
            );
            $data['category_id'] = $cat->id;
        }

        $product = Product::create($data);

        return $this->success(
            new ProductResource($product->load('category')),
            'Produk berhasil ditambahkan.',
            201
        );
    }

    /**
     * Display the specified product by ID or slug.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $product = is_numeric($idOrSlug)
            ? Product::with('category')->find($idOrSlug)
            : Product::with('category')->where('slug', $idOrSlug)->first();

        if (!$product) {
            return $this->error('Produk tidak ditemukan.', 404);
        }

        return $this->success(
            new ProductResource($product),
            'Detail produk berhasil diambil.'
        );
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['nama']) && $data['nama'] !== $product->nama) {
            $baseSlug = Str::slug($data['nama']);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $data['slug'] = $slug;
        }

        if (isset($data['category_id']) && empty($data['kategori'])) {
            $cat = \App\Models\Category::find($data['category_id']);
            if ($cat) {
                $data['kategori'] = $cat->name;
            }
        } elseif (!empty($data['kategori'])) {
            $cat = \App\Models\Category::firstOrCreate(
                ['name' => $data['kategori']],
                ['slug' => Str::slug($data['kategori'])]
            );
            $data['category_id'] = $cat->id;
        }

        $product->update($data);

        return $this->success(
            new ProductResource($product->load('category')),
            'Produk berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Check if product is referenced in order_items
        if ($product->orderItems()->exists()) {
            // Soft-deactivate if order history exists
            $product->update(['is_active' => false]);
            return $this->success(
                new ProductResource($product),
                'Produk telah dinonaktifkan karena memiliki riwayat transaksi pesanan.'
            );
        }

        $product->delete();

        return $this->success(null, 'Produk berhasil dihapus.');
    }
}

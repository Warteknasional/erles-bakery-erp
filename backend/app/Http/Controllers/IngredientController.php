<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ingredients.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ingredient::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori') && $request->query('kategori') !== 'Semua Kategori') {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            // Status computed via filter
            if ($status === 'Kritis') {
                $query->whereRaw('stok <= (minimum_stok * 0.5)');
            } elseif ($status === 'Menipis') {
                $query->whereRaw('stok > (minimum_stok * 0.5) AND stok <= minimum_stok');
            } elseif ($status === 'Aman') {
                $query->whereRaw('stok > minimum_stok');
            }
        }

        $query->orderBy('nama', 'asc');

        if ($request->boolean('all')) {
            return $this->successResponse($query->get());
        }

        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $ingredients = $query->paginate($perPage);

        return $this->paginatedResponse($ingredients);
    }

    /**
     * Store a newly created ingredient.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|max:50|unique:ingredients,kode',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'stok' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'minimum_stok' => 'required|numeric|min:0',
        ]);

        if (empty($validated['kode'])) {
            // Auto generate kode BHN-XXX
            $count = Ingredient::count() + 1;
            $validated['kode'] = 'BHN-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            // Ensure unique
            while (Ingredient::where('kode', $validated['kode'])->exists()) {
                $count++;
                $validated['kode'] = 'BHN-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        }

        $ingredient = Ingredient::create($validated);

        return $this->createdResponse($ingredient, 'Bahan baku berhasil ditambahkan');
    }

    /**
     * Display the specified ingredient.
     */
    public function show(Ingredient $ingredient): JsonResponse
    {
        return $this->successResponse($ingredient);
    }

    /**
     * Update the specified ingredient.
     */
    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'kode' => 'sometimes|required|string|max:50|unique:ingredients,kode,' . $ingredient->id,
            'nama' => 'sometimes|required|string|max:255',
            'kategori' => 'sometimes|required|string|max:100',
            'stok' => 'sometimes|required|numeric|min:0',
            'satuan' => 'sometimes|required|string|max:20',
            'minimum_stok' => 'sometimes|required|numeric|min:0',
        ]);

        $ingredient->update($validated);

        return $this->successResponse($ingredient, 'Bahan baku berhasil diperbarui');
    }

    /**
     * Remove the specified ingredient.
     */
    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();

        return $this->successResponse(null, 'Bahan baku berhasil dihapus');
    }
}

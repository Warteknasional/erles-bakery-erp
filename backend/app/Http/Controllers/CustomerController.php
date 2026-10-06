<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of customers with searching and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');
        if (in_array($sortBy, ['id', 'name', 'phone', 'total_orders', 'total_spent', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        if ($request->boolean('all')) {
            $customers = $query->get();
            return $this->success(CustomerResource::collection($customers), 'Daftar pelanggan berhasil diambil.');
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($item) => new CustomerResource($item)),
            'Daftar pelanggan berhasil diambil.'
        );
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return $this->success(
            new CustomerResource($customer),
            'Pelanggan berhasil ditambahkan.',
            201
        );
    }

    /**
     * Display the specified customer with order history.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['orders.items.product']);

        return $this->success(
            new CustomerResource($customer),
            'Detail pelanggan berhasil diambil.'
        );
    }

    /**
     * Update the specified customer.
     */
    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return $this->success(
            new CustomerResource($customer),
            'Data pelanggan berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return $this->success(null, 'Pelanggan berhasil dihapus.');
    }
}

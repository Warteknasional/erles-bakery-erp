<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of orders (Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items.product'])->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search by customer name, phone, or order code
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode_pesanan', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by tanggal_ambil range
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_ambil', '>=', $request->query('tanggal_mulai'));
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal_ambil', '<=', $request->query('tanggal_akhir'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($item) => new OrderResource($item)),
            'Daftar pesanan berhasil diambil.'
        );
    }

    /**
     * Store a newly created order from customer (Public).
     * Server-side calculation ensures price integrity.
     */
    public function store(OrderStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $productIds = collect($validated['items'])->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $customer = \App\Models\Customer::findOrCreateByPhone(
                $validated['customer_phone'],
                $validated['customer_name'],
                $validated['alamat'] ?? null
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'kode_pesanan' => Order::generateKodePesanan(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'alamat' => $validated['alamat'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'tanggal_ambil' => $validated['tanggal_ambil'] ?? null,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $totalPrice = 0;

            foreach ($validated['items'] as $itemData) {
                $product = $products->get($itemData['product_id']);
                if (!$product) {
                    continue;
                }

                $qty = (int) $itemData['qty'];
                $unitPrice = (float) $product->harga;
                $subtotal = $unitPrice * $qty;
                $totalPrice += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_price' => $totalPrice]);
            $customer->increment('total_orders');
            $customer->increment('total_spent', $totalPrice);

            $order->load(['items.product', 'customer']);

            return $order;
        });

        return $this->success(
            new OrderResource($order),
            'Pesanan berhasil dibuat. Kami akan segera menghubungi Anda.',
            201
        );
    }

    /**
     * Display the specified order by ID or kode_pesanan.
     */
    public function show(string $idOrCode): JsonResponse
    {
        $order = is_numeric($idOrCode)
            ? Order::with('items.product')->find($idOrCode)
            : Order::with('items.product')->where('kode_pesanan', $idOrCode)->first();

        if (!$order) {
            return $this->error('Pesanan tidak ditemukan.', 404);
        }

        return $this->success(
            new OrderResource($order),
            'Detail pesanan berhasil diambil.'
        );
    }

    /**
     * Update order status (Admin / Staff).
     */
    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = $request->validated('status');
        $oldStatus = $order->status;

        // Reject invalid status transitions
        if (!$order->canTransitionTo($newStatus)) {
            return $this->error("Transisi status dari '{$oldStatus}' ke '{$newStatus}' tidak diizinkan.", 422);
        }

        DB::transaction(function () use ($order, $newStatus, $oldStatus, $request) {
            $updateData = ['status' => $newStatus];
            if ($request->filled('catatan')) {
                $updateData['catatan'] = trim($order->catatan . "\n[Status: {$newStatus}] " . $request->validated('catatan'));
            }

            $order->update($updateData);

            // If transitioned to completed/selesai, auto-create finance transaction if not exists
            $isComplete = in_array(Order::normalizeStatus($newStatus), ['completed', 'selesai']);
            $wasComplete = in_array(Order::normalizeStatus($oldStatus), ['completed', 'selesai']);

            if ($isComplete && !$wasComplete) {
                $existingTx = FinanceTransaction::where('order_id', $order->id)->first();
                if (!$existingTx && $order->total_price > 0) {
                    FinanceTransaction::create([
                        'tipe' => 'pemasukan',
                        'nominal' => $order->total_price,
                        'kategori' => 'Penjualan',
                        'catatan' => "Pembayaran pesanan {$order->kode_pesanan} ({$order->customer_name})",
                        'tanggal' => now()->toDateString(),
                        'user_id' => $request->user()?->id,
                        'order_id' => $order->id,
                    ]);
                }
            }
        });

        $order->load(['items.product', 'customer']);

        return $this->success(
            new OrderResource($order),
            "Status pesanan berhasil diperbarui menjadi '{$newStatus}'."
        );
    }

    /**
     * Cancel an order (Admin / Staff).
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        if (!$order->canTransitionTo(Order::STATUS_CANCELLED)) {
            return $this->error("Pesanan dengan status '{$order->status}' tidak dapat dibatalkan.", 422);
        }

        DB::transaction(function () use ($order, $request) {
            $reason = $request->input('alasan', $request->input('catatan', 'Dibatalkan oleh admin'));
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'catatan' => trim($order->catatan . "\n[Dibatalkan] " . $reason),
            ]);
        });

        $order->load(['items.product', 'customer']);

        return $this->success(
            new OrderResource($order),
            'Pesanan berhasil dibatalkan.'
        );
    }

    /**
     * Remove or cancel an order (Admin).
     */
    public function destroy(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order) {
            $order->delete();
        });

        return $this->success(null, 'Pesanan berhasil dihapus.');
    }
}

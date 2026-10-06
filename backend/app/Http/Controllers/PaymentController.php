<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentStoreRequest;
use App\Http\Resources\PaymentResource;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of payments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['order:id,kode_pesanan,customer_name,total_price,status', 'user:id,name,role']);

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->query('order_id'));
        }

        if ($request->filled('metode')) {
            $query->where('metode', strtolower($request->query('metode')));
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', strtolower($request->query('tipe')));
        }

        $startDate = $request->query('tanggal_start') ?? $request->query('start_date');
        $endDate = $request->query('tanggal_end') ?? $request->query('end_date');
        $query->dateBetween($startDate, $endDate);

        $totalNominal = (float) (clone $query)->sum('nominal');

        if ($request->boolean('all')) {
            $payments = $query->orderByDesc('tanggal')->orderByDesc('id')->get();
            return $this->success(
                PaymentResource::collection($payments),
                'Daftar pembayaran berhasil diambil.',
                200,
                ['total_nominal' => $totalNominal]
            );
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pembayaran berhasil diambil.',
            'data' => PaymentResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_nominal' => $totalNominal,
            ],
        ]);
    }

    /**
     * Display payments for a specific order.
     */
    public function forOrder(Order $order): JsonResponse
    {
        $order->load(['payments.user']);

        return $this->success(
            PaymentResource::collection($order->payments),
            'Daftar pembayaran pesanan berhasil diambil.',
            200,
            [
                'order_id' => $order->id,
                'kode_pesanan' => $order->kode_pesanan,
                'total_price' => (float) $order->total_price,
                'paid_amount' => (float) ($order->paid_amount ?? 0),
                'sisa_pembayaran' => max(0, (float) $order->total_price - (float) ($order->paid_amount ?? 0)),
                'payment_status' => $order->payment_status ?? 'unpaid',
            ]
        );
    }

    /**
     * Store a newly created payment.
     */
    public function store(PaymentStoreRequest $request, ?Order $order = null): JsonResponse
    {
        if (!$order || !$order->exists) {
            $order = Order::findOrFail($request->input('order_id'));
        }

        // Validate that order is not cancelled
        $normalizedStatus = Order::normalizeStatus($order->status);
        if ($normalizedStatus === 'cancelled') {
            return $this->error('Tidak dapat mencatat pembayaran untuk pesanan yang telah dibatalkan.', 422);
        }

        $nominal = (float) $request->input('nominal');
        $currentPaid = (float) $order->payments()->sum('nominal');
        $orderTotal = (float) $order->total_price;
        $remaining = round(max(0, $orderTotal - $currentPaid), 2);

        if ($nominal > $remaining) {
            return $this->error(
                "Nominal pembayaran (Rp " . number_format($nominal, 0, ',', '.') . ") melebihi sisa tagihan pesanan (Rp " . number_format($remaining, 0, ',', '.') . ").",
                422
            );
        }

        $payment = DB::transaction(function () use ($request, $order, $nominal) {
            $paymentDate = $request->input('tanggal') ?? now()->toDateString();
            $metode = strtolower($request->input('metode'));
            $tipe = strtolower($request->input('tipe'));

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $request->user()?->id,
                'nominal' => $nominal,
                'metode' => $metode,
                'tipe' => $tipe,
                'tanggal' => $paymentDate,
                'catatan' => $request->input('catatan'),
                'bukti_bayar' => $request->input('bukti_bayar'),
            ]);

            // Auto-update order payment status & paid amount
            $order->recalculatePaymentStatus();

            // Auto-create income finance transaction
            FinanceTransaction::create([
                'tipe' => 'pemasukan',
                'nominal' => $nominal,
                'kategori' => 'Penjualan',
                'catatan' => "Pembayaran {$tipe} ({$metode}) pesanan {$order->kode_pesanan}" . ($request->filled('catatan') ? ": {$request->input('catatan')}" : ''),
                'tanggal' => $paymentDate,
                'user_id' => $request->user()?->id,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });

        $payment->load(['order', 'user']);

        return $this->success(
            new PaymentResource($payment),
            'Pembayaran berhasil dicatat.',
            201
        );
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['order', 'user', 'financeTransaction']);

        return $this->success(
            new PaymentResource($payment),
            'Detail pembayaran berhasil diambil.'
        );
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        DB::transaction(function () use ($payment) {
            if ($payment->financeTransaction) {
                $payment->financeTransaction->delete();
            }

            $order = $payment->order;
            $payment->delete();

            $order?->recalculatePaymentStatus();
        });

        return $this->success(null, 'Pembayaran berhasil dihapus.');
    }
}

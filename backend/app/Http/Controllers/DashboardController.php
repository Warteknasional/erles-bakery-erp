<?php

namespace App\Http\Controllers;

use App\Http\Resources\FinanceTransactionResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get aggregate statistics and metrics for the ERP dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Finance totals (All time & this month)
        $totalPemasukan = (float) FinanceTransaction::where('tipe', 'pemasukan')->sum('nominal');
        $totalPengeluaran = (float) FinanceTransaction::where('tipe', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $pemasukanBulanIni = (float) FinanceTransaction::where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('nominal');
        $pengeluaranBulanIni = (float) FinanceTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('nominal');
        $labaBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

        // 2. Order metrics
        $totalOrders = Order::count();
        $activeOrders = Order::whereIn('status', ['pending', 'confirmed', 'processing', 'ready', 'diproses'])->count();
        $completedOrders = Order::whereIn('status', ['completed', 'selesai'])->count();
        $cancelledOrders = Order::whereIn('status', ['cancelled', 'dibatalkan'])->count();

        // 3. Customer & Product counts
        $totalCustomers = Customer::count();
        $totalProducts = Product::where('is_active', true)->count();

        // 4. Latest 5 orders
        $recentOrders = Order::with(['items.product', 'customer'])
            ->latest('id')
            ->limit(5)
            ->get();

        // 5. Latest 5 finance transactions
        $recentTransactions = FinanceTransaction::with(['user', 'order'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(5)
            ->get();

        // 6. Trend data: Last 7 days daily breakdown
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $dailyTransactions = FinanceTransaction::where('tanggal', '>=', $sevenDaysAgo->toDateString())
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->tanggal)->format('Y-m-d'));

        $trendHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dayGroup = $dailyTransactions->get($date, collect());
            $pemasukan = (float) $dayGroup->where('tipe', 'pemasukan')->sum('nominal');
            $pengeluaran = (float) $dayGroup->where('tipe', 'pengeluaran')->sum('nominal');

            $trendHarian[] = [
                'tanggal' => $date,
                'hari' => Carbon::parse($date)->locale('id')->isoFormat('dddd'),
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'laba' => $pemasukan - $pengeluaran,
            ];
        }

        // 7. Top 5 selling products
        $topProducts = OrderItem::with('product')
            ->select('product_id', DB::raw('SUM(qty) as total_terjual'), DB::raw('SUM(subtotal) as total_omzet'))
            ->groupBy('product_id')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->nama,
                'total_terjual' => (int) $item->total_terjual,
                'total_omzet' => (float) $item->total_omzet,
            ]);

        return $this->success([
            'ringkasan' => [
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo' => $saldo,
                'omzet' => $totalPemasukan,
                'laba' => $saldo,
                'pemasukan_bulan_ini' => $pemasukanBulanIni,
                'pengeluaran_bulan_ini' => $pengeluaranBulanIni,
                'laba_bulan_ini' => $labaBulanIni,
                'total_orders' => $totalOrders,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
            ],
            'pesanan_terbaru' => OrderResource::collection($recentOrders),
            'transaksi_terbaru' => FinanceTransactionResource::collection($recentTransactions),
            'trend_harian' => $trendHarian,
            'produk_terlaris' => $topProducts,
        ], 'Data ringkasan dashboard berhasil dimuat.');
    }
}

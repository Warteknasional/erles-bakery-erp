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

        // Parameter filter:
        // - rentang: filter untuk grafik tren penjualan ('seminggu' atau 'bulan')
        // - rentang_produk: filter independen untuk produk terlaris ('seminggu', 'bulan', 'all')
        $rentang = $request->query('rentang', 'seminggu');
        $selectedTahun = (int) $request->query('tahun', Carbon::now()->year);
        $rentangProduk = $request->query('rentang_produk', 'seminggu');

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

        // 6. Trend data sesuai filter rentang waktu (menyediakan data qty pcs terjual dan nominal omzet)
        $trendHarian = [];

        if ($rentang === 'bulan' || $rentang === 'bulanan' || $rentang === 'tahun') {
            // Tampilkan langsung nama-nama bulan (Jan - Des) pada grafik tahun yang dipilih
            $startOfTargetYear = Carbon::createFromDate($selectedTahun, 1, 1)->startOfYear();
            $endOfTargetYear = Carbon::createFromDate($selectedTahun, 12, 31)->endOfYear();

            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            // Pemasukan & Pengeluaran per bulan
            $yearTransactions = FinanceTransaction::whereBetween('tanggal', [$startOfTargetYear->toDateString(), $endOfTargetYear->toDateString()])
                ->get()
                ->groupBy(fn ($item) => Carbon::parse($item->tanggal)->format('n'));

            // Volume produk/roti (qty) terjual per bulan
            $yearOrderItems = OrderItem::whereHas('order', function ($q) use ($startOfTargetYear, $endOfTargetYear) {
                $q->whereBetween('created_at', [$startOfTargetYear, $endOfTargetYear])
                  ->whereNotIn('status', ['cancelled', 'dibatalkan']);
            })->get()->groupBy(fn ($item) => Carbon::parse($item->created_at)->format('n'));

            for ($m = 1; $m <= 12; $m++) {
                $monthTx = $yearTransactions->get((string) $m, collect());
                $monthItems = $yearOrderItems->get((string) $m, collect());

                $pemasukan = (float) $monthTx->where('tipe', 'pemasukan')->sum('nominal');
                $pengeluaran = (float) $monthTx->where('tipe', 'pengeluaran')->sum('nominal');
                $qty = (int) $monthItems->sum('qty');

                $trendHarian[] = [
                    'tanggal' => $monthNames[$m],
                    'hari' => $monthNames[$m],
                    'pemasukan' => $pemasukan,
                    'qty' => $qty,
                    'pengeluaran' => $pengeluaran,
                    'laba' => $pemasukan - $pengeluaran,
                ];
            }
        } else {
            // Default 7 hari terakhir
            $sevenDaysAgo = Carbon::today()->subDays(6);
            $dailyTransactions = FinanceTransaction::where('tanggal', '>=', $sevenDaysAgo->toDateString())
                ->get()
                ->groupBy(fn ($item) => Carbon::parse($item->tanggal)->format('Y-m-d'));

            $dailyOrderItems = OrderItem::whereHas('order', function ($q) use ($sevenDaysAgo) {
                $q->whereDate('created_at', '>=', $sevenDaysAgo->toDateString())
                  ->whereNotIn('status', ['cancelled', 'dibatalkan']);
            })->get()->groupBy(fn ($item) => Carbon::parse($item->created_at)->format('Y-m-d'));

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->format('Y-m-d');
                $dayTx = $dailyTransactions->get($date, collect());
                $dayItems = $dailyOrderItems->get($date, collect());

                $pemasukan = (float) $dayTx->where('tipe', 'pemasukan')->sum('nominal');
                $pengeluaran = (float) $dayTx->where('tipe', 'pengeluaran')->sum('nominal');
                $qty = (int) $dayItems->sum('qty');

                $trendHarian[] = [
                    'tanggal' => $date,
                    'hari' => Carbon::parse($date)->locale('id')->isoFormat('dddd'),
                    'pemasukan' => $pemasukan,
                    'qty' => $qty,
                    'pengeluaran' => $pengeluaran,
                    'laba' => $pemasukan - $pengeluaran,
                ];
            }
        }

        // 7. Top 5 selling products sesuai query rentang_produk (independen dari rentang tren)
        $topProductQuery = OrderItem::with('product')
            ->select('product_id', DB::raw('SUM(qty) as total_terjual'), DB::raw('SUM(subtotal) as total_omzet'))
            ->whereHas('order', function ($q) {
                $q->whereNotIn('status', ['cancelled', 'dibatalkan']);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_terjual')
            ->limit(5);

        if ($rentangProduk === 'seminggu') {
            $sevenDaysAgo = Carbon::today()->subDays(6);
            $topProductQuery->whereHas('order', function ($q) use ($sevenDaysAgo) {
                $q->whereDate('created_at', '>=', $sevenDaysAgo->toDateString());
            });
        } elseif ($rentangProduk === 'bulan') {
            $topProductQuery->whereHas('order', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            });
        }

        $topProducts = $topProductQuery->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->nama,
                'total_terjual' => (int) $item->total_terjual,
                'total_omzet' => (float) $item->total_omzet,
            ]);

        // Fallback jika tidak ada penjualan di rentang tersebut agar tampilan tetap informatif
        if ($topProducts->isEmpty()) {
            $topProducts = OrderItem::with('product')
                ->select('product_id', DB::raw('SUM(qty) as total_terjual'), DB::raw('SUM(subtotal) as total_omzet'))
                ->whereHas('order', function ($q) {
                    $q->whereNotIn('status', ['cancelled', 'dibatalkan']);
                })
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
        }

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

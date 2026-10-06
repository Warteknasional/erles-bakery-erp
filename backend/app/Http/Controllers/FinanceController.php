<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinanceTransactionStoreRequest;
use App\Http\Requests\FinanceTransactionUpdateRequest;
use App\Http\Resources\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of finance transactions with filters and summary.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FinanceTransaction::with(['user', 'order'])->latest('tanggal')->latest('id');

        // Filter by tipe (pemasukan / pengeluaran)
        if ($request->filled('tipe')) {
            $query->where('tipe', strtolower($request->query('tipe')));
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        // Filter by date range (supports tanggal_mulai/akhir and start_date/end_date)
        $startDate = $request->query('tanggal_mulai') ?? $request->query('start_date');
        $endDate = $request->query('tanggal_akhir') ?? $request->query('end_date');
        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        // Search by catatan or kategori
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('catatan', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        // Calculate summary for the filtered query (cloned query without pagination)
        $summaryQuery = clone $query;
        $totalPemasukan = (float) (clone $summaryQuery)->where('tipe', 'pemasukan')->sum('nominal');
        $totalPengeluaran = (float) (clone $summaryQuery)->where('tipe', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($item) => new FinanceTransactionResource($item)),
            'Daftar transaksi keuangan berhasil diambil.',
            [
                'summary' => [
                    'total_pemasukan' => $totalPemasukan,
                    'total_pengeluaran' => $totalPengeluaran,
                    'saldo' => $saldo,
                    'omzet' => $totalPemasukan,
                    'laba' => $saldo,
                ],
            ]
        );
    }

    /**
     * Store a newly created finance transaction.
     */
    public function store(FinanceTransactionStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;

        $transaction = FinanceTransaction::create($data);
        $transaction->load(['user', 'order']);

        return $this->success(
            new FinanceTransactionResource($transaction),
            'Transaksi keuangan berhasil dicatat.',
            201
        );
    }

    /**
     * Display the specified finance transaction.
     */
    public function show(FinanceTransaction $finance): JsonResponse
    {
        $finance->load(['user', 'order']);

        return $this->success(
            new FinanceTransactionResource($finance),
            'Detail transaksi keuangan berhasil diambil.'
        );
    }

    /**
     * Update the specified finance transaction.
     */
    public function update(FinanceTransactionUpdateRequest $request, FinanceTransaction $finance): JsonResponse
    {
        $finance->update($request->validated());
        $finance->load(['user', 'order']);

        return $this->success(
            new FinanceTransactionResource($finance),
            'Transaksi keuangan berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified finance transaction.
     */
    public function destroy(FinanceTransaction $finance): JsonResponse
    {
        $finance->delete();

        return $this->success(null, 'Transaksi keuangan berhasil dihapus.');
    }

    /**
     * Get aggregate financial summary report with daily & monthly breakdowns.
     */
    public function summary(Request $request): JsonResponse
    {
        $query = FinanceTransaction::query();

        $startDate = $request->query('tanggal_mulai') ?? $request->query('start_date');
        $endDate = $request->query('tanggal_akhir') ?? $request->query('end_date');
        $month = $request->query('bulan') ?? $request->query('month');
        $year = $request->query('tahun') ?? $request->query('year');

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }
        if ($month) {
            $query->whereMonth('tanggal', $month);
        }
        if ($year) {
            $query->whereYear('tanggal', $year);
        }

        $allTransactions = $query->get();

        $totalPemasukan = (float) $allTransactions->where('tipe', 'pemasukan')->sum('nominal');
        $totalPengeluaran = (float) $allTransactions->where('tipe', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        // Breakdown by category
        $byCategory = $allTransactions->groupBy(['tipe', 'kategori'])->map(function ($itemsByTipe, $tipe) {
            return $itemsByTipe->map(function ($items, $kategori) use ($tipe) {
                return [
                    'tipe' => $tipe,
                    'kategori' => $kategori,
                    'total' => (float) $items->sum('nominal'),
                ];
            })->values();
        })->flatten(1)->values();

        // Breakdown harian (daily breakdown)
        $byDay = $allTransactions->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m-d');
        })->map(function ($items, $date) {
            $omzet = (float) $items->where('tipe', 'pemasukan')->sum('nominal');
            $pengeluaran = (float) $items->where('tipe', 'pengeluaran')->sum('nominal');
            return [
                'tanggal' => $date,
                'omzet' => $omzet,
                'pengeluaran' => $pengeluaran,
                'laba' => $omzet - $pengeluaran,
            ];
        })->sortBy('tanggal')->values();

        // Breakdown bulanan (monthly breakdown)
        $byMonth = $allTransactions->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        })->map(function ($items, $yearMonth) {
            $omzet = (float) $items->where('tipe', 'pemasukan')->sum('nominal');
            $pengeluaran = (float) $items->where('tipe', 'pengeluaran')->sum('nominal');
            return [
                'bulan' => $yearMonth,
                'omzet' => $omzet,
                'pengeluaran' => $pengeluaran,
                'laba' => $omzet - $pengeluaran,
            ];
        })->sortBy('bulan')->values();

        return $this->success([
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo' => $saldo,
            'omzet' => $totalPemasukan,
            'laba' => $saldo,
            'breakdown_harian' => $byDay,
            'breakdown_bulanan' => $byMonth,
            'breakdown_kategori' => $byCategory,
        ], 'Ringkasan keuangan berhasil diambil.');
    }
}

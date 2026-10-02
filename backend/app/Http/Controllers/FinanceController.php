<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinanceTransactionStoreRequest;
use App\Http\Requests\FinanceTransactionUpdateRequest;
use App\Http\Resources\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of finance transactions with filters and summary.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FinanceTransaction::with(['user', 'order'])->latest('tanggal')->latest('id');

        // Filter by tipe
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->query('tipe'));
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        // Filter by date range
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->query('tanggal_mulai'));
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal', '<=', $request->query('tanggal_akhir'));
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
     * Get aggregate financial summary report.
     */
    public function summary(Request $request): JsonResponse
    {
        $query = FinanceTransaction::query();

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->query('bulan'));
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun', date('Y')));
        }

        $totalPemasukan = (float) (clone $query)->where('tipe', 'pemasukan')->sum('nominal');
        $totalPengeluaran = (float) (clone $query)->where('tipe', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        // Breakdown by category
        $byCategory = FinanceTransaction::query()
            ->selectRaw('tipe, kategori, SUM(nominal) as total')
            ->when($request->filled('bulan'), fn ($q) => $q->whereMonth('tanggal', $request->query('bulan')))
            ->when($request->filled('tahun'), fn ($q) => $q->whereYear('tanggal', $request->query('tahun', date('Y'))))
            ->groupBy('tipe', 'kategori')
            ->get();

        return $this->success([
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo' => $saldo,
            'breakdown_kategori' => $byCategory,
        ], 'Ringkasan keuangan berhasil diambil.');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ═══════════════════════════════════════
        // 1. Users (Admin and Staff)
        // ═══════════════════════════════════════
        $admin = User::firstOrCreate(
            ['email' => 'admin@erlesbakery.com'],
            [
                'name' => 'Admin Erles',
                'phone' => '081234567890',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@erlesbakery.com'],
            [
                'name' => 'Staff Erles',
                'phone' => '081234567891',
                'role' => 'karyawan',
                'password' => bcrypt('password'),
            ]
        );

        // ═══════════════════════════════════════
        // 2. Categories
        // ═══════════════════════════════════════
        $categories = [
            'Roti' => Category::firstOrCreate(['slug' => 'roti'], ['name' => 'Roti', 'description' => 'Aneka roti tawar dan manis']),
            'Kue' => Category::firstOrCreate(['slug' => 'kue'], ['name' => 'Kue', 'description' => 'Kue basah, bolu, dan brownies']),
            'Kue Kering' => Category::firstOrCreate(['slug' => 'kue-kering'], ['name' => 'Kue Kering', 'description' => 'Kue toples khas hari raya']),
            'Hampers' => Category::firstOrCreate(['slug' => 'hampers'], ['name' => 'Hampers', 'description' => 'Paket bingkisan spesial']),
        ];

        // ═══════════════════════════════════════
        // 3. Products (15 items)
        // ═══════════════════════════════════════
        $productDefs = collect([
            // Roti
            ['nama' => 'Roti Tawar Gandum',      'harga' => 25000, 'kategori' => 'Roti',    'stok' => 50, 'deskripsi' => 'Roti tawar gandum utuh, lembut dan sehat.'],
            ['nama' => 'Roti Sobek Cokelat',      'harga' => 28000, 'kategori' => 'Roti',    'stok' => 40, 'deskripsi' => 'Roti sobek isi cokelat leleh, favorit anak-anak.'],
            ['nama' => 'Roti Sisir Mentega',       'harga' => 22000, 'kategori' => 'Roti',    'stok' => 35, 'deskripsi' => 'Roti sisir klasik dengan olesan mentega premium.'],
            ['nama' => 'Croissant Butter',         'harga' => 18000, 'kategori' => 'Roti',    'stok' => 30, 'deskripsi' => 'Croissant renyah berlapis butter Prancis.'],
            // Kue
            ['nama' => 'Brownies Panggang',        'harga' => 45000, 'kategori' => 'Kue',     'stok' => 25, 'deskripsi' => 'Brownies premium dengan dark chocolate.'],
            ['nama' => 'Bolu Pandan Pake Nasi',    'harga' => 55000, 'kategori' => 'Kue',     'stok' => 20, 'deskripsi' => 'Bolu pandan lembut dengan aroma pandan alami.'],
            ['nama' => 'Lapis Legit Original',     'harga' => 120000,'kategori' => 'Kue',     'stok' => 10, 'deskripsi' => 'Lapis legit tradisional, 18 lapis sempurna.'],
            ['nama' => 'Cheesecake Blueberry',     'harga' => 85000, 'kategori' => 'Kue',     'stok' => 15, 'deskripsi' => 'Cheesecake lembut dengan topping blueberry segar.'],
            // Kue Kering
            ['nama' => 'Nastar Keju',              'harga' => 75000, 'kategori' => 'Kue Kering', 'stok' => 30, 'deskripsi' => 'Nastar isi selai nanas dengan taburan keju.'],
            ['nama' => 'Kastengel Premium',        'harga' => 80000, 'kategori' => 'Kue Kering', 'stok' => 25, 'deskripsi' => 'Kastengel renyah dengan keju Edam asli.'],
            ['nama' => 'Putri Salju Almond',       'harga' => 70000, 'kategori' => 'Kue Kering', 'stok' => 20, 'deskripsi' => 'Putri salju lumer di mulut dengan almond pilihan.'],
            // Hampers
            ['nama' => 'Hampers Lebaran Classic',  'harga' => 250000,'kategori' => 'Hampers', 'stok' => 15, 'deskripsi' => 'Paket 3 toples kue kering (Nastar, Kastengel, Putri Salju) dalam box eksklusif.'],
            ['nama' => 'Hampers Premium Gold',     'harga' => 450000,'kategori' => 'Hampers', 'stok' => 10, 'deskripsi' => 'Paket premium: Lapis Legit, 2 toples kue kering, Brownies, dalam box gold.'],
            ['nama' => 'Hampers Natal Joy',        'harga' => 350000,'kategori' => 'Hampers', 'stok' => 12, 'deskripsi' => 'Paket Natal: Cheesecake, Brownies, 2 toples kue kering, dengan dekorasi Natal.'],
            ['nama' => 'Hampers Mini Gift',        'harga' => 150000,'kategori' => 'Hampers', 'stok' => 20, 'deskripsi' => 'Paket mini berisi 1 toples kue kering dan 1 Brownies, cocok untuk oleh-oleh.'],
        ]);

        $products = $productDefs->map(function ($data) use ($categories) {
            $slug = Str::slug($data['nama']);
            $data['slug'] = $slug;
            $data['is_active'] = true;
            $data['category_id'] = $categories[$data['kategori']]?->id;
            return Product::firstOrCreate(['slug' => $slug], $data);
        });

        // ═══════════════════════════════════════
        // 4. Orders with items (5 orders)
        // ═══════════════════════════════════════
        $orderData = [
            [
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081298765432',
                'alamat' => 'Jl. Merdeka No. 10, Jakarta Pusat',
                'tanggal_ambil' => now()->addDays(3)->toDateString(),
                'status' => 'pending',
                'catatan' => 'Tolong dikemas rapi, untuk acara kantor.',
                'items' => [
                    ['product_index' => 4, 'qty' => 2],  // Brownies x2
                    ['product_index' => 0, 'qty' => 3],  // Roti Tawar x3
                ],
            ],
            [
                'customer_name' => 'Siti Aminah',
                'customer_phone' => '085712345678',
                'alamat' => 'Jl. Pahlawan No. 25, Bandung',
                'tanggal_ambil' => now()->addDays(1)->toDateString(),
                'status' => 'processing',
                'catatan' => null,
                'items' => [
                    ['product_index' => 11, 'qty' => 1], // Hampers Lebaran
                    ['product_index' => 8, 'qty' => 2],  // Nastar x2
                ],
            ],
            [
                'customer_name' => 'Andi Prasetyo',
                'customer_phone' => '087654321098',
                'alamat' => null, // Ambil di toko
                'tanggal_ambil' => now()->subDays(2)->toDateString(),
                'status' => 'completed',
                'catatan' => 'Ambil di toko jam 10 pagi.',
                'items' => [
                    ['product_index' => 12, 'qty' => 1], // Hampers Premium Gold
                ],
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'customer_phone' => '081345678901',
                'alamat' => 'Jl. Sudirman Kav. 5, Jakarta Selatan',
                'tanggal_ambil' => now()->addDays(7)->toDateString(),
                'status' => 'pending',
                'catatan' => 'Untuk acara ulang tahun, tolong tambahin lilin.',
                'items' => [
                    ['product_index' => 7, 'qty' => 1],  // Cheesecake
                    ['product_index' => 5, 'qty' => 1],  // Bolu Pandan
                    ['product_index' => 3, 'qty' => 5],  // Croissant x5
                ],
            ],
            [
                'customer_name' => 'Rizky Hidayat',
                'customer_phone' => '089876543210',
                'alamat' => 'Jl. Gatot Subroto No. 88, Semarang',
                'tanggal_ambil' => now()->subDays(5)->toDateString(),
                'status' => 'cancelled',
                'catatan' => 'Batal karena perubahan jadwal acara.',
                'items' => [
                    ['product_index' => 13, 'qty' => 3], // Hampers Natal x3
                ],
            ],
        ];

        if (Order::count() === 0) {
            foreach ($orderData as $data) {
                $order = Order::create([
                    'kode_pesanan' => Order::generateKodePesanan(),
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'alamat' => $data['alamat'],
                    'catatan' => $data['catatan'],
                    'tanggal_ambil' => $data['tanggal_ambil'],
                    'status' => $data['status'],
                    'payment_status' => 'unpaid',
                    'paid_amount' => 0,
                ]);

                foreach ($data['items'] as $item) {
                    $product = $products[$item['product_index']];
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'unit_price' => $product->harga,
                        'subtotal' => $product->harga * $item['qty'],
                    ]);
                }

                $order->recalculateTotal();
            }
        }

        // ═══════════════════════════════════════
        // 5. Finance Transactions & Payment
        // ═══════════════════════════════════════
        // Pemasukan dari pesanan selesai
        $completedOrder = Order::whereIn('status', ['completed', 'selesai'])->first();
        if ($completedOrder && FinanceTransaction::where('order_id', $completedOrder->id)->count() === 0) {
            $payment = Payment::create([
                'order_id' => $completedOrder->id,
                'user_id' => $admin->id,
                'nominal' => $completedOrder->total_price,
                'metode' => 'transfer',
                'tipe' => 'lunas',
                'tanggal' => $completedOrder->updated_at->toDateString(),
                'catatan' => "Pembayaran pesanan {$completedOrder->kode_pesanan}",
            ]);

            $completedOrder->recalculatePaymentStatus();

            FinanceTransaction::create([
                'tipe' => 'pemasukan',
                'nominal' => $completedOrder->total_price,
                'kategori' => 'Penjualan',
                'catatan' => "Pembayaran pesanan {$completedOrder->kode_pesanan}",
                'tanggal' => $completedOrder->updated_at->toDateString(),
                'user_id' => $admin->id,
                'order_id' => $completedOrder->id,
                'payment_id' => $payment->id,
            ]);
        }

        // Pengeluaran operasional (5 transaksi)
        if (FinanceTransaction::where('tipe', 'pengeluaran')->count() === 0) {
            $expenses = [
                ['nominal' => 500000,  'kategori' => 'Bahan Baku',  'catatan' => 'Pembelian tepung terigu 25kg',              'tanggal' => now()->subDays(10)->toDateString()],
                ['nominal' => 350000,  'kategori' => 'Bahan Baku',  'catatan' => 'Pembelian mentega & margarin',              'tanggal' => now()->subDays(8)->toDateString()],
                ['nominal' => 150000,  'kategori' => 'Packaging',   'catatan' => 'Box hampers dan pita',                      'tanggal' => now()->subDays(7)->toDateString()],
                ['nominal' => 200000,  'kategori' => 'Operasional', 'catatan' => 'Biaya listrik toko bulan ini',              'tanggal' => now()->subDays(5)->toDateString()],
                ['nominal' => 1200000, 'kategori' => 'Gaji',        'catatan' => 'Gaji karyawan part-time (2 orang)',         'tanggal' => now()->subDays(1)->toDateString()],
            ];

            foreach ($expenses as $expense) {
                FinanceTransaction::create([
                    'tipe' => 'pengeluaran',
                    'nominal' => $expense['nominal'],
                    'kategori' => $expense['kategori'],
                    'catatan' => $expense['catatan'],
                    'tanggal' => $expense['tanggal'],
                    'user_id' => $admin->id,
                ]);
            }
        }

        // Pemasukan tambahan (non-order)
        if (FinanceTransaction::where('kategori', 'Penjualan Langsung')->count() === 0) {
            FinanceTransaction::create([
                'tipe' => 'pemasukan',
                'nominal' => 180000,
                'kategori' => 'Penjualan Langsung',
                'catatan' => 'Penjualan walk-in roti & kue harian',
                'tanggal' => now()->subDays(3)->toDateString(),
                'user_id' => $admin->id,
            ]);
        }
    }
}

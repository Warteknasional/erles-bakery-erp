<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Ingredient;
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
        // 1. User: Bu Erni Lestari (Owner & Baker Tunggal)
        // ═══════════════════════════════════════
        $admin = User::firstOrCreate(
            ['email' => 'admin@erlesbakery.com'],
            [
                'name' => 'Bu Erni Lestari',
                'phone' => '082145346270',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        // Pastikan nama user terupdate jika sebelumnya seeded nama generik
        $admin->update([
            'name' => 'Bu Erni Lestari',
            'phone' => '082145346270',
            'role' => 'admin',
        ]);

        // ═══════════════════════════════════════
        // 2. Categories
        // ═══════════════════════════════════════
        $categories = [
            'Roti Manis' => Category::firstOrCreate(['slug' => 'roti-manis'], ['name' => 'Roti Manis', 'description' => 'Aneka varian roti manis lembut hangat khas Erles Bakery']),
            'Donat' => Category::firstOrCreate(['slug' => 'donat'], ['name' => 'Donat', 'description' => 'Donat empuk kenyal dari resep rumahan dengan aneka taburan dan topping']),
        ];

        // ═══════════════════════════════════════
        // 3. Products (Varian Asli Erles Bakery)
        // ═══════════════════════════════════════
        $productDefs = collect([
            [
                'nama' => 'Roti Abon',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 50,
                'deskripsi' => 'Roti manis empuk bertabur abon sapi gurih melimpah dengan olesan saus manis khas bakery.',
            ],
            [
                'nama' => 'Roti Sosis',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 50,
                'deskripsi' => 'Roti lembut dengan isian sosis sapi pilihan berpadu saus gurih favorit sarapan keluarga.',
            ],
            [
                'nama' => 'Roti Keju',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 40,
                'deskripsi' => 'Roti manis lembut berisikan keju gurih dengan parutan keju cheddar panggang di atasnya.',
            ],
            [
                'nama' => 'Roti Pizza',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 40,
                'deskripsi' => 'Roti mini pizza dengan topping potongan sosis, saus tomat rempah, dan taburan keju leleh.',
            ],
            [
                'nama' => 'Roti Sakura',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 35,
                'deskripsi' => 'Roti manis klasik dibentuk kelopak bunga sakura yang cantik dan harum mentega segar.',
            ],
            [
                'nama' => 'Roti Kepang',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 35,
                'deskripsi' => 'Roti kepang tradisional dengan olesan mentega manis dan taburan gula pasir renyah.',
            ],
            [
                'nama' => 'Roti Pisang Coklat',
                'harga' => 5000,
                'kategori' => 'Roti Manis',
                'stok' => 50,
                'deskripsi' => 'Kombinasi klasik pisang raja manis alami dan lelehan cokelat legit berbalut adonan roti empuk.',
            ],
            [
                'nama' => 'Roti Pisang Coklat Keju',
                'harga' => 6000,
                'kategori' => 'Roti Manis',
                'stok' => 45,
                'deskripsi' => 'Perpaduan sempurna pisang manis, lelehan cokelat lumer, dan keju cheddar gurih dalam satu roti.',
            ],
            [
                'nama' => 'Roti Abon 1 Loyang (40 x 40)',
                'harga' => 60000,
                'kategori' => 'Roti Manis',
                'stok' => 15,
                'deskripsi' => 'Porsi besar 1 loyang ukuran 40 × 40 cm penuh abon sapi tebal, sangat cocok untuk hantaran dan acara keluarga.',
            ],
            [
                'nama' => 'Donuts Biasa',
                'harga' => 2500,
                'kategori' => 'Donat',
                'stok' => 60,
                'deskripsi' => 'Donat kentang tradisional super empuk dan kenyal, ditaburi gula halus dingin yang manis pas.',
            ],
            [
                'nama' => 'Donuts Meses Coklat',
                'harga' => 5000,
                'kategori' => 'Donat',
                'stok' => 50,
                'deskripsi' => 'Donat empuk dengan olesan krim manis lembut dan taburan meses cokelat tebal yang melimpah.',
            ],
            [
                'nama' => 'Donuts Premium',
                'harga' => 7000,
                'kategori' => 'Donat',
                'stok' => 40,
                'deskripsi' => 'Donat kentang premium bertekstur lembut istimewa dengan pilihan topping glaze artisan cokelat dan keju.',
            ],
        ]);

        $products = $productDefs->map(function ($data) use ($categories) {
            $slug = Str::slug($data['nama']);
            $data['slug'] = $slug;
            $data['is_active'] = true;
            $data['category_id'] = $categories[$data['kategori']]?->id;
            return Product::updateOrCreate(['slug' => $slug], $data);
        });

        // ═══════════════════════════════════════
        // 4. Orders with items (Alamat Asli Malang Raya)
        // ═══════════════════════════════════════
        $orderData = [
            [
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'alamat' => 'Jl. Soekarno Hatta No. 12, Lowokwaru, Kota Malang',
                'tanggal_ambil' => now()->addDays(1)->toDateString(),
                'status' => 'pending',
                'catatan' => 'Tolong dikemas rapi untuk sarapan keluarga',
                'items' => [
                    ['product_index' => 0, 'qty' => 4], // Roti Abon x4
                    ['product_index' => 9, 'qty' => 10], // Donuts Biasa x10
                ],
            ],
            [
                'customer_name' => 'Siti Aminah',
                'customer_phone' => '081298765432',
                'alamat' => 'Jl. Ijen No. 25, Klojen, Kota Malang',
                'tanggal_ambil' => now()->toDateString(),
                'status' => 'processing',
                'catatan' => 'Kirim via kurir instan Malang',
                'items' => [
                    ['product_index' => 1, 'qty' => 5], // Roti Sosis x5
                    ['product_index' => 10, 'qty' => 5], // Donuts Meses Coklat x5
                ],
            ],
            [
                'customer_name' => 'Ahmad Fauzi',
                'customer_phone' => '085612345678',
                'alamat' => 'Jl. Bendungan Sigura-gura No. 18, Sukun, Malang',
                'tanggal_ambil' => now()->subDays(1)->toDateString(),
                'status' => 'completed',
                'catatan' => 'Ambil langsung di dapur jam 07.00 pagi',
                'items' => [
                    ['product_index' => 7, 'qty' => 6], // Roti Pisang Coklat Keju x6
                    ['product_index' => 9, 'qty' => 10], // Donuts Biasa x10
                ],
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'customer_phone' => '087812345678',
                'alamat' => 'Jl. Kalpataru No. 45, Lowokwaru, Malang',
                'tanggal_ambil' => now()->addDays(2)->toDateString(),
                'status' => 'pending',
                'catatan' => 'Untuk konsumsi acara rapat keluarga',
                'items' => [
                    ['product_index' => 8, 'qty' => 1], // Roti Abon 1 Loyang (40 x 40)
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
        // 5. Finance Transactions (Operasional Riil Dapur Rumahan)
        // ═══════════════════════════════════════
        $completedOrder = Order::whereIn('status', ['completed', 'selesai'])->first();
        if ($completedOrder) {
            $payment = Payment::firstOrCreate(
                ['order_id' => $completedOrder->id],
                [
                    'user_id' => $admin->id,
                    'nominal' => $completedOrder->total_price,
                    'metode' => 'transfer',
                    'tipe' => 'lunas',
                    'tanggal' => $completedOrder->updated_at->toDateString(),
                    'catatan' => "Pembayaran pesanan {$completedOrder->kode_pesanan}",
                ]
            );

            $completedOrder->recalculatePaymentStatus();

            $existingTx = FinanceTransaction::where('order_id', $completedOrder->id)->first();
            if (!$existingTx) {
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
        }

        if (FinanceTransaction::where('tipe', 'pengeluaran')->count() === 0) {
            $expenses = [
                ['nominal' => 380000, 'kategori' => 'Bahan Baku', 'catatan' => 'Pembelian tepung Cakra Kembar Emas 25kg', 'tanggal' => now()->subDays(6)->toDateString()],
                ['nominal' => 280000, 'kategori' => 'Bahan Baku', 'catatan' => 'Kentang kukus segar pasar lokal Malang 20kg', 'tanggal' => now()->subDays(4)->toDateString()],
                ['nominal' => 450000, 'kategori' => 'Bahan Baku', 'catatan' => 'Pembelian French Butter Unsalted 5kg', 'tanggal' => now()->subDays(3)->toDateString()],
                ['nominal' => 120000, 'kategori' => 'Packaging', 'catatan' => 'Kotak kemasan food-grade dan kantong kraft', 'tanggal' => now()->subDays(2)->toDateString()],
                ['nominal' => 180000, 'kategori' => 'Operasional', 'catatan' => 'Isi ulang gas elpiji oven panggangan subuh', 'tanggal' => now()->subDays(1)->toDateString()],
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

        // ═══════════════════════════════════════
        // 6. Ingredients (Bahan Baku Dapur Erles)
        // ═══════════════════════════════════════
        if (Ingredient::count() === 0) {
            $ingredients = [
                ['kode' => 'BHN-001', 'nama' => 'Tepung Terigu Protein Tinggi (Cakra Kembar Emas)', 'kategori' => 'Tepung', 'stok' => 35, 'satuan' => 'kg', 'minimum_stok' => 20],
                ['kode' => 'BHN-002', 'nama' => 'Kentang Segar Pilihan Malang', 'kategori' => 'Bahan Segar', 'stok' => 25, 'satuan' => 'kg', 'minimum_stok' => 10],
                ['kode' => 'BHN-003', 'nama' => 'French Pure Butter Unsalted', 'kategori' => 'Lemak & Butter', 'stok' => 8, 'satuan' => 'kg', 'minimum_stok' => 5],
                ['kode' => 'BHN-004', 'nama' => 'Kultur Ragi Alami Sourdough Starter', 'kategori' => 'Pengembang', 'stok' => 5, 'satuan' => 'liter', 'minimum_stok' => 2],
                ['kode' => 'BHN-005', 'nama' => 'Dark Chocolate Couverture 70%', 'kategori' => 'Coklat & Topping', 'stok' => 12, 'satuan' => 'kg', 'minimum_stok' => 5],
                ['kode' => 'BHN-006', 'nama' => 'Almond Slice Panggang Gurih', 'kategori' => 'Coklat & Topping', 'stok' => 6, 'satuan' => 'kg', 'minimum_stok' => 3],
                ['kode' => 'BHN-007', 'nama' => 'Gula Donat Dingin Khusus', 'kategori' => 'Pemanis', 'stok' => 18, 'satuan' => 'kg', 'minimum_stok' => 8],
                ['kode' => 'BHN-008', 'nama' => 'Zaitun Hitam (Black Olives) & Rosemary Segar', 'kategori' => 'Bahan Segar', 'stok' => 4, 'satuan' => 'kg', 'minimum_stok' => 2],
            ];

            foreach ($ingredients as $ing) {
                Ingredient::create($ing);
            }
        }
    }
}

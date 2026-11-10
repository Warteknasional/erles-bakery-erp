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
            'Roti Sourdough & Artisan' => Category::firstOrCreate(['slug' => 'roti-artisan'], ['name' => 'Roti Sourdough & Artisan', 'description' => 'Roti fermentasi lambat 24 jam dengan ragi alami']),
            'Donat Kentang' => Category::firstOrCreate(['slug' => 'donat-kentang'], ['name' => 'Donat Kentang', 'description' => 'Donat empuk kenyal dari kentang kukus lokal Malang']),
            'Pastry & Viennoiserie' => Category::firstOrCreate(['slug' => 'pastry'], ['name' => 'Pastry & Viennoiserie', 'description' => 'Pastry renyah berlapis mentega murni']),
            'Hampers & Gift' => Category::firstOrCreate(['slug' => 'hampers'], ['name' => 'Hampers & Gift', 'description' => 'Kotak hantaran kue kering wisman spesial']),
        ];

        // ═══════════════════════════════════════
        // 3. Products (6 Produk Asli Erles Bakery)
        // ═══════════════════════════════════════
        $productDefs = collect([
            [
                'nama' => 'Sourdough Country Boule',
                'harga' => 45000,
                'kategori' => 'Roti Sourdough & Artisan',
                'stok' => 30,
                'deskripsi' => 'Kerak keemasan renyah berongga lembut hasil fermentasi alami 24 jam dengan ragi starter khas dapur Bu Erni.',
            ],
            [
                'nama' => 'Olive & Rosemary Ciabatta',
                'harga' => 32000,
                'kategori' => 'Roti Sourdough & Artisan',
                'stok' => 25,
                'deskripsi' => 'Roti klasik Italia dengan kelembapan tinggi, irisan buah zaitun hitam, dan aroma rosemary segar.',
            ],
            [
                'nama' => 'French Baguette Tradisi',
                'harga' => 28000,
                'kategori' => 'Roti Sourdough & Artisan',
                'stok' => 20,
                'deskripsi' => 'Baguette renyah autentik dengan remah airy dan kerak garing, dipanggang sempurna setiap subuh.',
            ],
            [
                'nama' => 'Donat Kentang Gula Halus',
                'harga' => 8000,
                'kategori' => 'Donat Kentang',
                'stok' => 50,
                'deskripsi' => 'Donat kentang legendaris dari kentang kukus lokal Malang, tekstur lembut empuk bertabur gula dingin.',
            ],
            [
                'nama' => 'Donat Coklat Almond Panggang',
                'harga' => 12000,
                'kategori' => 'Donat Kentang',
                'stok' => 40,
                'deskripsi' => 'Balutan dark chocolate leleh premium bertabur irisan kacang almond iris sangrai renyah gurih.',
            ],
            [
                'nama' => 'Hampers Lebaran Deluxe',
                'harga' => 350000,
                'kategori' => 'Hampers & Gift',
                'stok' => 15,
                'deskripsi' => 'Kotak eksklusif velvet gold berisi 3 toples kue kering butter Wisman legendaris & kartu ucapan kustom.',
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
                    ['product_index' => 0, 'qty' => 1], // Sourdough
                    ['product_index' => 3, 'qty' => 5], // Donat Kentang x5
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
                    ['product_index' => 1, 'qty' => 1], // Ciabatta
                    ['product_index' => 4, 'qty' => 4], // Donat Coklat x4
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
                    ['product_index' => 2, 'qty' => 2], // Baguette x2
                    ['product_index' => 3, 'qty' => 6], // Donat Kentang x6
                ],
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'customer_phone' => '087812345678',
                'alamat' => 'Jl. Kalpataru No. 45, Lowokwaru, Malang',
                'tanggal_ambil' => now()->addDays(2)->toDateString(),
                'status' => 'pending',
                'catatan' => 'Sertakan kartu ucapan kustom',
                'items' => [
                    ['product_index' => 5, 'qty' => 1], // Hampers Deluxe
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

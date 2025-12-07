<?php
$recentTransactions = [
    [
        'date' => '2025-11-30',
        'material' => 'Tepung Terigu Premium',
        'qty' => '50 Kg',
        'supplier' => 'PT Bogasari',
        'total' => 'Rp 600.000'
    ],
    [
        'date' => '2025-11-29',
        'material' => 'Gula Pasir',
        'qty' => '100 Kg',
        'supplier' => 'CV Sumber Manis',
        'total' => 'Rp 1.500.000'
    ],
];
?>

<section class="p-6 md:p-10 space-y-8">

    <!-- HEADER -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Stok Masuk</h1>
        <p class="text-sm text-slate-500">Catat pembelian dan penerimaan bahan baku</p>
    </div>

    <!-- FORM STOK MASUK -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6 space-y-6 max-w-3xl">

        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-emerald-500 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Form Stok Masuk</h2>
                <p class="text-xs text-slate-500">Isi detail transaksi pembelian bahan baku</p>
            </div>
        </div>

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- TANGGAL -->
            <div>
                <label class="text-sm text-slate-600">Tanggal</label>
                <input type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- NAMA BAHAN -->
            <div>
                <label class="text-sm text-slate-600">Nama Bahan</label>
                <input type="text" placeholder="Contoh: Tepung Terigu" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- JUMLAH -->
            <div>
                <label class="text-sm text-slate-600">Jumlah Masuk</label>
                <input type="number" placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- SATUAN -->
            <div>
                <label class="text-sm text-slate-600">Satuan</label>
                <input type="text" placeholder="Kg / Liter / Dus" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- SUPPLIER -->
            <div>
                <label class="text-sm text-slate-600">Supplier</label>
                <input type="text" placeholder="Nama Supplier" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- HARGA -->
            <div>
                <label class="text-sm text-slate-600">Harga per Satuan (Rp)</label>
                <input type="number" placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>

            <!-- CATATAN -->
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Catatan / Keterangan</label>
                <textarea rows="3" placeholder="Catatan tambahan (opsional)" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400"></textarea>
            </div>

            <!-- UPLOAD -->
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Upload Bukti (Opsional)</label>
                <div class="mt-1 border-2 border-dashed border-slate-200 rounded-xl p-6 text-center text-sm text-slate-400">
                    Klik untuk upload atau drag & drop <br> PNG, JPG, PDF (Max 5MB)
                </div>
            </div>

            <!-- BUTTON -->
            <div class="md:col-span-2">
                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 rounded-xl shadow flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Transaksi
                </button>
            </div>

        </form>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6">

        <h2 class="text-sm font-semibold text-slate-900 mb-4">Transaksi Terbaru</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-slate-500 border-b">
                    <tr>
                        <th class="py-3">Tanggal</th>
                        <th>Bahan</th>
                        <th>Jumlah</th>
                        <th>Supplier</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    <?php foreach ($recentTransactions as $trx): ?>
                        <tr class="border-b last:border-b-0">
                            <td class="py-3"><?= e($trx['date']) ?></td>
                            <td><?= e($trx['material']) ?></td>
                            <td><?= e($trx['qty']) ?></td>
                            <td><?= e($trx['supplier']) ?></td>
                            <td class="text-emerald-600 font-semibold"><?= e($trx['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</section>

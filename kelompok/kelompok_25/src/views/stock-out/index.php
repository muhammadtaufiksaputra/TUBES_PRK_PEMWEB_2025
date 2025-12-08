<?php
$recentTransactions = [
    [
        'date' => '2025-11-30',
        'material' => 'Tepung Terigu Premium',
        'qty' => '15 Kg',
        'purpose' => 'Produksi',
        'note' => 'Produksi toast harian'
    ],
    [
        'date' => '2025-11-29',
        'material' => 'Gula Pasir',
        'qty' => '10 Kg',
        'purpose' => 'Lain-lain',
        'note' => 'Tes menu baru'
    ],
];
?>

<section class="p-6 md:p-10 space-y-8">

    <!-- HEADER -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Stok Keluar</h1>
        <p class="text-sm text-slate-500">Catat penggunaan bahan baku</p>
    </div>

    <!-- FORM STOK KELUAR -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6 space-y-6 max-w-3xl">

        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-orange-500 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m7 7H5" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Form Stok Keluar</h2>
                <p class="text-xs text-slate-500">Isi detail penggunaan bahan baku</p>
            </div>
        </div>

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- TANGGAL -->
            <div>
                <label class="text-sm text-slate-600">Tanggal</label>
                <input type="date"
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm 
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>

            <!-- NAMA BAHAN -->
            <div>
                <label class="text-sm text-slate-600">Nama Bahan</label>
                <input type="text" placeholder="Contoh: Tepung Terigu"
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm 
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>

            <!-- JUMLAH KELUAR -->
            <div>
                <label class="text-sm text-slate-600">Jumlah Keluar</label>
                <input type="number" placeholder="0"
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm 
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>

            <!-- SATUAN -->
            <div>
                <label class="text-sm text-slate-600">Satuan</label>
                <input type="text" placeholder="Kg / Liter / Dus"
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm 
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>

            <!-- KEPERLUAN -->
            <div>
                <label class="text-sm text-slate-600">Keperluan</label>
                <select
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                    <option>Produksi</option>
                    <option>Lain-lain</option>
                </select>
            </div>

            <!-- CATATAN -->
            <div>
                <label class="text-sm text-slate-600">Catatan</label>
                <input type="text" placeholder="Keterangan tambahan"
                    class="mt-1 w-full rounded-xl border border-orange-200 px-4 py-3 shadow-sm 
                    focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>

            <!-- BUTTON -->
            <div class="md:col-span-2">
                <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl shadow 
                    flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Transaksi Keluar
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
                        <th>Keperluan</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    <?php foreach ($recentTransactions as $trx): ?>
                        <tr class="border-b last:border-b-0">
                            <td class="py-3"><?= e($trx['date']) ?></td>
                            <td><?= e($trx['material']) ?></td>
                            <td class="text-orange-600 font-semibold"><?= e($trx['qty']) ?></td>
                            <td><?= e($trx['purpose']) ?></td>
                            <td><?= e($trx['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</section>
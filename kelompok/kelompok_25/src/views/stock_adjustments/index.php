<style>
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: none; }
    .alert-success { background: #d1fae5; color: #065f46; }
    .alert-error { background: #fee2e2; color: #991b1b; }
</style>
    <section class="p-6 md:p-10 space-y-8">
        <div>
            <p class="text-sm text-slate-500 uppercase tracking-[0.3em]">Transaksi</p>
            <h1 class="text-2xl font-semibold text-slate-800 mt-1">Penyesuaian Stok</h1>
            <p class="text-sm text-slate-500">Koreksi stok jika terdapat selisih antara sistem dan fisik</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-6">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 text-xl">⚙️</span>
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Form Penyesuaian Stok</h3>
                    <p class="text-sm text-slate-500">Sesuaikan stok berdasarkan perhitungan fisik</p>
                </div>
            </div>

            <div id="alert" class="alert"></div>

            <form id="adjustmentForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">📅 Tanggal</label>
                        <input type="date" name="adjustment_date" id="adjustment_date" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">📦 Nama Bahan</label>
                        <select name="material_id" id="material_id" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Bahan</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?= $material['id'] ?>" data-stock="<?= $material['current_stock'] ?>" data-unit="<?= $material['unit'] ?>">
                                    <?= htmlspecialchars($material['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Stok di Sistem</label>
                        <input type="number" id="old_stock" step="0.01" readonly class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Stok Fisik (Hasil Hitung)</label>
                        <input type="number" name="new_stock" id="new_stock" step="0.01" placeholder="0" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Satuan</label>
                        <input type="text" id="unit" readonly class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Penyesuaian</label>
                        <input type="text" name="reason" id="reason" placeholder="Contoh: Barang rusak/expired" class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">📝 Keterangan</label>
                    <textarea name="note" id="note" placeholder="Catatan tambahan (opsional)" rows="3" class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    ⚙️ Simpan Penyesuaian
                </button>
            </form>
        </div>

        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-800">Riwayat Penyesuaian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Tanggal</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Bahan</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Stok Sistem</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Stok Fisik</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Selisih</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($adjustments)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada riwayat penyesuaian</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($adjustments as $adj): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm text-slate-800"><?= date('Y-m-d', strtotime($adj['adjustment_date'])) ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-800"><?= htmlspecialchars($adj['material_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-800"><?= number_format($adj['old_stock'], 2) ?> <?= $adj['unit'] ?></td>
                                <td class="px-6 py-4 text-sm text-slate-800"><?= number_format($adj['new_stock'], 2) ?> <?= $adj['unit'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $adj['difference'] < 0 ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700' ?>">
                                        <?= $adj['difference'] > 0 ? '+' : '' ?><?= number_format($adj['difference'], 2) ?> <?= $adj['unit'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($adj['reason'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <script>
        const materialSelect = document.getElementById('material_id');
        const oldStockInput = document.getElementById('old_stock');
        const unitInput = document.getElementById('unit');
        const form = document.getElementById('adjustmentForm');
        const alert = document.getElementById('alert');

        materialSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            oldStockInput.value = selected.dataset.stock || '0';
            unitInput.value = selected.dataset.unit || '';
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const response = await fetch('/stock-adjustments/store', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan: ' + error.message);
            }
        });

        function showAlert(type, message) {
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 5000);
        }
    </script>

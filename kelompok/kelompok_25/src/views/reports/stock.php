<section class="p-6 md:p-10 space-y-8">

    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 uppercase tracking-[0.3em]">Laporan</p>
            <h1 class="text-2xl font-semibold text-slate-800 mt-1">Laporan Stok Terkini</h1>
            <p class="text-sm text-slate-500">Monitoring real-time inventory bahan baku</p>
        </div>
        <button onclick="exportExcel()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-6-6m6 6l6-6" /></svg>
            Export Excel
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 text-xl">📦</span>
            </div>
            <p class="text-sm text-slate-500">Total Item</p>
            <p class="text-2xl font-semibold text-slate-900"><?= $summary['total_items'] ?? 0 ?></p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xl">✓</span>
            </div>
            <p class="text-sm text-slate-500">Total Nilai</p>
            <p class="text-2xl font-semibold text-slate-900">Rp <?= number_format($summary['total_value'] ?? 0, 0, ',', '.') ?></p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 text-xl">⚠</span>
            </div>
            <p class="text-sm text-slate-500">Perlu Restock</p>
            <p class="text-2xl font-semibold text-slate-900"><?= $summary['restock_needed'] ?? 0 ?> Item</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 text-xl">⊗</span>
            </div>
            <p class="text-sm text-slate-500">Hampir Habis</p>
            <p class="text-2xl font-semibold text-slate-900"><?= $summary['almost_empty'] ?? 0 ?> Item</p>
        </article>
    </div>

    <div class="rounded-2xl bg-white border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3">
        <div class="flex-1 min-w-[250px] relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            <input type="text" id="searchInput" placeholder="Cari bahan baku..." value="<?= htmlspecialchars($filters['search']) ?>" class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select id="categoryFilter" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $filters['category'] === $cat['name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="statusFilter" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Status</option>
            <option value="Aman" <?= $filters['status'] === 'Aman' ? 'selected' : '' ?>>Aman</option>
            <option value="Hampir Habis" <?= $filters['status'] === 'Hampir Habis' ? 'selected' : '' ?>>Hampir Habis</option>
            <option value="Perlu Restock" <?= $filters['status'] === 'Perlu Restock' ? 'selected' : '' ?>>Perlu Restock</option>
        </select>
    </div>

    <div class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Nama Bahan</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Kategori</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Stok Tersedia</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Stok Minimum</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Harga/Unit</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Total Nilai</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($materials)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">Tidak ada data</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $material): 
                            $status = $material['current_stock'] > $material['min_stock'] ? 'safe' : 
                                     ($material['current_stock'] > 0 ? 'warning' : 'restock');
                            $statusText = $status === 'safe' ? 'Aman' : ($status === 'warning' ? 'Hampir Habis' : 'Perlu Restock');
                            $statusClass = $status === 'safe' ? 'bg-emerald-50 text-emerald-700' : ($status === 'warning' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700');
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">📦</span>
                                    <span class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($material['name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($material['category_name'] ?? '-') ?></td>
                            <td class="px-6 py-4 text-sm text-slate-800"><?= number_format($material['current_stock'], 0, ',', '.') ?> <?= $material['unit'] ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= number_format($material['min_stock'], 0, ',', '.') ?> <?= $material['unit'] ?></td>
                            <td class="px-6 py-4 text-sm text-slate-800">Rp <?= number_format($material['unit_price'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">Rp <?= number_format($material['total_value'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                                    <?= $status === 'safe' ? '✓' : ($status === 'warning' ? '⊗' : '⚠') ?>
                                    <?= $statusText ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script>
let debounceTimer;

document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 500);
});

document.getElementById('categoryFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (category) params.append('category', category);
    if (status) params.append('status', status);
    
    window.location.href = '?' + params.toString();
}

function exportExcel() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (category) params.append('category', category);
    if (status) params.append('status', status);
    
    window.location.href = '/reports/export-excel?' + params.toString();
}
</script>

<section class="p-6 md:p-10 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 uppercase tracking-[0.3em]">Laporan</p>
            <h1 class="text-2xl font-semibold text-slate-800 mt-1">Laporan Transaksi</h1>
            <p class="text-sm text-slate-500">Riwayat semua transaksi stok masuk dan keluar</p>
        </div>
        <button onclick="exportCSV()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <span>⬇</span> Export CSV
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 text-xl">📄</span>
            </div>
            <p class="text-sm text-slate-500">Total Transaksi</p>
            <p class="text-2xl font-semibold text-slate-900"><?= $summary['total_transactions'] ?></p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xl">⊕</span>
            </div>
            <p class="text-sm text-slate-500">Total Stok Masuk</p>
            <p class="text-2xl font-semibold text-emerald-600">Rp <?= number_format($summary['total_stock_in'], 0, ',', '.') ?></p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 text-xl">⊖</span>
            </div>
            <p class="text-sm text-slate-500">Total Stok Keluar</p>
            <p class="text-2xl font-semibold text-amber-600">Rp <?= number_format($summary['total_stock_out'], 0, ',', '.') ?></p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 text-xl">⚙</span>
            </div>
            <p class="text-sm text-slate-500">Penyesuaian</p>
            <p class="text-2xl font-semibold text-blue-600"><?= $summary['total_adjustments'] ?> <span class="text-sm text-slate-500">Transaksi</span></p>
        </article>
    </div>

    <article class="rounded-2xl bg-white border border-slate-100 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Tren Transaksi (7 Hari Terakhir)</h2>
        <canvas id="trendChart" style="height: 300px;"></canvas>
    </article>

    <article class="rounded-2xl bg-white border border-slate-100 shadow-sm p-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 mb-2">Jenis Transaksi</label>
                <select id="typeFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Semua</option>
                    <option value="stock_in">Stok Masuk</option>
                    <option value="stock_out">Stok Keluar</option>
                    <option value="adjustment">Penyesuaian</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Mulai</label>
                <input type="date" id="startDate" value="<?= $filters['start_date'] ?? '' ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Akhir</label>
                <input type="date" id="endDate" value="<?= $filters['end_date'] ?? '' ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button onclick="applyFilter()" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Filter</button>
        </div>
    </article>

    <article class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Bahan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($transactions as $txn): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 text-sm text-slate-600"><?= date('Y-m-d', strtotime($txn['date'])) ?></td>
                        <td class="px-6 py-4">
                            <?php if ($txn['type'] === 'stock_in'): ?>
                                <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Stok Masuk</span>
                            <?php elseif ($txn['type'] === 'stock_out'): ?>
                                <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Stok Keluar</span>
                            <?php else: ?>
                                <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Penyesuaian</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-800">
                            <?php if ($txn['type'] === 'stock_in'): ?>
                                <span class="text-emerald-600">⊕</span>
                            <?php elseif ($txn['type'] === 'stock_out'): ?>
                                <span class="text-amber-600">⊖</span>
                            <?php else: ?>
                                <span class="text-blue-600">⚙</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($txn['material_name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= number_format($txn['quantity'], 0, ',', '.') ?> <?= $txn['unit'] ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-800">Rp <?= number_format($txn['value'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function applyFilter() {
            const type = document.getElementById('typeFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            const params = new URLSearchParams();
            if (type !== 'all') params.append('type', type);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            
            window.location.href = '/reports/transactions?' + params.toString();
        }

        function exportCSV() {
            const type = document.getElementById('typeFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            const params = new URLSearchParams();
            if (type !== 'all') params.append('type', type);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            
            window.location.href = '/reports/transactions/export?' + params.toString();
        }

    // Load chart after page loads
    window.addEventListener('load', function() {
        fetch('/api/transactions/trend')
            .then(res => res.json())
            .then(data => {
                const dates = [];
                const stockInValues = [];
                const stockOutValues = [];
                
                for (let i = 6; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    const dateStr = date.toISOString().split('T')[0];
                    dates.push(date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
                    
                    const stockIn = data.stock_in.find(d => d.date === dateStr);
                    const stockOut = data.stock_out.find(d => d.date === dateStr);
                    
                    stockInValues.push(stockIn ? parseFloat(stockIn.stock_in_value) : 0);
                    stockOutValues.push(stockOut ? parseFloat(stockOut.stock_out_count) * 100000 : 0);
                }

                const ctx = document.getElementById('trendChart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Stok Masuk (Rp)',
                                data: stockInValues,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true
                            }, {
                                label: 'Stok Keluar (Est.)',
                                data: stockOutValues,
                                borderColor: '#F97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    display: true,
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: { 
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading chart:', error);
            });
    });
</script>


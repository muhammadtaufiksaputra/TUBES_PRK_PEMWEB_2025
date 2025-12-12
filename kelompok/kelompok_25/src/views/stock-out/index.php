<section class="p-6 md:p-10 space-y-8">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Stok Keluar</h1>
            <p class="text-sm text-slate-500">Catat penggunaan bahan baku</p>
        </div>
        <button id="btnCreateStockOut" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-3 rounded-xl shadow flex items-center gap-2 w-fit">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Catat Pengeluaran
        </button>
    </div>

    <!-- FILTERS -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm text-slate-600">Material</label>
                <select id="filterMaterial" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                    <option value="">Semua Material</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Jenis Penggunaan</label>
                <select id="filterUsageType" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                    <option value="">Semua Jenis</option>
                    <option value="production">Produksi</option>
                    <option value="sale">Penjualan</option>
                    <option value="waste">Terbuang</option>
                    <option value="transfer">Transfer</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Dari Tanggal</label>
                <input type="date" id="filterStartDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>
            <div>
                <label class="text-sm text-slate-600">Sampai Tanggal</label>
                <input type="date" id="filterEndDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-slate-500 border-b">
                    <tr>
                        <th class="py-3 text-left">No</th>
                        <th class="text-left">No. Referensi</th>
                        <th class="text-left">Material</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-left">Jenis</th>
                        <th class="text-left">Tujuan</th>
                        <th class="text-left">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700" id="stockOutTableBody">
                    <tr>
                        <td colspan="8" class="py-10 text-center">
                            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-orange-500 border-r-transparent"></div>
                            <p class="mt-2 text-slate-400">Memuat data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <nav class="mt-6">
            <ul class="flex justify-center items-center gap-2" id="pagination"></ul>
        </nav>
    </div>

</section>

<!-- MODAL CREATE/EDIT -->
<div id="stockOutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 rounded-t-3xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-900" id="modalTitle">Catat Pengeluaran Stok</h3>
            <button id="closeModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="stockOutForm" class="p-6 space-y-4">
            <input type="hidden" id="stockout_id" name="id">
            <input type="hidden" id="current_stock" value="0">
            
            <div>
                <label class="text-sm text-slate-600">Material <span class="text-red-500">*</span></label>
                <select id="material_id" name="material_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                    <option value="">Pilih Material</option>
                </select>
            </div>

            <!-- STOCK PREVIEW -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Stok Tersedia:</span>
                    <span class="font-semibold text-slate-900" id="currentStockDisplay">-</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-slate-600">Setelah Keluar:</span>
                    <span class="font-semibold" id="afterStockDisplay">-</span>
                </div>
                <p class="text-sm mt-2" id="stockWarning" class="hidden"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Jumlah <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="quantity" name="quantity" required placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Jenis Penggunaan <span class="text-red-500">*</span></label>
                    <select id="usage_type" name="usage_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                        <option value="">Pilih Jenis</option>
                        <option value="production">Produksi</option>
                        <option value="sale">Penjualan</option>
                        <option value="waste">Terbuang</option>
                        <option value="transfer">Transfer</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tujuan/Destinasi</label>
                    <input type="text" id="destination" name="destination" placeholder="Contoh: Produksi Roti" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" id="transaction_date" name="transaction_date" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400">
                </div>
            </div>

            <div>
                <label class="text-sm text-slate-600">Catatan</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Catatan tambahan..." class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-400"></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" id="cancelBtn" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 rounded-xl">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full">
        <div class="border-b border-slate-100 px-6 py-4 rounded-t-3xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-900">Detail Pengeluaran Stok</h3>
            <button id="closeDetailModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="detailContent" class="p-6"></div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toast" class="hidden fixed top-4 right-4 z-50 max-w-sm w-full">
    <div class="flex gap-3 rounded-xl bg-white shadow-lg border border-slate-200 p-4">
        <div id="toastIcon" class="mt-0.5"></div>
        <div class="flex-1">
            <p id="toastTitle" class="font-semibold text-slate-800"></p>
            <p id="toastMessage" class="text-sm text-slate-600 mt-1"></p>
        </div>
        <button onclick="Toast.hide()" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

<script src="/assets/js/utils/api.js"></script>
<script src="/assets/js/utils/toast.js"></script>
<script src="/assets/js/utils/validator.js"></script>
<script src="/assets/js/modules/stock-out.js"></script>

<script>
// Modal close handlers
document.getElementById('closeModal')?.addEventListener('click', () => {
    document.getElementById('stockOutModal')?.classList.add('hidden');
});

document.getElementById('cancelBtn')?.addEventListener('click', () => {
    document.getElementById('stockOutModal')?.classList.add('hidden');
});

document.getElementById('closeDetailModal')?.addEventListener('click', () => {
    document.getElementById('detailModal')?.classList.add('hidden');
});
</script>
<section class="p-6 md:p-10 space-y-8">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Stok Masuk</h1>
            <p class="text-sm text-slate-500">Catat pembelian dan penerimaan bahan baku</p>
        </div>
        <button id="btnCreateStockIn" class="bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-6 py-3 rounded-xl shadow flex items-center gap-2 w-fit">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Stok Masuk
        </button>
    </div>

    <!-- FILTERS -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="text-sm text-slate-600">Material</label>
                <select id="filterMaterial" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                    <option value="">Semua Material</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Supplier</label>
                <select id="filterSupplier" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                    <option value="">Semua Supplier</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Dari Tanggal</label>
                <input type="date" id="filterStartDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
            </div>
            <div>
                <label class="text-sm text-slate-600">Sampai Tanggal</label>
                <input type="date" id="filterEndDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
            </div>
            <div>
                <label class="text-sm text-slate-600">Cari</label>
                <input type="text" id="searchInput" placeholder="Cari..." class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
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
                        <th class="text-left">Tanggal</th>
                        <th class="text-left">Material</th>
                        <th class="text-left">Supplier</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Harga/Unit</th>
                        <th class="text-right">Total</th>
                        <th class="text-left">Batch</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700" id="stockInTableBody">
                    <tr>
                        <td colspan="9" class="py-10 text-center">
                            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-emerald-500 border-r-transparent"></div>
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
<div id="stockInModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 rounded-t-3xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-900" id="modalTitle">Tambah Stok Masuk</h3>
            <button id="closeModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="stockInForm" class="p-6 space-y-4">
            <input type="hidden" id="stockin_id" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Material <span class="text-red-500">*</span></label>
                <select id="material_id" name="material_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                    <option value="">Pilih Material</option>
                </select>
                <p class="mt-1 text-sm text-slate-500">Stok saat ini: <span id="currentStockDisplay" class="font-medium">-</span></p>
            </div>                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Supplier <span class="text-red-500">*</span></label>
                    <select id="supplier_id" name="supplier_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                        <option value="">Pilih Supplier</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" id="transaction_date" name="transaction_date" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Nomor Batch</label>
                    <input type="text" id="batch_number" name="batch_number" placeholder="Contoh: B20251210" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Jumlah <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="quantity" name="quantity" required placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Harga per Unit <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="unit_price" name="unit_price" required placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Total Harga</label>
                    <input type="number" step="0.01" id="total_price" name="total_price" readonly class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 bg-slate-50">
                    <p class="mt-1 text-sm text-emerald-600 font-semibold" id="totalDisplay">Rp 0</p>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tanggal Kadaluarsa</label>
                    <input type="date" id="expiry_date" name="expiry_date" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Catatan</label>
                    <input type="text" id="notes" name="notes" placeholder="Catatan tambahan" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400">
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" id="cancelBtn" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 rounded-xl">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 rounded-xl">
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
            <h3 class="text-lg font-semibold text-slate-900">Detail Stok Masuk</h3>
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
<script src="/assets/js/modules/stock-in.js"></script>

<script>
// Modal close handlers
document.getElementById('closeModal')?.addEventListener('click', () => {
    document.getElementById('stockInModal')?.classList.add('hidden');
});

document.getElementById('cancelBtn')?.addEventListener('click', () => {
    document.getElementById('stockInModal')?.classList.add('hidden');
});

document.getElementById('closeDetailModal')?.addEventListener('click', () => {
    document.getElementById('detailModal')?.classList.add('hidden');
});

// Auto-calculate total price
document.getElementById('quantity')?.addEventListener('input', calculateTotal);
document.getElementById('unit_price')?.addEventListener('input', calculateTotal);

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity')?.value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price')?.value) || 0;
    const total = quantity * unitPrice;
    
    document.getElementById('total_price').value = total;
    document.getElementById('totalDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
}
</script>

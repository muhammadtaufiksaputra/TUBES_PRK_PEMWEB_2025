<section class="p-6 md:p-10 space-y-8">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Penyesuaian Stok</h1>
            <p class="text-sm text-slate-500">Koreksi stok jika terdapat selisih antara sistem dan fisik</p>
        </div>
        <button id="btnCreateAdjustment" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-xl shadow flex items-center gap-2 w-fit">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Penyesuaian
        </button>
    </div>

    <!-- FILTERS -->
    <div class="bg-white border border-slate-100 rounded-3xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm text-slate-600">Material</label>
                <select id="filterMaterial" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">Semua Material</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Alasan</label>
                <select id="filterReason" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">Semua Alasan</option>
                    <option value="count_correction">Koreksi Perhitungan</option>
                    <option value="damage">Kerusakan</option>
                    <option value="expiry">Kadaluarsa</option>
                    <option value="theft">Kehilangan</option>
                    <option value="system_error">Error Sistem</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Dari Tanggal</label>
                <input type="date" id="filterStartDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>
            <div>
                <label class="text-sm text-slate-600">Sampai Tanggal</label>
                <input type="date" id="filterEndDate" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
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
                        <th class="text-left">Nama Bahan</th>
                        <th class="text-right">Stok Sistem</th>
                        <th class="text-right">Stok Fisik (Hasil Hitung)</th>
                        <th class="text-right">Selisih</th>
                        <th class="text-left">Alasan Penyesuaian</th>
                        <th class="text-left">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700" id="adjustmentTableBody">
                    <tr>
                        <td colspan="7" class="py-10 text-center">
                            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div>
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

<!-- MODAL CREATE -->
<div id="adjustmentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 rounded-t-3xl flex justify-between items-center">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-blue-100 text-blue-600 rounded-xl p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900" id="adjustmentModalLabel">Form Penyesuaian Stok</h3>
                        <p class="text-sm text-slate-500">Sesuaikan stok berdasarkan perhitungan fisik</p>
                    </div>
                </div>
            </div>
            <button id="closeModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="adjustmentForm" class="p-6 space-y-4">
            <input type="hidden" id="adjustment_id" name="id">
            
            <!-- Material Selection -->
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 mb-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Nama Bahan <span class="text-red-500">*</span>
                </label>
                <select id="material_id" name="material_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">Pilih Material</option>
                </select>
            </div>

            <!-- Stock Information Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700 mb-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Stok di Sistem
                    </label>
                    <input type="number" id="current_stock" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-slate-600 font-mono text-right" value="0">
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700 mb-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Stok Fisik (Hasil Hitung) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="new_stock" name="new_stock" step="0.01" min="0" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 font-mono text-right" placeholder="0">
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 mb-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Alasan Penyesuaian <span class="text-red-500">*</span>
                </label>
                <select id="reason" name="reason" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">Pilih Alasan</option>
                    <option value="count_correction">Koreksi Perhitungan</option>
                    <option value="damage">Barang Rusak/Expired</option>
                    <option value="expiry">Kadaluarsa</option>
                    <option value="theft">Kehilangan/Pencurian</option>
                    <option value="system_error">Kesalahan Input/Sistem</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 mb-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    Keterangan
                    <span class="text-xs text-slate-400">(opsional)</span>
                </label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 resize-none" placeholder="Catatan tambahan (opsional)"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" id="btnCancelAdjustment" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-6 py-2.5 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-2.5 rounded-xl shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Penyesuaian
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 rounded-t-3xl flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-900">Detail Penyesuaian Stok</h3>
            <button id="closeDetailModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div id="detailContent" class="p-6">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed top-4 right-4 z-50 min-w-[300px] bg-white rounded-lg shadow-lg border-l-4 transform transition-all duration-300 ease-in-out">
    <div class="p-4 flex items-start">
        <div id="toastIcon" class="flex-shrink-0 w-6 h-6 mr-3"></div>
        <div class="flex-1">
            <h4 id="toastTitle" class="font-semibold text-gray-900 mb-1"></h4>
            <p id="toastMessage" class="text-sm text-gray-600"></p>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="/assets/js/utils/api.js"></script>
<script src="/assets/js/utils/toast.js"></script>
<script src="/assets/js/utils/validator.js"></script>
<script src="/assets/js/modules/stock-adjustment.js"></script>

<script>
// Bootstrap Modal replacement for Tailwind
document.addEventListener('DOMContentLoaded', function() {
    // Show/Hide Modal Functions
    const adjustmentModal = document.getElementById('adjustmentModal');
    const detailModal = document.getElementById('detailModal');
    
    // Create Adjustment Button
    document.getElementById('btnCreateAdjustment')?.addEventListener('click', () => {
        adjustmentModal.classList.remove('hidden');
    });
    
    // Close buttons
    document.getElementById('closeModal')?.addEventListener('click', () => {
        adjustmentModal.classList.add('hidden');
    });
    
    document.getElementById('btnCancelAdjustment')?.addEventListener('click', () => {
        adjustmentModal.classList.add('hidden');
    });
    
    document.getElementById('closeDetailModal')?.addEventListener('click', () => {
        detailModal.classList.add('hidden');
    });
    
    // Click outside to close
    adjustmentModal?.addEventListener('click', (e) => {
        if (e.target === adjustmentModal) {
            adjustmentModal.classList.add('hidden');
        }
    });
    
    detailModal?.addEventListener('click', (e) => {
        if (e.target === detailModal) {
            detailModal.classList.add('hidden');
        }
    });
    
    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            adjustmentModal.classList.add('hidden');
            detailModal.classList.add('hidden');
        }
    });
});
</script>

<section class="p-6 md:p-10 space-y-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Data Bahan Baku</h1>
        <p class="text-sm text-slate-500">Kelola semua bahan baku inventory Anda</p>
    </div>

    <!-- FILTERS AND ADD BUTTON -->
    <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="relative flex-1">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" /></svg>
                </span>
                <input id="searchInput" type="text" placeholder="Cari bahan baku..." class="w-full rounded-2xl border border-slate-200 pl-12 pr-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
            </div>
            <select id="categoryFilter" class="rounded-2xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                <option value="">Semua Kategori</option>
            </select>
            <select id="statusFilter" class="rounded-2xl border border-slate-200 px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                <option value="">Semua Status</option>
                <option value="Aman">Aman</option>
                <option value="Hampir Habis">Hampir Habis</option>
                <option value="Habis">Habis</option>
            </select>
        </div>
        <button id="btnAddMaterial" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-sm hover:shadow-md transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" /></svg>
            Tambah Bahan Baku
        </button>
    </div>

    <!-- LOADING STATE -->
    <div id="loadingState" class="hidden">
        <div class="flex flex-col items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
            <p class="mt-4 text-slate-500">Memuat data material...</p>
        </div>
    </div>

    <!-- EMPTY STATE -->
    <div id="emptyState" class="hidden">
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
            <p id="emptyMessage" class="text-slate-500">Belum ada material yang ditambahkan</p>
        </div>
    </div>

    <!-- MATERIAL TABLE -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                <tr>
                    <th class="px-6 py-4">Nama Bahan</th>
                    <th class="px-6 py-4">Kategori</th>
                    <th class="px-6 py-4">Stok</th>
                    <th class="px-6 py-4">Min. Stok</th>
                    <th class="px-6 py-4">Supplier</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody id="materialTable" class="divide-y divide-slate-100 text-sm text-slate-600"></tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div id="paginationContainer" class="flex justify-center mt-8"></div>
</section>

<!-- MATERIAL MODAL -->
<div id="materialModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <h2 id="modalTitle" class="text-lg font-semibold text-slate-900">Tambah Bahan Baku</h2>
            <button onclick="MaterialModule.closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Form -->
        <form id="materialForm" class="p-6 space-y-4">
            <input type="hidden" id="materialId" name="id" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Kode Material -->
                <div>
                    <label for="materialCode" class="block text-sm font-medium text-slate-700 mb-2">Kode Material</label>
                    <input id="materialCode" name="code" type="text" placeholder="e.g., MAT-001" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" />
                    <p id="codeError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Nama Material -->
                <div>
                    <label for="materialName" class="block text-sm font-medium text-slate-700 mb-2">Nama Material <span class="text-red-500">*</span></label>
                    <input id="materialName" name="name" type="text" placeholder="e.g., Tepung Terigu" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required />
                    <p id="nameError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Kategori -->
                <div>
                    <label for="categoryId" class="block text-sm font-medium text-slate-700 mb-2">Kategori <span class="text-red-500">*</span></label>
                    <select id="categoryId" name="category_id" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required>
                        <option value="">Pilih Kategori</option>
                    </select>
                    <p id="category_idError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Supplier -->
                <div>
                    <label for="supplierId" class="block text-sm font-medium text-slate-700 mb-2">Supplier Default</label>
                    <select id="supplierId" name="default_supplier_id" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                        <option value="">Pilih Supplier</option>
                    </select>
                    <p id="default_supplier_idError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Satuan -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-slate-700 mb-2">Satuan <span class="text-red-500">*</span></label>
                    <input id="unit" name="unit" type="text" placeholder="e.g., Kg, Liter, Pcs" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required />
                    <p id="unitError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Minimal Stok -->
                <div>
                    <label for="minStock" class="block text-sm font-medium text-slate-700 mb-2">Minimal Stok <span class="text-red-500">*</span></label>
                    <input id="minStock" name="min_stock" type="number" step="0.01" placeholder="0" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required />
                    <p id="min_stockError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>

                <!-- Stok Saat Ini -->
                <div>
                    <label for="currentStock" class="block text-sm font-medium text-slate-700 mb-2">Stok Saat Ini</label>
                    <input id="currentStock" name="current_stock" type="number" step="0.01" placeholder="0" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" />
                    <p id="current_stockError" class="hidden text-sm text-red-500 mt-1"></p>
                </div>
            </div>

            <!-- Image Upload -->
            <div>
                <label for="imageUpload" class="block text-sm font-medium text-slate-700 mb-2">Gambar Material</label>
                <input id="imageUpload" name="image" type="file" accept="image/*" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" />
                <p class="text-xs text-slate-500 mt-1">Format: JPG, PNG, GIF. Maksimal 2MB</p>
                <div id="imagePreview" class="mt-2 flex gap-2"></div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="MaterialModule.closeModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button id="btnSubmit" type="submit" class="flex-1 px-4 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium hover:opacity-90 transition-all inline-flex items-center justify-center gap-2">
                    <span id="submitText">Simpan</span>
                    <svg id="submitSpinner" class="hidden w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="15.7 47.1" /></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Hapus Material</h2>
        </div>
        <div class="p-6">
            <p class="text-slate-600">Apakah Anda yakin ingin menghapus material <strong id="deleteItemName"></strong>?</p>
            <p class="text-sm text-slate-500 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="flex gap-3 p-6 border-t border-slate-100">
            <button onclick="MaterialModule.closeDeleteModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                Batal
            </button>
            <button id="btnConfirmDelete" onclick="MaterialModule.confirmDelete()" class="flex-1 px-4 py-2.5 rounded-lg bg-red-500 text-white font-medium hover:bg-red-600 transition-colors inline-flex items-center justify-center gap-2">
                <span id="deleteText">Hapus</span>
                <svg id="deleteSpinner" class="hidden w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="15.7 47.1" /></svg>
            </button>
        </div>
    </div>
</div>

<!-- IMAGE MANAGEMENT MODAL -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Kelola Gambar Material</h2>
                <p id="imageModalMaterialName" class="text-sm text-slate-500 mt-1"></p>
            </div>
            <button onclick="MaterialModule.closeImageModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-6 space-y-6">
            <!-- Upload Form -->
            <form id="imageUploadForm" class="bg-slate-50 rounded-xl p-4">
                <div class="flex gap-3">
                    <input id="imageFile" type="file" accept="image/*" class="flex-1 rounded-lg border border-slate-200 px-4 py-2 bg-white" required />
                    <button id="imageUploadBtn" type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Upload
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-2">Format: JPG, PNG, GIF. Maksimal 2MB</p>
            </form>

            <!-- Image Grid -->
            <div id="imageGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toast" class="hidden fixed top-4 right-4 z-50 max-w-sm w-full">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 flex items-start gap-3">
        <div id="toastIcon" class="flex-shrink-0"></div>
        <div class="flex-1 min-w-0">
            <h4 id="toastTitle" class="font-semibold text-slate-900 text-sm"></h4>
            <p id="toastMessage" class="text-sm text-slate-600 mt-1"></p>
        </div>
        <button onclick="Toast.hide()" class="flex-shrink-0 text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

<!-- SCRIPTS -->
<script src="/assets/js/utils/api.js"></script>
<script src="/assets/js/utils/toast.js"></script>
<script src="/assets/js/utils/validator.js"></script>
<script src="/assets/js/modules/material.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, initializing MaterialModule...');
        MaterialModule.init();
    });
</script>

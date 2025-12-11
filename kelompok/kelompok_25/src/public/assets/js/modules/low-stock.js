/**
 * Low Stock Management Module
 * Handles low stock materials monitoring and alerts
 */

const LowStock = (function() {
    let currentMaterials = [];
    let currentPage = 1;
    let totalPages = 1;
    let debounceTimer = null;

    function init() {
        loadSummary();
        loadMaterials();
        setupEventListeners();
    }

    function setupEventListeners() {
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const onlyOutOfStock = document.getElementById('onlyOutOfStock');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentPage = 1;
                    loadMaterials();
                }, 500);
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                currentPage = 1;
                loadMaterials();
            });
        }

        if (onlyOutOfStock) {
            onlyOutOfStock.addEventListener('change', () => {
                currentPage = 1;
                loadMaterials();
            });
        }

        if (prevPage) {
            prevPage.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadMaterials();
                }
            });
        }

        if (nextPage) {
            nextPage.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadMaterials();
                }
            });
        }
    }

    async function loadSummary() {
        try {
            const response = await fetch('/api/low-stock/summary');
            const result = await response.json();

            if (result.success && result.data) {
                const summary = result.data;
                document.getElementById('totalLowStock').textContent = summary.total_low_stock || 0;
                document.getElementById('outOfStock').textContent = summary.out_of_stock || 0;
                document.getElementById('critical').textContent = summary.critical || 0;
                document.getElementById('totalShortage').textContent = (summary.total_shortage_quantity || 0) + ' items';
            }
        } catch (error) {
            console.error('Error loading summary:', error);
        }
    }

    async function loadMaterials() {
        const loadingState = document.getElementById('loadingState');
        const tableBody = document.getElementById('materialsTableBody');
        const emptyState = document.getElementById('emptyState');
        const pagination = document.getElementById('pagination');

        try {
            loadingState.classList.remove('hidden');
            tableBody.innerHTML = '';
            emptyState.classList.add('hidden');
            pagination.classList.add('hidden');

            const params = new URLSearchParams();
            params.append('page', currentPage);
            params.append('per_page', 20);

            const search = document.getElementById('searchInput')?.value;
            const categoryId = document.getElementById('categoryFilter')?.value;
            const onlyOut = document.getElementById('onlyOutOfStock')?.checked;

            if (search) params.append('search', search);
            if (categoryId) params.append('category_id', categoryId);
            if (onlyOut) params.append('only_out_of_stock', '1');

            const response = await fetch(`/api/low-stock?${params.toString()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            currentMaterials = result.data.data || [];
            totalPages = result.data.last_page || 1;
            
            renderMaterials();
            updatePagination(result.data);

        } catch (error) {
            console.error('Error loading materials:', error);
            showToast('error', 'Gagal', 'Gagal memuat data bahan');
        } finally {
            loadingState.classList.add('hidden');
        }
    }

    function renderMaterials() {
        const tableBody = document.getElementById('materialsTableBody');
        const emptyState = document.getElementById('emptyState');

        if (currentMaterials.length === 0) {
            tableBody.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');

        tableBody.innerHTML = currentMaterials.map(material => {
            const statusClass = material.status === 'out_of_stock' 
                ? 'bg-red-100 text-red-700' 
                : 'bg-orange-100 text-orange-700';
            const statusText = material.status === 'out_of_stock' ? 'Habis Total' : 'Low Stock';
            const statusIcon = material.status === 'out_of_stock' ? '🔴' : '⚠️';

            return `
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 text-sm font-mono text-slate-600">${escapeHtml(material.code)}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-800">${escapeHtml(material.name)}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${escapeHtml(material.category_name || '-')}</td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold ${material.current_stock === 0 ? 'text-red-600' : 'text-slate-800'}">
                            ${material.current_stock} ${escapeHtml(material.unit)}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${material.min_stock} ${escapeHtml(material.unit)}</td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold text-red-600">
                            ${material.shortage_quantity || 0} ${escapeHtml(material.unit)}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full ${statusClass}">
                            ${statusIcon} ${statusText}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${escapeHtml(material.supplier_name || '-')}</td>
                    <td class="px-6 py-4">
                        <button onclick="LowStock.notifySupplier(${material.id})" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Kirim Notif
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updatePagination(data) {
        const pagination = document.getElementById('pagination');
        const showingStart = document.getElementById('showingStart');
        const showingEnd = document.getElementById('showingEnd');
        const totalItems = document.getElementById('totalItems');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');

        if (data.total > 0) {
            pagination.classList.remove('hidden');
            const start = (data.page - 1) * data.per_page + 1;
            const end = Math.min(data.page * data.per_page, data.total);
            
            showingStart.textContent = start;
            showingEnd.textContent = end;
            totalItems.textContent = data.total;

            prevBtn.disabled = data.page <= 1;
            nextBtn.disabled = data.page >= data.last_page;
        } else {
            pagination.classList.add('hidden');
        }
    }

    async function showUrgent() {
        try {
            const response = await fetch('/api/low-stock/urgent');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const materials = result.data.materials || [];
            const urgentContent = document.getElementById('urgentContent');

            if (materials.length === 0) {
                urgentContent.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-green-500 text-5xl mb-4">✅</div>
                        <p class="text-slate-600">Tidak ada bahan yang habis total</p>
                    </div>
                `;
            } else {
                urgentContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm font-semibold text-red-800">${materials.length} bahan memerlukan tindakan segera!</p>
                        </div>
                        <div class="space-y-3">
                            ${materials.map(m => `
                                <div class="border border-slate-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-slate-800">${escapeHtml(m.name)}</h4>
                                            <p class="text-sm text-slate-500">Kode: ${escapeHtml(m.code)} • Kategori: ${escapeHtml(m.category_name || '-')}</p>
                                        </div>
                                        <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                            🔴 Habis Total
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <p class="text-slate-500">Stok Saat Ini</p>
                                            <p class="font-semibold text-red-600">${m.current_stock} ${escapeHtml(m.unit)}</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">Min Stok</p>
                                            <p class="font-semibold text-slate-800">${m.min_stock} ${escapeHtml(m.unit)}</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">Harga Terakhir</p>
                                            <p class="font-semibold text-slate-800">Rp ${formatNumber(m.last_unit_price || 0)}</p>
                                        </div>
                                    </div>
                                    ${m.supplier_name ? `
                                        <div class="mt-3 pt-3 border-t border-slate-200">
                                            <p class="text-xs text-slate-500">Supplier:</p>
                                            <p class="text-sm font-medium text-slate-700">${escapeHtml(m.supplier_name)}</p>
                                            ${m.supplier_phone ? `<p class="text-sm text-slate-600">📞 ${escapeHtml(m.supplier_phone)}</p>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            document.getElementById('urgentModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error loading urgent materials:', error);
            showToast('error', 'Gagal', 'Gagal memuat data urgent');
        }
    }

    async function showReorderSuggestions() {
        try {
            const response = await fetch('/api/low-stock/reorder-suggestions');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const suggestions = result.data.suggestions || [];
            const totalCost = result.data.total_estimated_cost || 0;
            const reorderContent = document.getElementById('reorderContent');

            if (suggestions.length === 0) {
                reorderContent.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-green-500 text-5xl mb-4">✅</div>
                        <p class="text-slate-600">Tidak ada bahan yang perlu reorder</p>
                    </div>
                `;
            } else {
                reorderContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">${suggestions.length} bahan perlu direorder</p>
                                    <p class="text-xs text-emerald-600 mt-1">Estimasi total biaya pembelian</p>
                                </div>
                                <p class="text-2xl font-bold text-emerald-700">Rp ${formatNumber(totalCost)}</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Bahan</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Stok</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Min</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Saran Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Harga/Unit</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Est. Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    ${suggestions.map(s => `
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <p class="text-sm font-medium text-slate-800">${escapeHtml(s.name)}</p>
                                                <p class="text-xs text-slate-500">${escapeHtml(s.code)}</p>
                                            </td>
                                            <td class="px-4 py-3 text-sm ${s.current_stock === 0 ? 'text-red-600 font-semibold' : 'text-slate-600'}">
                                                ${s.current_stock} ${escapeHtml(s.unit)}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">${s.min_stock} ${escapeHtml(s.unit)}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex px-2 py-1 text-sm font-semibold bg-emerald-100 text-emerald-700 rounded">
                                                    ${s.suggested_reorder_qty} ${escapeHtml(s.unit)}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">Rp ${formatNumber(s.last_unit_price || 0)}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-slate-800">Rp ${formatNumber(s.estimated_cost || 0)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            document.getElementById('reorderModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error loading reorder suggestions:', error);
            showToast('error', 'Gagal', 'Gagal memuat saran reorder');
        }
    }

    async function showCategories() {
        try {
            const response = await fetch('/api/low-stock/categories');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const categories = result.data || [];
            const categoriesContent = document.getElementById('categoriesContent');

            if (categories.length === 0) {
                categoriesContent.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-slate-600">Tidak ada data kategori</p>
                    </div>
                `;
            } else {
                categoriesContent.innerHTML = `
                    <div class="space-y-3">
                        ${categories.map(cat => `
                            <div class="border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-slate-800">${escapeHtml(cat.name)}</h4>
                                        <p class="text-sm text-slate-500 mt-1">
                                            ${cat.low_stock_count} bahan low stock
                                            ${cat.out_of_stock_count > 0 ? ` • ${cat.out_of_stock_count} habis total` : ''}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-orange-600">${cat.low_stock_count}</p>
                                            <p class="text-xs text-slate-500">Low Stock</p>
                                        </div>
                                        ${cat.out_of_stock_count > 0 ? `
                                            <div class="text-right">
                                                <p class="text-2xl font-bold text-red-600">${cat.out_of_stock_count}</p>
                                                <p class="text-xs text-slate-500">Habis</p>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            document.getElementById('categoriesModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error loading categories:', error);
            showToast('error', 'Gagal', 'Gagal memuat data kategori');
        }
    }

    async function notifySupplier(materialId) {
        try {
            const response = await fetch(`/api/low-stock/${materialId}/notify`, {
                method: 'POST'
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('success', 'Berhasil', 'Notifikasi berhasil dicatat');
        } catch (error) {
            console.error('Error notifying supplier:', error);
            showToast('error', 'Gagal', 'Gagal mengirim notifikasi');
        }
    }

    function hideUrgentModal() {
        document.getElementById('urgentModal').classList.add('hidden');
    }

    function hideReorderModal() {
        document.getElementById('reorderModal').classList.add('hidden');
    }

    function hideCategoriesModal() {
        document.getElementById('categoriesModal').classList.add('hidden');
    }

    function exportReport() {
        showToast('info', 'Export', 'Fitur export akan segera tersedia');
    }

    function printReorder() {
        window.print();
    }

    function showToast(type, title, message) {
        const toast = document.getElementById('toast');
        const icon = document.getElementById('toastIcon');
        const titleEl = document.getElementById('toastTitle');
        const messageEl = document.getElementById('toastMessage');

        const icons = {
            success: '<svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };

        icon.innerHTML = icons[type] || icons.info;
        titleEl.textContent = title;
        messageEl.textContent = message;

        toast.classList.remove('hidden');
        setTimeout(() => hideToast(), 5000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on DOM load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        showUrgent,
        showReorderSuggestions,
        showCategories,
        notifySupplier,
        hideUrgentModal,
        hideReorderModal,
        hideCategoriesModal,
        exportReport,
        printReorder,
        hideToast
    };
})();

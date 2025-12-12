/**
 * Stock In Module
 * Handle Stock In CRUD operations dengan AJAX
 */

const StockInModule = {
    state: {
        stockIns: [],
        materials: [],
        suppliers: [],
        currentPage: 1,
        totalPages: 1,
        filters: {
            material_id: '',
            supplier_id: '',
            start_date: '',
            end_date: '',
            search: ''
        }
    },

    /**
     * Initialize module
     */
    init() {
        this.loadMaterials();
        this.loadSuppliers();
        this.loadStockIns();
        this.bindEvents();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Create button
        document.getElementById('btnCreateStockIn')?.addEventListener('click', () => {
            this.showModal();
        });

        // Form submit
        document.getElementById('stockInForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });

        // Filter changes
        document.getElementById('filterMaterial')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterSupplier')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterStartDate')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterEndDate')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            this.state.filters.search = e.target.value;
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.applyFilters(), 500);
        });

        // Material change handler - update current stock display
        document.getElementById('material_id')?.addEventListener('change', (e) => {
            this.updateCurrentStockDisplay(e.target.value);
        });
    },

    /**
     * Load materials for dropdown
     */
    async loadMaterials() {
        try {
            const response = await ApiClient.get('/materials', { per_page: 1000 });
            if (response.success && response.data) {
                this.state.materials = response.data.data || [];
                this.renderMaterialDropdowns();
            }
        } catch (error) {
            console.error('Load materials error:', error);
        }
    },

    /**
     * Load suppliers for dropdown
     */
    async loadSuppliers() {
        try {
            const response = await ApiClient.get('/suppliers', { per_page: 1000 });
            if (response.success && response.data) {
                this.state.suppliers = response.data.data || [];
                this.renderSupplierDropdowns();
            }
        } catch (error) {
            console.error('Load suppliers error:', error);
        }
    },

    /**
     * Render material dropdowns
     */
    renderMaterialDropdowns() {
        const formSelect = document.getElementById('material_id');
        const filterSelect = document.getElementById('filterMaterial');

        const options = `
            <option value="">Pilih Material</option>
            ${this.state.materials.map(m => `
                <option value="${m.id}">${m.code ? m.code + ' - ' : ''}${m.name} (Stok: ${m.current_stock || 0} ${m.unit})</option>
            `).join('')}
        `;

        if (formSelect) formSelect.innerHTML = options;
        if (filterSelect) filterSelect.innerHTML = '<option value="">Semua Material</option>' + options.substring(options.indexOf('</option>') + 9);
    },

    /**
     * Render supplier dropdowns
     */
    renderSupplierDropdowns() {
        const formSelect = document.getElementById('supplier_id');
        const filterSelect = document.getElementById('filterSupplier');

        const options = `
            <option value="">Pilih Supplier</option>
            ${this.state.suppliers.map(s => `
                <option value="${s.id}">${s.code ? s.code + ' - ' : ''}${s.name}</option>
            `).join('')}
        `;

        if (formSelect) formSelect.innerHTML = options;
        if (filterSelect) filterSelect.innerHTML = '<option value="">Semua Supplier</option>' + options.substring(options.indexOf('</option>') + 9);
    },

    /**
     * Update current stock display when material changes
     */
    updateCurrentStockDisplay(materialId) {
        const currentStockEl = document.getElementById('currentStockDisplay');
        if (!currentStockEl) return;

        if (!materialId) {
            currentStockEl.textContent = '-';
            return;
        }

        const material = this.state.materials.find(m => m.id == materialId);
        if (material) {
            currentStockEl.textContent = `${material.current_stock || 0} ${material.unit}`;
        }
    },

    /**
     * Load stock ins with filters
     */
    async loadStockIns() {
        try {
            const params = {
                page: this.state.currentPage,
                per_page: 10,
                ...this.state.filters
            };

            const response = await ApiClient.get('/stock-in', params);
            
            if (response.success && response.data) {
                this.state.stockIns = response.data.data || [];
                this.state.currentPage = response.data.current_page || 1;
                this.state.totalPages = response.data.last_page || 1;
                
                this.renderTable();
                this.renderPagination();
            }
        } catch (error) {
            console.error('Load stock ins error:', error);
            Toast.error('Gagal memuat data stok masuk: ' + error.message);
        }
    },

    /**
     * Apply filters and reload
     */
    applyFilters() {
        this.state.filters.material_id = document.getElementById('filterMaterial')?.value || '';
        this.state.filters.supplier_id = document.getElementById('filterSupplier')?.value || '';
        this.state.filters.start_date = document.getElementById('filterStartDate')?.value || '';
        this.state.filters.end_date = document.getElementById('filterEndDate')?.value || '';
        this.state.currentPage = 1;
        this.loadStockIns();
    },

    /**
     * Render table
     */
    renderTable() {
        const tbody = document.getElementById('stockInTableBody');
        if (!tbody) return;

        if (this.state.stockIns.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-8 text-slate-500">
                        Tidak ada data stok masuk
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.state.stockIns.map((item, index) => {
            const rowNum = (this.state.currentPage - 1) * 10 + index + 1;
            return `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="py-3 px-4">${rowNum}</td>
                    <td class="py-3 px-4 font-medium">${this.escapeHtml(item.reference_number || '-')}</td>
                    <td class="py-3 px-4">${this.escapeHtml(item.material_name || '-')}</td>
                    <td class="py-3 px-4">${this.escapeHtml(item.supplier_name || '-')}</td>
                    <td class="py-3 px-4 text-right font-medium text-emerald-600">
                        ${item.quantity || 0} ${this.escapeHtml(item.unit || '')}
                    </td>
                    <td class="py-3 px-4 text-right">Rp ${this.formatNumber(item.unit_price || 0)}</td>
                    <td class="py-3 px-4 text-right font-semibold">Rp ${this.formatNumber(item.total_price || 0)}</td>
                    <td class="py-3 px-4">${this.formatDate(item.transaction_date)}</td>
                </tr>
            `;
        }).join('');
    },

    /**
     * Render pagination
     */
    renderPagination() {
        const pagination = document.getElementById('pagination');
        if (!pagination || this.state.totalPages <= 1) {
            if (pagination) pagination.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `
            <button ${this.state.currentPage === 1 ? 'disabled' : ''} 
                    onclick="StockInModule.goToPage(${this.state.currentPage - 1})"
                    class="px-4 py-2 rounded-lg border border-slate-200 ${this.state.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'}">
                Previous
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= this.state.totalPages; i++) {
            if (i === 1 || i === this.state.totalPages || (i >= this.state.currentPage - 1 && i <= this.state.currentPage + 1)) {
                html += `
                    <button onclick="StockInModule.goToPage(${i})"
                            class="px-4 py-2 rounded-lg border ${i === this.state.currentPage ? 'bg-emerald-500 text-white border-emerald-500' : 'border-slate-200 hover:bg-slate-50'}">
                        ${i}
                    </button>
                `;
            } else if (i === this.state.currentPage - 2 || i === this.state.currentPage + 2) {
                html += '<span class="px-2">...</span>';
            }
        }

        // Next button
        html += `
            <button ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''} 
                    onclick="StockInModule.goToPage(${this.state.currentPage + 1})"
                    class="px-4 py-2 rounded-lg border border-slate-200 ${this.state.currentPage === this.state.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'}">
                Next
            </button>
        `;

        pagination.innerHTML = html;
    },

    /**
     * Go to specific page
     */
    goToPage(page) {
        if (page < 1 || page > this.state.totalPages) return;
        this.state.currentPage = page;
        this.loadStockIns();
    },

    /**
     * Show modal for create
     */
    showModal() {
        const modal = document.getElementById('stockInModal');
        const form = document.getElementById('stockInForm');
        
        if (form) form.reset();
        
        // Set default transaction date to today
        const today = new Date().toISOString().split('T')[0];
        const transactionDate = document.getElementById('transaction_date');
        if (transactionDate) transactionDate.value = today;
        
        // Clear current stock display
        const currentStockEl = document.getElementById('currentStockDisplay');
        if (currentStockEl) currentStockEl.textContent = '-';
        
        if (modal) modal.classList.remove('hidden');
    },

    /**
     * Hide modal
     */
    hideModal() {
        const modal = document.getElementById('stockInModal');
        if (modal) modal.classList.add('hidden');
    },

    /**
     * Handle form submit
     */
    async handleSubmit() {
        const form = document.getElementById('stockInForm');
        if (!form) return;

        const formData = {
            material_id: document.getElementById('material_id')?.value,
            supplier_id: document.getElementById('supplier_id')?.value,
            quantity: parseFloat(document.getElementById('quantity')?.value),
            unit_price: parseFloat(document.getElementById('unit_price')?.value),
            transaction_date: document.getElementById('transaction_date')?.value,
            notes: document.getElementById('notes')?.value || ''
        };

        // Validation
        if (!formData.material_id) {
            Toast.error('Pilih material terlebih dahulu');
            return;
        }
        if (!formData.supplier_id) {
            Toast.error('Pilih supplier terlebih dahulu');
            return;
        }
        if (!formData.quantity || formData.quantity <= 0) {
            Toast.error('Jumlah harus lebih dari 0');
            return;
        }
        if (!formData.unit_price || formData.unit_price < 0) {
            Toast.error('Harga satuan tidak valid');
            return;
        }
        if (!formData.transaction_date) {
            Toast.error('Tanggal transaksi harus diisi');
            return;
        }

        try {
            const response = await ApiClient.post('/stock-in', formData);
            
            if (response.success) {
                Toast.success('Stok masuk berhasil ditambahkan');
                this.hideModal();
                this.loadStockIns();
                this.loadMaterials(); // Reload to update stock display
            }
        } catch (error) {
            console.error('Submit error:', error);
            Toast.error('Gagal menambahkan stok masuk: ' + error.message);
        }
    },

    /**
     * View detail
     */
    async viewDetail(id) {
        try {
            const response = await ApiClient.get(`/stock-in/${id}`);
            
            if (response.success && response.data) {
                const item = response.data;
                
                const detailHtml = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-slate-600">No. Referensi</label>
                                <p class="font-medium">${this.escapeHtml(item.reference_number || '-')}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Tanggal</label>
                                <p class="font-medium">${this.formatDate(item.transaction_date)}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-slate-600">Material</label>
                                <p class="font-medium">${this.escapeHtml(item.material_name || '-')}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Supplier</label>
                                <p class="font-medium">${this.escapeHtml(item.supplier_name || '-')}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm text-slate-600">Jumlah</label>
                                <p class="font-medium text-emerald-600">${item.quantity || 0} ${this.escapeHtml(item.unit || '')}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Harga Satuan</label>
                                <p class="font-medium">Rp ${this.formatNumber(item.unit_price || 0)}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Total Harga</label>
                                <p class="font-medium text-lg">Rp ${this.formatNumber(item.total_price || 0)}</p>
                            </div>
                        </div>
                        ${item.notes ? `
                            <div>
                                <label class="text-sm text-slate-600">Catatan</label>
                                <p class="font-medium">${this.escapeHtml(item.notes)}</p>
                            </div>
                        ` : ''}
                        <div class="grid grid-cols-2 gap-4 text-sm text-slate-500">
                            <div>
                                <label class="text-xs">Dibuat</label>
                                <p>${this.formatDateTime(item.created_at)}</p>
                            </div>
                            <div>
                                <label class="text-xs">Diubah</label>
                                <p>${this.formatDateTime(item.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('detailContent').innerHTML = detailHtml;
                const modal = document.getElementById('detailModal');
                if (modal) modal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('View detail error:', error);
            Toast.error('Gagal memuat detail: ' + error.message);
        }
    },

    /**
     * Utility functions
     */
    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    },

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', { 
            day: '2-digit',
            month: 'short', 
            year: 'numeric' 
        }).format(date);
    },

    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', { 
            day: '2-digit',
            month: 'short', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => StockInModule.init());
} else {
    StockInModule.init();
}

/**
 * Stock Adjustment Module
 * Handle Stock Adjustment CRUD operations dengan AJAX
 */

const StockAdjustmentModule = {
    state: {
        adjustments: [],
        materials: [],
        currentPage: 1,
        totalPages: 1,
        filters: {
            material_id: '',
            reason: '',
            start_date: '',
            end_date: ''
        }
    },

    /**
     * Initialize module
     */
    init() {
        this.loadMaterials();
        this.loadAdjustments();
        this.bindEvents();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Create button
        document.getElementById('btnCreateAdjustment')?.addEventListener('click', () => {
            this.showModal();
        });

        // Form submit
        document.getElementById('adjustmentForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });

        // Filter changes
        document.getElementById('filterMaterial')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterReason')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterStartDate')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filterEndDate')?.addEventListener('change', () => {
            this.applyFilters();
        });

        // Material change handler
        document.getElementById('material_id')?.addEventListener('change', (e) => {
            this.handleMaterialChange(e.target.value);
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
     * Render material dropdowns
     */
    renderMaterialDropdowns() {
        const formSelect = document.getElementById('material_id');
        const filterSelect = document.getElementById('filterMaterial');

        const options = `
            <option value="">Pilih Material</option>
            ${this.state.materials.map(m => `
                <option value="${m.id}">${m.code ? m.code + ' - ' : ''}${m.name}</option>
            `).join('')}
        `;

        if (formSelect) {
            formSelect.innerHTML = options;
        }

        if (filterSelect) {
            filterSelect.innerHTML = `<option value="">Semua Material</option>` + options;
        }
    },

    /**
     * Handle material selection change
     */
    async handleMaterialChange(materialId) {
        const currentStockField = document.getElementById('current_stock');
        
        if (!materialId || !currentStockField) return;

        try {
            const response = await ApiClient.get(`/materials/${materialId}`);
            if (response.success && response.data) {
                const material = response.data.data || response.data;
                currentStockField.value = material.current_stock || 0;
            }
        } catch (error) {
            console.error('Get material error:', error);
            currentStockField.value = '0';
        }
    },

    /**
     * Load adjustments with filters
     */
    async loadAdjustments() {
        try {
            const params = {
                page: this.state.currentPage,
                per_page: 20,
                ...this.state.filters
            };

            // Remove empty params
            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === null) {
                    delete params[key];
                }
            });

            const response = await ApiClient.get('/stock-adjustments', params);
            
            if (response.success && response.data) {
                this.state.adjustments = response.data.data || [];
                this.state.currentPage = response.data.current_page || 1;
                this.state.totalPages = response.data.last_page || 1;
                
                this.renderAdjustments();
                this.renderPagination();
            }
        } catch (error) {
            console.error('Load adjustments error:', error);
            Toast.error('Gagal memuat data stock adjustment');
        }
    },

    /**
     * Render adjustments table
     */
    renderAdjustments() {
        const tbody = document.getElementById('adjustmentTableBody');
        if (!tbody) return;

        if (this.state.adjustments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data stock adjustment</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.state.adjustments.map((adj, index) => {
            const no = (this.state.currentPage - 1) * 20 + index + 1;
            const difference = parseFloat(adj.difference || 0);
            const diffClass = difference >= 0 ? 'text-success' : 'text-danger';
            const diffIcon = difference >= 0 ? '+' : '';
            
            // Format tanggal adjustment_date
            const adjustmentDate = adj.adjustment_date ? new Date(adj.adjustment_date).toLocaleDateString('id-ID') : '-';
            
            return `
                <tr>
                    <td>${no}</td>
                    <td>${adj.material_code ? adj.material_code + '<br>' : ''}${adj.material_name || '-'}</td>
                    <td class="text-end">${parseFloat(adj.old_stock || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(adj.new_stock || 0).toFixed(2)}</td>
                    <td class="text-end ${diffClass}">
                        <strong>${diffIcon}${difference.toFixed(2)}</strong>
                    </td>
                    <td><span class="badge bg-info">${this.getReasonLabel(adj.reason)}</span></td>
                    <td>
                        <small>${adjustmentDate}</small><br>
                        <small class="text-muted">oleh: ${adj.adjusted_by_name || '-'}</small>
                    </td>
                </tr>
            `;
        }).join('');
    },

    /**
     * Get reason label
     */
    getReasonLabel(reason) {
        const labels = {
            'count_correction': 'Koreksi Perhitungan',
            'damage': 'Kerusakan',
            'expiry': 'Kadaluarsa',
            'theft': 'Kehilangan',
            'system_error': 'Error Sistem',
            'other': 'Lainnya'
        };
        return labels[reason] || reason;
    },

    /**
     * Render pagination
     */
    renderPagination() {
        const pagination = document.getElementById('pagination');
        if (!pagination) return;

        if (this.state.totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = `
            <li>
                <button onclick="StockAdjustmentModule.changePage(${this.state.currentPage - 1}); return false;" 
                    ${this.state.currentPage === 1 ? 'disabled' : ''}
                    class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
            </li>
        `;

        for (let i = 1; i <= this.state.totalPages; i++) {
            if (i === 1 || i === this.state.totalPages || (i >= this.state.currentPage - 2 && i <= this.state.currentPage + 2)) {
                html += `
                    <li>
                        <button onclick="StockAdjustmentModule.changePage(${i}); return false;"
                            class="px-4 py-2 rounded-lg border ${i === this.state.currentPage ? 'bg-blue-500 text-white border-blue-500' : 'border-slate-200 hover:bg-slate-50'}">
                            ${i}
                        </button>
                    </li>
                `;
            } else if (i === this.state.currentPage - 3 || i === this.state.currentPage + 3) {
                html += `<li><span class="px-2">...</span></li>`;
            }
        }

        html += `
            <li>
                <button onclick="StockAdjustmentModule.changePage(${this.state.currentPage + 1}); return false;"
                    ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''}
                    class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </li>
        `;

        pagination.innerHTML = html;
    },

    /**
     * Change page
     */
    changePage(page) {
        if (page < 1 || page > this.state.totalPages) return;
        this.state.currentPage = page;
        this.loadAdjustments();
    },

    /**
     * Apply filters
     */
    applyFilters() {
        this.state.filters.material_id = document.getElementById('filterMaterial')?.value || '';
        this.state.filters.reason = document.getElementById('filterReason')?.value || '';
        this.state.filters.start_date = document.getElementById('filterStartDate')?.value || '';
        this.state.filters.end_date = document.getElementById('filterEndDate')?.value || '';
        
        this.state.currentPage = 1;
        this.loadAdjustments();
    },

    /**
     * Show create/edit modal
     */
    showModal(adjustmentId = null) {
        const modal = document.getElementById('adjustmentModal');
        const form = document.getElementById('adjustmentForm');
        const modalTitle = document.getElementById('adjustmentModalLabel');

        form.reset();
        document.getElementById('adjustment_id').value = '';
        document.getElementById('current_stock').value = '0';
        modalTitle.textContent = 'Form Penyesuaian Stok';

        modal.classList.remove('hidden');
    },

    /**
     * Handle form submit
     */
    async handleSubmit() {
        const form = document.getElementById('adjustmentForm');
        const formData = new FormData(form);

        const data = {
            material_id: parseInt(formData.get('material_id')),
            new_stock: parseFloat(formData.get('new_stock')),
            reason: formData.get('reason'),
            notes: formData.get('notes')
        };

        // Validate
        if (!data.material_id) {
            Toast.error('Material harus dipilih');
            return;
        }
        if (!data.new_stock && data.new_stock !== 0) {
            Toast.error('New stock harus diisi');
            return;
        }
        if (parseFloat(data.new_stock) < 0) {
            Toast.error('New stock tidak boleh negatif');
            return;
        }
        if (!data.reason) {
            Toast.error('Reason harus dipilih');
            return;
        }

        try {
            const response = await ApiClient.post('/stock-adjustments', data);

            if (response.success) {
                Toast.success(response.message || 'Stock adjustment berhasil dibuat');
                
                const modal = document.getElementById('adjustmentModal');
                modal.classList.add('hidden');
                
                form.reset();
                this.loadAdjustments();
            } else {
                Toast.error(response.message || 'Gagal membuat stock adjustment');
            }
        } catch (error) {
            console.error('Submit error:', error);
            Toast.error(error.message || 'Terjadi kesalahan saat menyimpan');
        }
    },

    /**
     * View detail
     */
    async viewDetail(id) {
        try {
            const response = await ApiClient.get(`/stock-adjustments/${id}`);
            
            if (response.success && response.data) {
                const adj = response.data.data;
                const difference = parseFloat(adj.difference || 0);
                const diffClass = difference >= 0 ? 'text-success' : 'text-danger';
                
                const detailHtml = `
                    <table class="table">
                        <tr>
                            <th width="150">Material</th>
                            <td>${adj.material_code ? adj.material_code + ' - ' : ''}${adj.material_name}</td>
                        </tr>
                        <tr>
                            <th>Stok Lama</th>
                            <td>${parseFloat(adj.old_stock).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Stok Baru</th>
                            <td>${parseFloat(adj.new_stock).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Selisih</th>
                            <td class="${diffClass}"><strong>${difference >= 0 ? '+' : ''}${difference.toFixed(2)}</strong></td>
                        </tr>
                        <tr>
                            <th>Alasan</th>
                            <td><span class="badge bg-info">${this.getReasonLabel(adj.reason)}</span></td>
                        </tr>
                        <tr>
                            <th>Catatan</th>
                            <td>${adj.notes || '-'}</td>
                        </tr>
                        <tr>
                            <th>Waktu Adjustment</th>
                            <td>${new Date(adj.adjusted_at).toLocaleString('id-ID')}</td>
                        </tr>
                        <tr>
                            <th>Disesuaikan Oleh</th>
                            <td>${adj.adjusted_by_name || '-'}</td>
                        </tr>
                    </table>
                `;
                
                document.getElementById('detailContent').innerHTML = detailHtml;
                const modal = document.getElementById('detailModal');
                modal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('View detail error:', error);
            Toast.error('Gagal memuat detail');
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    StockAdjustmentModule.init();
});

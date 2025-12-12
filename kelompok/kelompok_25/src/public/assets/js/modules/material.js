/**
 * Material Module
 * Handles all material CRUD operations with AJAX and image management
 */
const MaterialModule = {
    // State
    state: {
        materials: [],
        categories: [],
        suppliers: [],
        currentPage: 1,
        perPage: 10,
        totalPages: 1,
        searchKeyword: '',
        categoryFilter: '',
        statusFilter: '',
        editingId: null,
        deletingId: null,
        currentMaterialImages: []
    },

    // DOM Elements
    elements: {},

    /**
     * Initialize module
     */
    init() {
        console.log('MaterialModule.init() called');
        this.cacheElements();
        this.bindEvents();
        this.loadCategories();
        this.loadSuppliers();
        this.loadMaterials();
    },

    /**
     * Cache DOM elements
     */
    cacheElements() {
        this.elements = {
            searchInput: document.getElementById('searchInput'),
            categoryFilter: document.getElementById('categoryFilter'),
            statusFilter: document.getElementById('statusFilter'),
            btnAddMaterial: document.getElementById('btnAddMaterial'),
            materialTable: document.getElementById('materialTable'),
            paginationContainer: document.getElementById('paginationContainer'),
            loadingState: document.getElementById('loadingState'),
            emptyState: document.getElementById('emptyState'),
            
            // Modal elements
            materialModal: document.getElementById('materialModal'),
            modalTitle: document.getElementById('modalTitle'),
            materialForm: document.getElementById('materialForm'),
            materialId: document.getElementById('materialId'),
            materialCode: document.getElementById('materialCode'),
            materialName: document.getElementById('materialName'),
            categoryId: document.getElementById('categoryId'),
            supplierId: document.getElementById('supplierId'),
            unit: document.getElementById('unit'),
            minStock: document.getElementById('minStock'),
            currentStock: document.getElementById('currentStock'),
            imageUpload: document.getElementById('imageUpload'),
            imagePreview: document.getElementById('imagePreview'),
            submitText: document.getElementById('submitText'),
            submitSpinner: document.getElementById('submitSpinner'),
            btnSubmit: document.getElementById('btnSubmit'),
            
            // Delete modal elements
            deleteModal: document.getElementById('deleteModal'),
            deleteItemName: document.getElementById('deleteItemName'),
            btnConfirmDelete: document.getElementById('btnConfirmDelete'),
            deleteText: document.getElementById('deleteText'),
            deleteSpinner: document.getElementById('deleteSpinner'),

            // Image modal
            imageModal: document.getElementById('imageModal'),
            imageModalMaterialName: document.getElementById('imageModalMaterialName'),
            imageGrid: document.getElementById('imageGrid'),
            imageUploadForm: document.getElementById('imageUploadForm'),
            imageFile: document.getElementById('imageFile'),
            imageUploadBtn: document.getElementById('imageUploadBtn')
        };
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Search with debounce
        let searchTimeout;
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.state.searchKeyword = e.target.value;
                    this.state.currentPage = 1;
                    this.loadMaterials();
                }, 500);
            });
        }

        // Category filter
        if (this.elements.categoryFilter) {
            this.elements.categoryFilter.addEventListener('change', (e) => {
                this.state.categoryFilter = e.target.value;
                this.state.currentPage = 1;
                this.loadMaterials();
            });
        }

        // Status filter
        if (this.elements.statusFilter) {
            this.elements.statusFilter.addEventListener('change', (e) => {
                this.state.statusFilter = e.target.value;
                this.state.currentPage = 1;
                this.loadMaterials();
            });
        }

        // Add button
        if (this.elements.btnAddMaterial) {
            this.elements.btnAddMaterial.addEventListener('click', (e) => {
                e.preventDefault();
                this.openModalForCreate();
            });
        }

        // Form submit
        if (this.elements.materialForm) {
            this.elements.materialForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        // Image upload preview
        if (this.elements.imageUpload) {
            this.elements.imageUpload.addEventListener('change', (e) => {
                this.handleImagePreview(e.target.files);
            });
        }

        // Image form submit
        if (this.elements.imageUploadForm) {
            this.elements.imageUploadForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleImageUpload();
            });
        }

        // Close modals on backdrop click
        if (this.elements.materialModal) {
            this.elements.materialModal.addEventListener('click', (e) => {
                if (e.target === this.elements.materialModal) {
                    this.closeModal();
                }
            });
        }

        if (this.elements.deleteModal) {
            this.elements.deleteModal.addEventListener('click', (e) => {
                if (e.target === this.elements.deleteModal) {
                    this.closeDeleteModal();
                }
            });
        }

        if (this.elements.imageModal) {
            this.elements.imageModal.addEventListener('click', (e) => {
                if (e.target === this.elements.imageModal) {
                    this.closeImageModal();
                }
            });
        }
    },

    /**
     * Load categories for dropdown
     */
    async loadCategories() {
        try {
            const response = await ApiClient.get('/categories', { per_page: 100 });
            if (response.success) {
                this.state.categories = response.data.data || [];
                this.renderCategoryOptions();
            }
        } catch (error) {
            console.error('Load categories error:', error);
        }
    },

    /**
     * Load suppliers for dropdown
     */
    async loadSuppliers() {
        try {
            const response = await ApiClient.get('/suppliers', { per_page: 100 });
            if (response.success) {
                this.state.suppliers = response.data.data || [];
                this.renderSupplierOptions();
            }
        } catch (error) {
            console.error('Load suppliers error:', error);
        }
    },

    /**
     * Render category options
     */
    renderCategoryOptions() {
        if (!this.elements.categoryId) return;

        const options = this.state.categories.map(cat => 
            `<option value="${cat.id}">${this.escapeHtml(cat.name)}</option>`
        ).join('');

        this.elements.categoryId.innerHTML = '<option value="">Pilih Kategori</option>' + options;

        // Also update filter dropdown
        if (this.elements.categoryFilter) {
            this.elements.categoryFilter.innerHTML = '<option value="">Semua Kategori</option>' + options;
        }
    },

    /**
     * Render supplier options
     */
    renderSupplierOptions() {
        if (!this.elements.supplierId) return;

        const options = this.state.suppliers.map(sup => 
            `<option value="${sup.id}">${this.escapeHtml(sup.name)}</option>`
        ).join('');

        this.elements.supplierId.innerHTML = '<option value="">Pilih Supplier</option>' + options;
    },

    /**
     * Load materials from API
     */
    async loadMaterials() {
        this.showLoading();

        try {
            const params = {
                page: this.state.currentPage,
                per_page: this.state.perPage
            };

            if (this.state.searchKeyword) {
                params.search = this.state.searchKeyword;
            }

            if (this.state.categoryFilter) {
                params.category_id = this.state.categoryFilter;
            }

            if (this.state.statusFilter) {
                params.status = this.state.statusFilter;
            }

            const response = await ApiClient.get('/materials', params);

            if (response.success) {
                this.state.materials = response.data.data || response.data || [];
                this.state.currentPage = response.data.current_page || 1;
                this.state.totalPages = response.data.last_page || 1;

                this.renderMaterials();
                this.renderPagination();
            } else {
                throw new Error(response.message || 'Gagal memuat data');
            }
        } catch (error) {
            console.error('Load materials error:', error);
            Toast.error('Gagal Memuat Data', error.message);
            this.showEmpty('Gagal memuat data material');
        }
    },

    /**
     * Render materials table
     */
    renderMaterials() {
        if (this.state.materials.length === 0) {
            this.showEmpty(
                this.state.searchKeyword 
                    ? `Tidak ditemukan material dengan kata kunci "${this.state.searchKeyword}"`
                    : 'Belum ada material yang ditambahkan'
            );
            return;
        }

        const html = this.state.materials.map(material => {
            const stockStatus = this.getStockStatus(material);
            const statusBadge = this.getStatusBadge(stockStatus);
            
            return `
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            ${material.image_url ? 
                                `<img src="${material.image_url}" alt="${this.escapeHtml(material.name)}" class="h-12 w-12 rounded-xl object-cover border border-slate-200" />` :
                                `<span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 text-white font-semibold text-sm">
                                    ${this.getInitials(material.name)}
                                </span>`
                            }
                            <div>
                                <p class="font-semibold text-slate-800">${this.escapeHtml(material.name)}</p>
                                <p class="text-xs text-slate-400">${material.code || 'N/A'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-500">${this.escapeHtml(material.category_name || 'N/A')}</td>
                    <td class="px-6 py-4 font-semibold text-slate-800">${material.current_stock || 0} ${material.unit || ''}</td>
                    <td class="px-6 py-4 text-slate-500">${material.min_stock || 0} ${material.unit || ''}</td>
                    <td class="px-6 py-4 text-slate-500">${this.escapeHtml(material.supplier_name || 'N/A')}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="MaterialModule.openImageModal(${material.id}, '${this.escapeHtml(material.name)}')" class="p-2 text-purple-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Kelola Gambar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </button>
                            <button onclick="MaterialModule.openModalForEdit(${material.id})" class="p-2 text-blue-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            <button onclick="MaterialModule.openDeleteModal(${material.id}, '${this.escapeHtml(material.name)}')" class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        this.elements.materialTable.innerHTML = html;
        this.elements.loadingState.classList.add('hidden');
        this.elements.emptyState.classList.add('hidden');
        this.elements.materialTable.parentElement.parentElement.classList.remove('hidden');
    },

    /**
     * Get stock status
     */
    getStockStatus(material) {
        const current = parseFloat(material.current_stock) || 0;
        const min = parseFloat(material.min_stock) || 0;

        if (current > min) return 'Aman';
        if (current > 0) return 'Hampir Habis';
        return 'Habis';
    },

    /**
     * Get status badge HTML
     */
    getStatusBadge(status) {
        const badges = {
            'Aman': '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold bg-emerald-50 text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Aman</span>',
            'Hampir Habis': '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold bg-amber-50 text-amber-600"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Hampir Habis</span>',
            'Habis': '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold bg-rose-50 text-rose-500"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Habis</span>'
        };
        return badges[status] || badges['Habis'];
    },

    /**
     * Get initials from name
     */
    getInitials(name) {
        return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
    },

    /**
     * Render pagination
     */
    renderPagination() {
        if (this.state.totalPages <= 1) {
            this.elements.paginationContainer.innerHTML = '';
            return;
        }

        const pages = [];
        for (let i = 1; i <= this.state.totalPages; i++) {
            pages.push(i);
        }

        const html = pages.map(page => {
            const isActive = page === this.state.currentPage;
            return `
                <button
                    onclick="MaterialModule.goToPage(${page})"
                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all ${
                        isActive
                            ? 'bg-blue-500 text-white'
                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    }"
                >
                    ${page}
                </button>
            `;
        }).join('');

        this.elements.paginationContainer.innerHTML = `<nav class="flex items-center gap-2">${html}</nav>`;
    },

    /**
     * Go to page
     */
    goToPage(page) {
        this.state.currentPage = page;
        this.loadMaterials();
    },

    /**
     * Open modal for create
     */
    openModalForCreate() {
        this.state.editingId = null;
        this.clearForm();
        
        if (this.elements.modalTitle) {
            this.elements.modalTitle.textContent = 'Tambah Bahan Baku';
        }
        if (this.elements.submitText) {
            this.elements.submitText.textContent = 'Simpan';
        }
        
        this.elements.materialModal.classList.remove('hidden');
    },

    /**
     * Open modal for edit
     */
    async openModalForEdit(id) {
        try {
            this.showLoading();
            
            const response = await ApiClient.get(`/materials/${id}`);
            
            if (response.success) {
                const material = response.data.data || response.data;
                
                this.state.editingId = material.id;
                this.elements.materialId.value = material.id;
                this.elements.materialCode.value = material.code || '';
                this.elements.materialName.value = material.name;
                this.elements.categoryId.value = material.category_id || '';
                this.elements.supplierId.value = material.default_supplier_id || '';
                this.elements.unit.value = material.unit || '';
                this.elements.minStock.value = material.min_stock || 0;
                this.elements.currentStock.value = material.current_stock || 0;
                
                if (this.elements.modalTitle) {
                    this.elements.modalTitle.textContent = 'Edit Bahan Baku';
                }
                if (this.elements.submitText) {
                    this.elements.submitText.textContent = 'Perbarui';
                }
                
                this.elements.materialModal.classList.remove('hidden');
                this.loadMaterials();
            } else {
                Toast.error('Gagal Memuat', response.message);
            }
        } catch (error) {
            Toast.error('Error', error.message);
            this.loadMaterials();
        }
    },

    /**
     * Close modal
     */
    closeModal() {
        this.elements.materialModal.classList.add('hidden');
        this.clearForm();
    },

    /**
     * Clear form
     */
    clearForm() {
        this.elements.materialForm.reset();
        this.elements.materialId.value = '';
        this.elements.imagePreview.innerHTML = '';
        this.state.editingId = null;
        this.clearErrors();
    },

    /**
     * Handle image preview
     */
    handleImagePreview(files) {
        const preview = this.elements.imagePreview;
        preview.innerHTML = '';

        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'h-24 w-24 rounded-lg object-cover border-2 border-blue-200';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    },

    /**
     * Handle form submit
     */
    async handleSubmit() {
        const formData = new FormData(this.elements.materialForm);
        
        // Convert FormData to JSON for API
        const data = {
            name: formData.get('name'),
            category_id: parseInt(formData.get('category_id')),
            unit: formData.get('unit'),
            min_stock: parseFloat(formData.get('min_stock')) || 0
        };

        // Add optional fields only if they have values
        const code = formData.get('code');
        if (code && code.trim() !== '') {
            data.code = code.trim();
        }

        const supplierId = parseInt(formData.get('default_supplier_id'));
        if (supplierId && !isNaN(supplierId)) {
            data.default_supplier_id = supplierId;
        }

        const currentStock = parseFloat(formData.get('current_stock'));
        if (!isNaN(currentStock)) {
            data.current_stock = currentStock;
        }

        // Validation
        const errors = {};
        
        if (!data.name) errors.name = 'Nama bahan baku harus diisi';
        if (!data.category_id) errors.category_id = 'Kategori harus dipilih';
        if (!data.unit) errors.unit = 'Satuan harus diisi';
        if (data.min_stock < 0) errors.min_stock = 'Minimal stok tidak boleh negatif';

        if (Object.keys(errors).length > 0) {
            this.displayErrors(errors);
            return;
        }

        this.clearErrors();
        this.setSubmitLoading(true);

        try {
            let response;
            if (this.state.editingId) {
                response = await ApiClient.post(`/materials/${this.state.editingId}`, data);
            } else {
                response = await ApiClient.post('/materials', data);
            }

            if (response.success) {
                // Handle image upload if file selected
                const imageFile = this.elements.imageUpload.files[0];
                const materialId = response.data?.data?.id || response.data?.id;
                
                if (imageFile && materialId) {
                    await this.uploadMaterialImage(materialId, imageFile);
                }

                Toast.success('Berhasil!', response.message);
                this.closeModal();
                this.loadMaterials();
            } else if (response.errors) {
                this.displayErrors(response.errors);
                Toast.error('Validasi Gagal', response.message);
            } else {
                Toast.error('Error', response.message);
            }
        } catch (error) {
            if (error.errors && Object.keys(error.errors).length > 0) {
                this.displayErrors(error.errors);
                Toast.error('Validasi Gagal', error.message || 'Ada kesalahan pada form');
            } else {
                Toast.error('Error', error.message || 'Terjadi kesalahan');
            }
        } finally {
            this.setSubmitLoading(false);
        }
    },

    /**
     * Upload material image
     */
    async uploadMaterialImage(materialId, file) {
        try {
            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch(`/api/materials/${materialId}/images`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (!response.ok) {
                console.error('Image upload failed:', data);
            }
        } catch (error) {
            console.error('Image upload error:', error);
        }
    },

    /**
     * Open delete confirmation modal
     */
    openDeleteModal(id, name) {
        this.state.deletingId = id;
        this.elements.deleteItemName.textContent = name;
        this.elements.deleteModal.classList.remove('hidden');
    },

    /**
     * Close delete modal
     */
    closeDeleteModal() {
        this.elements.deleteModal.classList.add('hidden');
        this.state.deletingId = null;
    },

    /**
     * Confirm delete
     */
    async confirmDelete() {
        if (!this.state.deletingId) return;

        this.setDeleteLoading(true);

        try {
            const response = await ApiClient.post(`/materials/${this.state.deletingId}/delete`, {});

            if (response.success) {
                Toast.success('Berhasil!', response.message);
                this.closeDeleteModal();
                this.loadMaterials();
            } else {
                Toast.error('Error', response.message);
            }
        } catch (error) {
            Toast.error('Error', error.message);
        } finally {
            this.setDeleteLoading(false);
        }
    },

    /**
     * Open image modal
     */
    async openImageModal(materialId, materialName) {
        this.state.editingId = materialId;
        this.elements.imageModalMaterialName.textContent = materialName;
        this.elements.imageModal.classList.remove('hidden');
        this.loadMaterialImages(materialId);
    },

    /**
     * Close image modal
     */
    closeImageModal() {
        this.elements.imageModal.classList.add('hidden');
        this.elements.imageUploadForm.reset();
        this.state.editingId = null;
    },

    /**
     * Load material images
     */
    async loadMaterialImages(materialId) {
        try {
            const response = await ApiClient.get(`/materials/${materialId}/images`);
            console.log('Load images response:', response);
            
            if (response.success && response.data) {
                this.state.currentMaterialImages = response.data.data || [];
                console.log('Current images:', this.state.currentMaterialImages);
                this.renderImageGrid();
            } else {
                this.state.currentMaterialImages = [];
                this.renderImageGrid();
            }
        } catch (error) {
            console.error('Load images error:', error);
            this.state.currentMaterialImages = [];
            this.elements.imageGrid.innerHTML = '<p class="text-slate-500 text-center py-8">Gagal memuat gambar</p>';
        }
    },

    /**
     * Render image grid
     */
    renderImageGrid() {
        if (this.state.currentMaterialImages.length === 0) {
            this.elements.imageGrid.innerHTML = '<p class="text-slate-500 text-center py-8">Belum ada gambar</p>';
            return;
        }

        const html = this.state.currentMaterialImages.map(img => {
            const imageUrl = img.image_url || img.path || '';
            return `
            <div class="relative group">
                <img src="${imageUrl}" alt="${this.escapeHtml(img.filename)}" class="w-full h-32 object-cover rounded-lg border-2 ${img.is_primary ? 'border-blue-500' : 'border-slate-200'}" />
                ${img.is_primary ? '<div class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">Utama</div>' : ''}
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all rounded-lg flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                    ${!img.is_primary ? `<button onclick="MaterialModule.setPrimaryImage(${img.id})" class="p-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" title="Jadikan Utama"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></button>` : ''}
                    <button onclick="MaterialModule.deleteImage(${img.id})" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600" title="Hapus"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                </div>
            </div>
        `;
        }).join('');

        this.elements.imageGrid.innerHTML = html;
    },

    /**
     * Handle image upload to material
     */
    async handleImageUpload() {
        const file = this.elements.imageFile.files[0];
        if (!file) {
            Toast.error('Error', 'Pilih file gambar terlebih dahulu');
            return;
        }

        if (!file.type.startsWith('image/')) {
            Toast.error('Error', 'File harus berupa gambar');
            return;
        }

        this.elements.imageUploadBtn.disabled = true;
        this.elements.imageUploadBtn.textContent = 'Uploading...';

        try {
            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch(`/api/materials/${this.state.editingId}/images`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Toast.success('Berhasil!', 'Gambar berhasil diupload');
                this.elements.imageUploadForm.reset();
                this.loadMaterialImages(this.state.editingId);
                this.loadMaterials(); // Refresh material list to show new image
            } else {
                Toast.error('Error', data.message || 'Gagal upload gambar');
            }
        } catch (error) {
            Toast.error('Error', error.message);
        } finally {
            this.elements.imageUploadBtn.disabled = false;
            this.elements.imageUploadBtn.textContent = 'Upload';
        }
    },

    /**
     * Set primary image
     */
    async setPrimaryImage(imageId) {
        try {
            const response = await ApiClient.post(`/materials/images/${imageId}/set-primary`, {});

            if (response.success) {
                Toast.success('Berhasil!', 'Gambar utama berhasil diatur');
                this.loadMaterialImages(this.state.editingId);
                this.loadMaterials();
            } else {
                Toast.error('Error', response.message);
            }
        } catch (error) {
            Toast.error('Error', error.message);
        }
    },

    /**
     * Delete image
     */
    async deleteImage(imageId) {
        if (!confirm('Apakah Anda yakin ingin menghapus gambar ini?')) return;

        try {
            const response = await ApiClient.post(`/materials/images/${imageId}/delete`, {});

            if (response.success) {
                Toast.success('Berhasil!', 'Gambar berhasil dihapus');
                this.loadMaterialImages(this.state.editingId);
                this.loadMaterials();
            } else {
                Toast.error('Error', response.message);
            }
        } catch (error) {
            Toast.error('Error', error.message);
        }
    },

    /**
     * Show loading state
     */
    showLoading() {
        this.elements.loadingState.classList.remove('hidden');
        this.elements.emptyState.classList.add('hidden');
        this.elements.materialTable.parentElement.parentElement.classList.add('hidden');
        this.elements.paginationContainer.classList.add('hidden');
    },

    /**
     * Show empty state
     */
    showEmpty(message) {
        this.elements.emptyState.classList.remove('hidden');
        document.getElementById('emptyMessage').textContent = message;
        this.elements.loadingState.classList.add('hidden');
        this.elements.materialTable.parentElement.parentElement.classList.add('hidden');
        this.elements.paginationContainer.classList.add('hidden');
    },

    /**
     * Display validation errors
     */
    displayErrors(errors) {
        Object.keys(errors).forEach(field => {
            const errorEl = document.getElementById(`${field}Error`);
            if (errorEl) {
                const errorMessage = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                errorEl.textContent = errorMessage;
                errorEl.classList.remove('hidden');
            }
        });
    },

    /**
     * Clear validation errors
     */
    clearErrors() {
        document.querySelectorAll('[id$="Error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    },

    /**
     * Set submit button loading state
     */
    setSubmitLoading(loading) {
        this.elements.btnSubmit.disabled = loading;
        if (loading) {
            this.elements.submitText.classList.add('hidden');
            this.elements.submitSpinner.classList.remove('hidden');
        } else {
            this.elements.submitText.classList.remove('hidden');
            this.elements.submitSpinner.classList.add('hidden');
        }
    },

    /**
     * Set delete button loading state
     */
    setDeleteLoading(loading) {
        this.elements.btnConfirmDelete.disabled = loading;
        if (loading) {
            this.elements.deleteText.classList.add('hidden');
            this.elements.deleteSpinner.classList.remove('hidden');
        } else {
            this.elements.deleteText.classList.remove('hidden');
            this.elements.deleteSpinner.classList.add('hidden');
        }
    },

    /**
     * Escape HTML special characters
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

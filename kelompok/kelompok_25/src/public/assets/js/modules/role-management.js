/**
 * Role Management Module
 * Handles role CRUD operations and UI interactions
 */

const RoleManagement = (function() {
    let currentRoles = [];
    let allPermissions = [];
    let roleToDelete = null;
    let debounceTimer = null;

    function init() {
        loadRoles();
        loadPermissions();
        setupEventListeners();
    }

    function setupEventListeners() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    loadRoles(this.value);
                }, 500);
            });
        }
    }

    async function loadRoles(search = '') {
        const loadingState = document.getElementById('loadingState');
        const roleGrid = document.getElementById('roleGrid');
        const emptyState = document.getElementById('emptyState');

        try {
            loadingState.classList.remove('hidden');
            roleGrid.classList.add('hidden');
            emptyState.classList.add('hidden');

            const params = new URLSearchParams();
            if (search) params.append('search', search);

            const response = await fetch(`/api/roles?${params.toString()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            currentRoles = result.data.data || [];
            renderRoles();

        } catch (error) {
            console.error('Error loading roles:', error);
            showToast('error', 'Gagal', 'Gagal memuat data role');
        } finally {
            loadingState.classList.add('hidden');
        }
    }

    async function loadPermissions() {
        try {
            const response = await fetch('/api/permissions');
            const result = await response.json();

            if (result.success) {
                allPermissions = result.data || [];
            }
        } catch (error) {
            console.error('Error loading permissions:', error);
        }
    }

    function renderRoles() {
        const roleGrid = document.getElementById('roleGrid');
        const emptyState = document.getElementById('emptyState');

        if (currentRoles.length === 0) {
            roleGrid.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        roleGrid.classList.remove('hidden');
        emptyState.classList.add('hidden');

        const roleColors = {
            'admin': 'bg-gradient-to-br from-pink-500 to-purple-600',
            'owner': 'bg-gradient-to-br from-pink-500 to-purple-600',
            'manager': 'bg-gradient-to-br from-blue-400 to-blue-600',
            'staff_gudang': 'bg-gradient-to-br from-blue-400 to-blue-600',
            'staff': 'bg-gradient-to-br from-blue-400 to-blue-600',
            'keuangan': 'bg-gradient-to-br from-green-400 to-green-600'
        };

        roleGrid.innerHTML = currentRoles.map(role => {
            const colorClass = roleColors[role.code] || 'bg-gradient-to-br from-indigo-400 to-indigo-600';
            const statusClass = role.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            const statusText = role.is_active == 1 ? 'active' : 'inactive';
            
            return `
                <article class="rounded-2xl bg-white shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="${colorClass} p-6 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center text-2xl">
                                🔒
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">${escapeHtml(role.name)}</h3>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="RoleManagement.showEditModal(${role.id})" class="w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg flex items-center justify-center transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <button onclick="RoleManagement.showDeleteModal(${role.id})" class="w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg flex items-center justify-center transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-5 space-y-3">
                        <p class="text-sm text-slate-600">${escapeHtml(role.description || 'Tidak ada deskripsi')}</p>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 mb-2">User dengan role ini:</p>
                            <p class="text-2xl font-semibold text-slate-800">0 <span class="text-sm text-slate-500 font-normal">User</span></p>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 mb-2">Izin Akses:</p>
                            <p class="text-sm text-slate-700">Dashboard, Data Bahan Baku, Data Supplier</p>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 mb-2">Status:</p>
                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Role Baru';
        document.getElementById('roleId').value = '';
        document.getElementById('roleCode').value = '';
        document.getElementById('roleName').value = '';
        document.getElementById('roleDescription').value = '';
        document.getElementById('roleIsActive').checked = true;
        
        renderPermissions([]);
        document.getElementById('roleModal').classList.remove('hidden');
    }

    async function showEditModal(roleId) {
        try {
            const response = await fetch(`/api/roles/${roleId}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const role = result.data;
            document.getElementById('modalTitle').textContent = 'Edit Role';
            document.getElementById('roleId').value = role.id;
            document.getElementById('roleCode').value = role.code;
            document.getElementById('roleName').value = role.name;
            document.getElementById('roleDescription').value = role.description || '';
            document.getElementById('roleIsActive').checked = role.is_active == 1;

            renderPermissions(role.permission_ids || []);
            document.getElementById('roleModal').classList.remove('hidden');

        } catch (error) {
            console.error('Error loading role:', error);
            showToast('error', 'Gagal', 'Gagal memuat data role');
        }
    }

    function renderPermissions(selectedIds = []) {
        const container = document.getElementById('permissionsList');
        
        if (allPermissions.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500">Tidak ada permission tersedia</p>';
            return;
        }

        container.innerHTML = allPermissions.map(perm => {
            const checked = selectedIds.includes(perm.id) ? 'checked' : '';
            return `
                <label class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded cursor-pointer">
                    <input type="checkbox" name="permissions[]" value="${perm.id}" ${checked} class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800">${escapeHtml(perm.name)}</p>
                        <p class="text-xs text-slate-500">${escapeHtml(perm.code)}</p>
                    </div>
                </label>
            `;
        }).join('');
    }

    async function saveRole() {
        const roleId = document.getElementById('roleId').value;
        const code = document.getElementById('roleCode').value.trim();
        const name = document.getElementById('roleName').value.trim();
        const description = document.getElementById('roleDescription').value.trim();
        const isActive = document.getElementById('roleIsActive').checked ? 1 : 0;

        if (!code || !name) {
            showToast('error', 'Validasi Gagal', 'Kode dan nama role harus diisi');
            return;
        }

        const selectedPermissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked'))
            .map(cb => parseInt(cb.value));

        try {
            const url = roleId ? `/api/roles/${roleId}` : '/api/roles';
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    code,
                    name,
                    description,
                    is_active: isActive
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // Sync permissions if role was created/updated
            const savedRoleId = roleId || result.data.role_id;
            if (selectedPermissions.length > 0) {
                await syncPermissions(savedRoleId, selectedPermissions);
            }

            showToast('success', 'Berhasil', roleId ? 'Role berhasil diperbarui' : 'Role berhasil ditambahkan');
            hideModal();
            loadRoles();

        } catch (error) {
            console.error('Error saving role:', error);
            showToast('error', 'Gagal', error.message || 'Gagal menyimpan role');
        }
    }

    async function syncPermissions(roleId, permissionIds) {
        try {
            const response = await fetch(`/api/roles/${roleId}/permissions`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ permission_ids: permissionIds })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error syncing permissions:', error);
        }
    }

    function showDeleteModal(roleId) {
        roleToDelete = roleId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    async function confirmDelete() {
        if (!roleToDelete) return;

        try {
            const response = await fetch(`/api/roles/${roleToDelete}/delete`, {
                method: 'POST'
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('success', 'Berhasil', 'Role berhasil dihapus');
            hideDeleteModal();
            loadRoles();

        } catch (error) {
            console.error('Error deleting role:', error);
            showToast('error', 'Gagal', error.message || 'Gagal menghapus role');
        }
    }

    function hideModal() {
        document.getElementById('roleModal').classList.add('hidden');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        roleToDelete = null;
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

    function escapeHtml(text) {
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
        showCreateModal,
        showEditModal,
        showDeleteModal,
        saveRole,
        confirmDelete,
        hideModal,
        hideDeleteModal,
        hideToast
    };
})();

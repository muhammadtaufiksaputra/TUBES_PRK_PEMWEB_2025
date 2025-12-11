/**
 * User Management Module
 * Handles user CRUD operations and UI interactions
 */

const UserManagement = (function() {
    let currentUsers = [];
    let allRoles = [];
    let currentPage = 1;
    let totalPages = 1;
    let debounceTimer = null;
    let userToDeactivate = null;
    let userToResetPassword = null;

    function init() {
        loadRoles();
        loadUsers();
        setupEventListeners();
    }

    function setupEventListeners() {
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentPage = 1;
                    loadUsers();
                }, 500);
            });
        }

        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                currentPage = 1;
                loadUsers();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                currentPage = 1;
                loadUsers();
            });
        }

        if (prevPage) {
            prevPage.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadUsers();
                }
            });
        }

        if (nextPage) {
            nextPage.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadUsers();
                }
            });
        }
    }

    async function loadRoles() {
        try {
            const response = await fetch('/api/roles?per_page=100');
            const result = await response.json();

            if (result.success) {
                allRoles = result.data.data || [];
                renderRoleOptions();
            }
        } catch (error) {
            console.error('Error loading roles:', error);
        }
    }

    function renderRoleOptions() {
        const roleFilter = document.getElementById('roleFilter');
        const userRoleSelect = document.getElementById('userRole');

        if (roleFilter) {
            const filterOptions = allRoles.map(role => 
                `<option value="${role.id}">${escapeHtml(role.name)}</option>`
            ).join('');
            roleFilter.innerHTML = '<option value="">Semua Role</option>' + filterOptions;
        }

        if (userRoleSelect) {
            const selectOptions = allRoles.filter(r => r.is_active == 1).map(role => 
                `<option value="${role.id}">${escapeHtml(role.name)}</option>`
            ).join('');
            userRoleSelect.innerHTML = '<option value="">Pilih Role</option>' + selectOptions;
        }
    }

    async function loadUsers() {
        const loadingState = document.getElementById('loadingState');
        const userGrid = document.getElementById('userGrid');
        const emptyState = document.getElementById('emptyState');
        const pagination = document.getElementById('pagination');

        try {
            loadingState.classList.remove('hidden');
            userGrid.classList.add('hidden');
            emptyState.classList.add('hidden');
            pagination.classList.add('hidden');

            const params = new URLSearchParams();
            params.append('page', currentPage);
            params.append('per_page', 20);

            const search = document.getElementById('searchInput')?.value;
            const roleId = document.getElementById('roleFilter')?.value;
            const status = document.getElementById('statusFilter')?.value;

            if (search) params.append('search', search);
            if (roleId) params.append('role_id', roleId);
            if (status !== '') params.append('is_active', status);

            const response = await fetch(`/api/users?${params.toString()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            currentUsers = result.data.data || [];
            totalPages = result.data.last_page || 1;
            
            renderUsers();
            updatePagination(result.data);

        } catch (error) {
            console.error('Error loading users:', error);
            showToast('error', 'Gagal', 'Gagal memuat data user');
        } finally {
            loadingState.classList.add('hidden');
        }
    }

    function renderUsers() {
        const userGrid = document.getElementById('userGrid');
        const emptyState = document.getElementById('emptyState');

        if (currentUsers.length === 0) {
            userGrid.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        userGrid.classList.remove('hidden');
        emptyState.classList.add('hidden');

        const cardColors = [
            'bg-gradient-to-br from-blue-400 to-blue-600',
            'bg-gradient-to-br from-cyan-400 to-cyan-600',
            'bg-gradient-to-br from-sky-400 to-sky-600'
        ];

        userGrid.innerHTML = currentUsers.map((user, index) => {
            const colorClass = cardColors[index % cardColors.length];
            const statusClass = user.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            const statusText = user.is_active == 1 ? 'active' : 'inactive';
            const lastLogin = user.updated_at ? new Date(user.updated_at).toLocaleDateString('id-ID') : '-';
            
            return `
                <article class="rounded-2xl bg-white shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="${colorClass} p-6 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center text-2xl">
                                👤
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">${escapeHtml(user.name)}</h3>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="UserManagement.showEditModal(${user.id})" class="w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg flex items-center justify-center transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <button onclick="UserManagement.showDeactivateModal(${user.id})" class="w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg flex items-center justify-center transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <p class="text-xs text-slate-500">📧 Email:</p>
                            <p class="text-sm text-slate-700">${escapeHtml(user.email)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">📞 No. HP:</p>
                            <p class="text-sm text-slate-700">${escapeHtml(user.phone || '-')}</p>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 mb-1">Role:</p>
                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">
                                ${escapeHtml(user.role_name || 'No Role')}
                            </span>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 mb-1">Status:</p>
                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full ${statusClass}">${statusText}</span>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500">Last Login:</p>
                            <p class="text-sm text-slate-700">${lastLogin}</p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex gap-2">
                            <button onclick="UserManagement.showResetPasswordModal(${user.id})" class="flex-1 px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                Reset Password
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function updatePagination(data) {
        const pagination = document.getElementById('pagination');
        const showingStart = document.getElementById('showingStart');
        const showingEnd = document.getElementById('showingEnd');
        const totalUsers = document.getElementById('totalUsers');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');

        if (data.total > 0) {
            pagination.classList.remove('hidden');
            const start = (data.page - 1) * data.per_page + 1;
            const end = Math.min(data.page * data.per_page, data.total);
            
            showingStart.textContent = start;
            showingEnd.textContent = end;
            totalUsers.textContent = data.total;

            prevBtn.disabled = data.page <= 1;
            nextBtn.disabled = data.page >= data.last_page;
        } else {
            pagination.classList.add('hidden');
        }
    }

    function showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Tambah User Baru';
        document.getElementById('userId').value = '';
        document.getElementById('userName').value = '';
        document.getElementById('userEmail').value = '';
        document.getElementById('userPassword').value = '';
        document.getElementById('userRole').value = '';
        document.getElementById('userIsActive').checked = true;
        
        document.getElementById('passwordField').style.display = 'block';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('userPassword').required = true;
        document.getElementById('passwordHint').textContent = 'Minimal 6 karakter';

        document.getElementById('userModal').classList.remove('hidden');
    }

    async function showEditModal(userId) {
        try {
            const response = await fetch(`/api/users/${userId}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            const user = result.data;
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userRole').value = user.role_id || '';
            document.getElementById('userIsActive').checked = user.is_active == 1;

            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('userPassword').required = false;
            document.getElementById('passwordHint').textContent = 'Kosongkan jika tidak ingin mengubah password';

            document.getElementById('userModal').classList.remove('hidden');

        } catch (error) {
            console.error('Error loading user:', error);
            showToast('error', 'Gagal', 'Gagal memuat data user');
        }
    }

    async function saveUser() {
        const userId = document.getElementById('userId').value;
        const name = document.getElementById('userName').value.trim();
        const email = document.getElementById('userEmail').value.trim();
        const password = document.getElementById('userPassword').value;
        const roleId = document.getElementById('userRole').value;
        const isActive = document.getElementById('userIsActive').checked ? 1 : 0;

        if (!name || !email || !roleId) {
            showToast('error', 'Validasi Gagal', 'Nama, email, dan role harus diisi');
            return;
        }

        if (!userId && !password) {
            showToast('error', 'Validasi Gagal', 'Password harus diisi untuk user baru');
            return;
        }

        if (password && password.length < 6) {
            showToast('error', 'Validasi Gagal', 'Password minimal 6 karakter');
            return;
        }

        try {
            const payload = {
                name,
                email,
                role_id: parseInt(roleId),
                is_active: isActive
            };

            if (password) {
                payload.password = password;
            }

            const url = userId ? `/api/users/${userId}` : '/api/users';
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('success', 'Berhasil', userId ? 'User berhasil diperbarui' : 'User berhasil ditambahkan');
            hideModal();
            loadUsers();

        } catch (error) {
            console.error('Error saving user:', error);
            showToast('error', 'Gagal', error.message || 'Gagal menyimpan user');
        }
    }

    function showResetPasswordModal(userId) {
        userToResetPassword = userId;
        document.getElementById('resetUserId').value = userId;
        document.getElementById('newPassword').value = '';
        document.getElementById('newPasswordConfirmation').value = '';
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }

    async function confirmResetPassword() {
        const userId = document.getElementById('resetUserId').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmation = document.getElementById('newPasswordConfirmation').value;

        if (!newPassword || !confirmation) {
            showToast('error', 'Validasi Gagal', 'Password harus diisi');
            return;
        }

        if (newPassword.length < 6) {
            showToast('error', 'Validasi Gagal', 'Password minimal 6 karakter');
            return;
        }

        if (newPassword !== confirmation) {
            showToast('error', 'Validasi Gagal', 'Password tidak cocok');
            return;
        }

        try {
            const response = await fetch(`/api/users/${userId}/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    new_password: newPassword,
                    new_password_confirmation: confirmation
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('success', 'Berhasil', 'Password berhasil direset');
            hideResetPasswordModal();

        } catch (error) {
            console.error('Error resetting password:', error);
            showToast('error', 'Gagal', error.message || 'Gagal reset password');
        }
    }

    function showDeactivateModal(userId) {
        userToDeactivate = userId;
        document.getElementById('deactivateModal').classList.remove('hidden');
    }

    async function confirmDeactivate() {
        if (!userToDeactivate) return;

        try {
            const response = await fetch(`/api/users/${userToDeactivate}/deactivate`, {
                method: 'POST'
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            showToast('success', 'Berhasil', 'User berhasil dinonaktifkan');
            hideDeactivateModal();
            loadUsers();

        } catch (error) {
            console.error('Error deactivating user:', error);
            showToast('error', 'Gagal', error.message || 'Gagal menonaktifkan user');
        }
    }

    function hideModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    function hideResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
        userToResetPassword = null;
    }

    function hideDeactivateModal() {
        document.getElementById('deactivateModal').classList.add('hidden');
        userToDeactivate = null;
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
        showCreateModal,
        showEditModal,
        showResetPasswordModal,
        showDeactivateModal,
        saveUser,
        confirmResetPassword,
        confirmDeactivate,
        hideModal,
        hideResetPasswordModal,
        hideDeactivateModal,
        hideToast
    };
})();

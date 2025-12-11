<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Profil Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .tab-active {
            color: #2563eb;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-blue-50">

<!-- ================= HEADER PROFIL ================= -->

<div class="max-w-5xl mx-auto mt-8 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-xl p-8 shadow">
    <div class="flex items-center gap-6">
        <!-- Avatar -->
        <div class="w-24 h-24 rounded-full bg-white text-blue-600 flex items-center justify-center text-4xl font-bold shadow">
            A
        </div>

        <div class="flex-1">
            <h1 class="text-2xl font-semibold">Admin User</h1>
            <p class="opacity-90">scss2wfgcgf@gjsdjs.ck</p>

            <p class="opacity-90 mt-2">
                Bergabung sejak  
                <span class="font-semibold">15 Januari 2024</span>
            </p>
        </div>
    </div>
</div>

<!-- ================= NAVIGATION TAB ================= -->

<div class="max-w-5xl mx-auto mt-6">
    <div class="bg-white p-4 rounded-xl shadow flex gap-8 border">

        <button id="tab-profil" class="tab-active">Edit Profil</button>
        <button id="tab-password" class="text-gray-600 hover:text-blue-600">Ganti Password</button>
        <button id="tab-log" class="text-gray-600 hover:text-blue-600">Aktivitas Log</button>

    </div>
</div>

<!-- ============================================================= -->
<!-- ===================== EDIT PROFIL (DEFAULT) ================= -->
<!-- ============================================================= -->

<div id="content-profil" class="max-w-5xl mx-auto mt-6 bg-white rounded-xl shadow p-6">

    <h2 class="text-xl font-semibold mb-4">Edit Profil</h2>

    <form action="#" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <label class="block font-medium mb-1">Nama Lengkap</label>
            <input type="text" value="Admin User" class="w-full border rounded-lg p-3">
        </div>

        <div>
            <label class="block font-medium mb-1">Email</label>
            <input type="email" value="scss2wfgcgf@gjsdjs.ck" class="w-full border rounded-lg p-3">
        </div>

        <div>
            <label class="block font-medium mb-1">No. Telepon</label>
            <input type="text" value="0812-3456-7890" class="w-full border rounded-lg p-3">
        </div>

        <div>
            <label class="block font-medium mb-1">Perusahaan</label>
            <input type="text" value="PT Inventory Sejahtera" class="w-full border rounded-lg p-3">
        </div>

        <div class="md:col-span-2">
            <label class="block font-medium mb-1">Alamat</label>
            <textarea class="w-full border rounded-lg p-3 h-28"></textarea>
        </div>

    </form>

    <button class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
        Simpan Perubahan
    </button>

</div>

<!-- ============================================================= -->
<!-- ===================== GANTI PASSWORD ========================= -->
<!-- ============================================================= -->

<div id="content-password" class="max-w-5xl mx-auto mt-6 bg-white rounded-xl shadow p-6 hidden">

    <h2 class="text-xl font-semibold mb-4">Ganti Password</h2>

    <form method="POST" class="grid grid-cols-1 gap-5">

        <div>
            <label class="block font-medium mb-1">Password Lama</label>
            <input type="password" class="w-full border rounded-lg p-3">
        </div>

        <div>
            <label class="block font-medium mb-1">Password Baru</label>
            <input type="password" class="w-full border rounded-lg p-3">
        </div>

        <div>
            <label class="block font-medium mb-1">Konfirmasi Password Baru</label>
            <input type="password" class="w-full border rounded-lg p-3">
        </div>

    </form>

    <button class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
        Simpan Password Baru
    </button>

</div>

<!-- ============================================================= -->
<!-- ======================== AKTIVITAS LOG ======================= -->
<!-- ============================================================= -->

<div id="content-log" class="max-w-5xl mx-auto mt-6 bg-white rounded-xl shadow p-6 hidden mb-20">

    <h2 class="text-xl font-semibold mb-4">Aktivitas Log</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">

            <thead>
                <tr class="border-b bg-gray-100">
                    <th class="p-3 font-medium">Tanggal</th>
                    <th class="p-3 font-medium">Aktivitas</th>
                    <th class="p-3 font-medium">IP Address</th>
                </tr>
            </thead>

            <tbody class="text-gray-700">
                <tr class="border-b">
                    <td class="p-3">2025-01-10 17:31</td>
                    <td class="p-3">Login ke sistem</td>
                    <td class="p-3">192.168.1.22</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">2025-01-09 09:15</td>
                    <td class="p-3">Mengubah data profil</td>
                    <td class="p-3">192.168.1.22</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">2025-01-08 18:40</td>
                    <td class="p-3">Logout</td>
                    <td class="p-3">192.168.1.22</td>
                </tr>
            </tbody>

        </table>
    </div>

</div>

<!-- ================= SCRIPT TAB SWITCH ================= -->

<script>
    const tabProfil = document.getElementById("tab-profil");
    const tabPassword = document.getElementById("tab-password");
    const tabLog = document.getElementById("tab-log");

    const contentProfil = document.getElementById("content-profil");
    const contentPassword = document.getElementById("content-password");
    const contentLog = document.getElementById("content-log");

    function resetTabs() {
        tabProfil.classList.remove("tab-active");
        tabPassword.classList.remove("tab-active");
        tabLog.classList.remove("tab-active");

        contentProfil.classList.add("hidden");
        contentPassword.classList.add("hidden");
        contentLog.classList.add("hidden");
    }

    tabProfil.onclick = () => {
        resetTabs();
        tabProfil.classList.add("tab-active");
        contentProfil.classList.remove("hidden");
    };

    tabPassword.onclick = () => {
        resetTabs();
        tabPassword.classList.add("tab-active");
        contentPassword.classList.remove("hidden");
    };

    tabLog.onclick = () => {
        resetTabs();
        tabLog.classList.add("tab-active");
        contentLog.classList.remove("hidden");
    };
</script>

</body>
</html>

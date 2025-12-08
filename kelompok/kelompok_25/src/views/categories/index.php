<?php
$categories = [
    ['name' => 'Tepung', 'desc' => 'Berbagai jenis tepung untuk bahan dasar', 'total' => 12, 'color' => 'from-blue-500 to-blue-400'],
    ['name' => 'Gula', 'desc' => 'Pemanis untuk produk', 'total' => 8, 'color' => 'from-purple-500 to-fuchsia-500'],
    ['name' => 'Minyak', 'desc' => 'Minyak goreng dan bahan lemak', 'total' => 6, 'color' => 'from-pink-500 to-rose-500'],
    ['name' => 'Coklat', 'desc' => 'Coklat bubuk dan batangan', 'total' => 10, 'color' => 'from-orange-500 to-amber-500'],
    ['name' => 'Dairy', 'desc' => 'Susu, mentega, keju', 'total' => 15, 'color' => 'from-emerald-500 to-green-500'],
    ['name' => 'Telur', 'desc' => 'Telur ayam dan telur puyuh', 'total' => 4, 'color' => 'from-red-400 to-rose-400'],
];
?>

<section class="p-6 md:p-10 space-y-6">
    <!-- HEADER -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Kategori Bahan</h1>
        <p class="text-sm text-slate-500">Kelola kategori untuk mengelompokkan bahan baku</p>
    </div>

    <!-- SEARCH & ADD -->
    <div class="flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
        <div class="relative flex-1">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
            </span>
            <input type="text" placeholder="Cari kategori..." class="w-full rounded-2xl border border-slate-200 pl-12 pr-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
        </div>

        <button class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-green-500 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
            </svg>
            Tambah Kategori
        </button>
    </div>

    <!-- GRID KATEGORI -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($categories as $cat): ?>
            <article class="rounded-3xl border border-slate-100 bg-white shadow-sm p-5 flex flex-col gap-4">
                
                <!-- HEADER CARD -->
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-2xl text-white flex items-center justify-center bg-gradient-to-br <?= $cat['color'] ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6c0 .9.3 1.8.9 2.5l7.6 8.5c.6.7 1.7.8 2.4.2L21 19.4c.6-.6.9-1.5.9-2.4V7c0-1.7-1.3-3-3-3H6C4.3 4 3 5.3 3 7z" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-slate-900"><?= e($cat['name']) ?></h2>
                        <p class="text-sm text-slate-500 mt-1"><?= e($cat['desc']) ?></p>
                    </div>

                    <!-- ACTION -->
                    <div class="flex items-center gap-3 text-slate-400">
                        <button title="Edit" class="hover:text-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5h2m-1 14v-4m0 0l-4-4m4 4l4-4" />
                            </svg>
                        </button>
                        <button title="Hapus" class="hover:text-rose-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4h6v3m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 11h18M3 15h18" />
                        </svg>
                    </span>
                    <?= e($cat['total']) ?> Bahan
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

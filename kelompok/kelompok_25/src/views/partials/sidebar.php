<?php
$menuSections = [
    [
        'label' => 'Dashboard',
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'chart-bar', 'href' => url('/dashboard')],
        ]
    ],
    [
        'label' => 'Data Master',
        'items' => [
            ['label' => 'Bahan Baku', 'icon' => 'cube', 'href' => url('/materials')],
            ['label' => 'Supplier', 'icon' => 'truck', 'href' => url('/suppliers')],
            ['label' => 'Kategori', 'icon' => 'tag', 'href' => url('/categories')],
        ]
    ],
    [
        'label' => 'Transaksi Stok',
        'items' => [
            ['label' => 'Stok Masuk', 'icon' => 'arrow-down', 'href' => url('/stock-in')],
            ['label' => 'Stok Keluar', 'icon' => 'arrow-up', 'href' => url('/stock-out')],
            ['label' => 'Penyesuaian Stok', 'icon' => 'adjustments', 'href' => url('/stock-adjustments')],
        ]
    ],
    [
        'label' => 'Laporan',
        'items' => [
            ['label' => 'Laporan Stok', 'icon' => 'document', 'href' => url('/reports/stock')],
            ['label' => 'Laporan Transaksi', 'icon' => 'document-text', 'href' => url('/reports/transactions')],
            ['label' => 'Bahan Hampir Habis', 'icon' => 'warning', 'href' => url('/reports/low-stock')],
        ]
    ],
    [
        'label' => 'Pengaturan',
        'items' => [
            ['label' => 'Manajemen Role', 'icon' => 'shield', 'href' => url('/roles')],
            ['label' => 'Profil Saya', 'icon' => 'user', 'href' => url('/profile')],
        ]
    ],
];

function renderSidebarIcon($icon)
{
    $icons = [
        'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13h4v8H3zm7-6h4v14h-4zm7-4h4v18h-4z" />',
        'cube' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7l-9-4-9 4 9 4 9-4zm0 0v10l-9 4-9-4V7" />',
        'truck' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7h10v10H3zM13 7h5l3 4v6h-8zM5 21a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" />',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 7l-4 4v6h6l10-10-6-6L7 7z" />',
        'arrow-down' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l6-6m-6 6l-6-6" />',
        'arrow-up' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l6 6m-6-6l-6 6" />',
        'adjustments' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12V4m0 0l-2 2m2-2l2 2m4 10v-8m0 0l-2 2m2-2l2 2M5 20v-4m0 0l-2 2m2-2l2 2" />',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 2h8l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2z" />',
        'document-text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h6l4 4v10a2 2 0 01-2 2z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.93 19h14.14a2 2 0 001.73-3l-7.07-12a2 2 0 00-3.46 0l-7.07 12a2 2 0 001.73 3z" />',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />',
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 10-8 0m8 0v4H8v-4m8 0H8" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />',
    ];

    return $icons[$icon] ?? $icons['document'];
}
?>

<aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
    <div class="px-6 py-5 border-b border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Inventory</p>
        <p class="text-lg font-semibold text-gray-900 mt-1">Stok Bahan Baku</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-5">
        <?php foreach ($menuSections as $section): ?>
            <div>
                <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"><?= e($section['label']) ?></p>
                <div class="space-y-1">
                    <?php foreach ($section['items'] as $item): ?>
                        <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition sidebar-link">
                            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <?= renderSidebarIcon($item['icon']) ?>
                                </svg>
                            </span>
                            <?= e($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="px-6 py-5 border-t border-gray-200">
        <form action="<?= url('/logout') ?>" method="POST" class="w-full" onsubmit="return confirm('Apakah Anda yakin ingin logout?');">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 transition">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-gray-500 group-hover:bg-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </span>
                Logout
            </button>
        </form>
    </div>
</aside>

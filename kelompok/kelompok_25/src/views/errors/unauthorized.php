<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-20 w-20 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-4">403 - Akses Ditolak</h1>
            
            <p class="text-gray-600 mb-6">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>
            
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 text-left">
                    <p class="text-red-700"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
            
            <div class="space-y-3">
                <?php if (is_logged_in()): ?>
                    <a href="<?= url('/') ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                        Kembali ke Beranda
                    </a>
                    <form method="POST" action="<?= url('/logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded transition">
                            Logout
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

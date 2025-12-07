<?php
// index.php
// Simple Category Manager using SQLite + Tailwind Play CDN
// Usage: put in web root. Ensure php has write permission to this folder.

$dbFile = __DIR__ . '/data.sqlite';
$initDb = !file_exists($dbFile);

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($initDb) {
        // create table
        $pdo->exec("CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            tag_color TEXT DEFAULT '#e2e8f0',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // seed sample
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, tag_color) VALUES (?, ?, ?)");
        $samples = [
            ['Tepung', 'Berbagai jenis tepung untuk bahan dasar', '#dbeafe'],
            ['Gula', 'Pemanis untuk produk', '#fce7f3'],
            ['Minyak', 'Minyak goreng dan bahan lemak', '#fee2e2'],
            ['Coklat', 'Coklat bubuk dan batangan', '#fff7ed'],
            ['Dairy', 'Susu, mentega, keju', '#ecfccb'],
            ['Telur', 'Telur ayam dan telur puyuh', '#fee9e7'],
        ];
        foreach ($samples as $s) $stmt->execute($s);
    }
} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// Helper: escape
function e($s) { return htmlspecialchars($s, ENT_QUOTES); }

// Handle actions: add, edit, delete
$action = $_POST['action'] ?? '';
if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $color = trim($_POST['tag_color'] ?? '#e2e8f0');
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, tag_color) VALUES (:name, :desc, :color)");
        $stmt->execute([':name'=>$name, ':desc'=>$desc, ':color'=>$color]);
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"],'?'));
    exit;
}
if ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $color = trim($_POST['tag_color'] ?? '#e2e8f0');
    if ($id && $name !== '') {
        $stmt = $pdo->prepare("UPDATE categories SET name=:name, description=:desc, tag_color=:color WHERE id=:id");
        $stmt->execute([':name'=>$name, ':desc'=>$desc, ':color'=>$color, ':id'=>$id]);
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"],'?'));
    exit;
}
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
    header('Location: ' . strtok($_SERVER["REQUEST_URI"],'?'));
    exit;
}

// Fetch categories with optional search
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE name LIKE :q OR description LIKE :q ORDER BY id DESC");
    $stmt->execute([':q' => "%$q%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
}
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Inventory Manager — Kategori</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* small custom tweaks */
    .sidebar-collapsed { width: 72px; }
    .transition-smooth { transition: all .25s ease; }
    /* card hover */
    .card-hover:hover { box-shadow: 0 8px 18px rgba(15,23,42,0.08); transform: translateY(-3px); }
    /* modal backdrop */
    .backdrop { background: rgba(2,6,23,0.5); }
    /* small icon box */
    .tag-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center; }
  </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">

  <div id="app" class="flex h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white border-r border-slate-200 w-64 transition-smooth">
      <div class="px-4 py-5 flex items-center justify-between border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="bg-indigo-600 text-white rounded-lg p-2">
            <!-- logo -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
          </div>
          <div>
            <div class="text-sm font-semibold">Inventory Manager</div>
            <div class="text-xs text-slate-400">Sistem Manajemen Stok Bahan Baku</div>
          </div>
        </div>
        <button id="collapseBtn" class="text-slate-500 hover:text-slate-800 p-1 rounded-md">
          <svg xmlns="http://www.w3.org/2000/svg" id="collapseIcon" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M6 6L14 10L6 14V6Z" />
          </svg>
        </button>
      </div>

      <nav class="p-3">
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
          <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v4H3zM3 11h18v10H3z" /></svg>
          <span class="text-sm font-medium">Dashboard</span>
        </a>

        <div class="mt-3 text-xs text-slate-400 uppercase px-3">Data Master</div>
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
          <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none"><path d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span class="text-sm">Bahan Baku</span>
        </a>

        <!-- active -->
        <a id="menuKategori" href="?page=kategori" class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md bg-gradient-to-r from-blue-500 to-violet-500 text-white shadow">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span class="text-sm font-semibold">Kategori</span>
        </a>

        <div class="mt-6 text-xs text-slate-400 uppercase px-3">Transaksi Stok</div>
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
          <span class="text-sm">Stok Masuk</span>
        </a>

        <div class="mt-6 text-xs text-slate-400 uppercase px-3">Laporan</div>
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
          <span class="text-sm">Laporan Stok</span>
        </a>

        <div class="mt-6 px-3">
          <a href="#" class="text-sm text-rose-600 hover:underline">Logout</a>
        </div>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-6 overflow-auto">
      <div class="max-w-7xl mx-auto">
        <!-- header -->
        <div class="flex items-start justify-between gap-4 mb-6">
          <div>
            <h1 class="text-2xl font-semibold">Kategori Bahan</h1>
            <p class="text-sm text-slate-500">Kelola kategori untuk mengelompokkan bahan baku</p>
          </div>
          <div class="flex items-center gap-3">
            <form method="get" class="flex items-center gap-2">
              <label for="search" class="sr-only">Cari</label>
              <input id="search" name="q" value="<?= e($q) ?>" placeholder="Cari kategori..." class="px-4 py-2 rounded-lg border border-slate-200 bg-white shadow-sm w-64" />
            </form>
            <button id="addBtn" class="bg-emerald-500 text-white px-4 py-2 rounded-lg shadow hover:brightness-95">+ Tambah Kategori</button>
          </div>
        </div>

        <!-- cards grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <?php foreach ($categories as $cat): ?>
            <div class="bg-white rounded-xl p-4 border border-slate-100 card-hover transition-smooth relative">
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="tag-icon" style="background: <?= e($cat['tag_color']) ?>;">
                    <!-- tag icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none"><path d="M3 7v6c0 .9.3 1.8.9 2.5l7.6 8.5c.6.7 1.7.8 2.4.2L21 19.4c.6-.6.9-1.5.9-2.4V7c0-1.7-1.3-3-3-3H6C4.3 4 3 5.3 3 7z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                  <div>
                    <div class="text-base font-semibold"><?= e($cat['name']) ?></div>
                    <div class="text-xs text-slate-400 mt-1"><?= e($cat['description']) ?></div>
                  </div>
                </div>
                <div class="flex items-start gap-2">
                  <button class="editBtn p-2 rounded-md hover:bg-slate-50" data-id="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>" data-desc="<?= e($cat['description']) ?>" data-color="<?= e($cat['tag_color']) ?>" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6L20 4l-6 6M4 21v-3.5a2 2 0 01.586-1.414L14 7l4 4L8.586 20.586A2 2 0 017.172 21H4z"/></svg>
                  </button>
                  <form method="post" class="inline" onsubmit="return confirm('Hapus kategori <?= addslashes($cat['name']) ?>?');">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>" />
                    <button type="submit" class="p-2 rounded-md hover:bg-slate-50" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </form>
                </div>
              </div>

              <div class="mt-4 text-xs text-slate-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 11h18M3 15h18"/></svg>
                <div>-- Metadata --</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- footer small -->
        <div class="mt-6 text-sm text-slate-400">Menampilkan <?= count($categories) ?> kategori</div>
      </div>
    </main>
  </div>

  <!-- Add Modal -->
  <div id="modalAdd" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="fixed inset-0 backdrop"></div>
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Tambah Kategori</h3>
        <button onclick="closeAdd()" class="text-slate-500 hover:text-slate-800">✕</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="add" />
        <div class="mb-3">
          <label class="text-sm">Nama Kategori</label>
          <input name="name" required class="w-full mt-1 px-3 py-2 border rounded-lg" />
        </div>
        <div class="mb-3">
          <label class="text-sm">Deskripsi</label>
          <textarea name="description" rows="3" class="w-full mt-1 px-3 py-2 border rounded-lg"></textarea>
        </div>
        <div class="mb-4">
          <label class="text-sm">Warna Tag</label>
          <input type="color" name="tag_color" value="#e2e8f0" class="w-20 h-10 block mt-1" />
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeAdd()" class="px-4 py-2 rounded-lg border">Batal</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-500 text-white">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="fixed inset-0 backdrop"></div>
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Edit Kategori</h3>
        <button onclick="closeEdit()" class="text-slate-500 hover:text-slate-800">✕</button>
      </div>
      <form method="post" id="editForm">
        <input type="hidden" name="action" value="edit" />
        <input type="hidden" name="id" id="edit_id" />
        <div class="mb-3">
          <label class="text-sm">Nama Kategori</label>
          <input name="name" id="edit_name" required class="w-full mt-1 px-3 py-2 border rounded-lg" />
        </div>
        <div class="mb-3">
          <label class="text-sm">Deskripsi</label>
          <textarea name="description" id="edit_description" rows="3" class="w-full mt-1 px-3 py-2 border rounded-lg"></textarea>
        </div>
        <div class="mb-4">
          <label class="text-sm">Warna Tag</label>
          <input type="color" name="tag_color" id="edit_color" value="#e2e8f0" class="w-20 h-10 block mt-1" />
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeEdit()" class="px-4 py-2 rounded-lg border">Batal</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white">Perbarui</button>
        </div>
      </form>
    </div>
  </div>

<script>
  // Sidebar collapse logic
  const sidebar = document.getElementById('sidebar');
  const collapseBtn = document.getElementById('collapseBtn');
  const collapseIcon = document.getElementById('collapseIcon');
  let collapsed = false;
  collapseBtn.addEventListener('click', () => {
    collapsed = !collapsed;
    if (collapsed) {
      sidebar.style.width = '72px';
      // hide labels
      sidebar.querySelectorAll('span, .text-xs, .text-sm').forEach(el => {
        if (!el.classList.contains('sr-only')) el.style.display = 'none';
      });
      collapseIcon.style.transform = 'rotate(180deg)';
      document.querySelectorAll('#app main').forEach(el => el.style.marginLeft = '72px');
    } else {
      sidebar.style.width = '';
      sidebar.querySelectorAll('span, .text-xs, .text-sm').forEach(el => el.style.display = '');
      collapseIcon.style.transform = '';
      document.querySelectorAll('#app main').forEach(el => el.style.marginLeft = '');
    }
  });

  // Modal logic
  const modalAdd = document.getElementById('modalAdd');
  const addBtn = document.getElementById('addBtn');
  addBtn.addEventListener('click', () => {
    modalAdd.classList.remove('hidden');
    modalAdd.classList.add('flex');
  });
  function closeAdd(){
    modalAdd.classList.add('hidden');
    modalAdd.classList.remove('flex');
  }

  const modalEdit = document.getElementById('modalEdit');
  function openEdit(data){
    modalEdit.classList.remove('hidden'); modalEdit.classList.add('flex');
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_description').value = data.desc;
    document.getElementById('edit_color').value = data.color || '#e2e8f0';
  }
  function closeEdit(){ modalEdit.classList.add('hidden'); modalEdit.classList.remove('flex'); }

  // attach edit button listeners
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.dataset.id;
      const name = btn.dataset.name;
      const desc = btn.dataset.desc;
      const color = btn.dataset.color;
      openEdit({id,name,desc,color});
    });
  });

  // Basic keyboard short-cuts
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAdd(); closeEdit();
    }
  });

  // (Optional) simple client-side confirmation for delete handled by form onsubmit already.
</script>

</body>
</html>

# Super Admin Infrastructure & Content Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) atau superpowers:executing-plans untuk mengimplementasikan rencana ini task-by-task. Gunakan sintaks checkbox (`- [ ]`) untuk tracking.

**Goal:** Membangun infrastruktur Super Admin Dashboard yang benar-benar terpisah dari UI publik (landing page), memiliki tata letak profesional yang *compact*, mendukung tema Dark/Light mode, dan memiliki fondasi untuk manajemen konten website.

**Architecture:** 
- **Controller Layer:** Menambahkan class `Admin_Controller` di `MY_Controller` untuk memproteksi semua rute admin. Membuat controller `Admin_Dashboard` dan `Admin_Content`.
- **View Layer:** Membuat sub-direktori `application/views/admin/layouts` yang memuat `head.php`, `sidebar.php`, `topbar.php`, dan `footer.php`. UI akan menggunakan Tailwind CSS dan Alpine.js untuk *state management* (sidebar toggle & theme switcher) tanpa memengaruhi tampilan publik.

**Tech Stack:** CodeIgniter 3, Tailwind CSS, Alpine.js, PHP 7.4/8.0.

---

### Task 1: Create Admin Base Controller

**Files:**
- Modify: `application/core/MY_Controller.php`

- [ ] **Step 1: Modify `MY_Controller.php` to add `Admin_Controller` class**

Buka file dan tambahkan kelas `Admin_Controller` di bagian paling bawah.

```php
// application/core/MY_Controller.php

class Admin_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        // Redirect jika belum login atau bukan admin
        if (!$this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Administrator.');
            redirect('Auth/login');
        }
    }

    // Custom render untuk layout khusus admin
    protected function render_admin($view, $data = []) {
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }
}
```

- [ ] **Step 2: Commit changes**

```bash
git add application/core/MY_Controller.php
git commit -m "feat(admin): add Admin_Controller base class for RBAC"
```

---

### Task 2: Create Professional Admin Layouts (Dark/Light Support)

**Files:**
- Create: `application/views/admin/index.php`
- Create: `application/views/admin/layouts/head.php`
- Create: `application/views/admin/layouts/sidebar.php`
- Create: `application/views/admin/layouts/topbar.php`
- Create: `application/views/admin/layouts/footer.php`

- [ ] **Step 1: Create Admin Head**

```php
<!-- application/views/admin/layouts/head.php -->
<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>Super Admin PKP</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        admin: {
                            50: '#f8fafc',
                            800: '#1e293b',
                            900: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-admin-900 dark:text-gray-200 transition-colors duration-200 flex h-screen overflow-hidden">
```

- [ ] **Step 2: Create Compact Sidebar**

```php
<!-- application/views/admin/layouts/sidebar.php -->
<aside class="w-64 bg-white dark:bg-admin-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300 relative z-20" :class="{ '-translate-x-full absolute': !sidebarOpen, 'translate-x-0 static': sidebarOpen }">
    <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700">
        <h1 class="text-xl font-bold text-blue-600 dark:text-blue-400">Admin PKP</h1>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <a href="<?= base_url('Admin_Dashboard') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="ph ph-squares-four text-xl mr-3"></i> Dashboard
        </a>
        <a href="<?= base_url('Admin_Content') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="ph ph-article text-xl mr-3"></i> Kelola Konten
        </a>
        <a href="<?= base_url('Admin_Users') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="ph ph-users text-xl mr-3"></i> Pengguna
        </a>
        <a href="<?= base_url('Admin_Queue') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="ph ph-list-checks text-xl mr-3"></i> Antrean & Validasi
        </a>
    </nav>
</aside>
```

- [ ] **Step 3: Create Topbar with Theme Switcher**

```php
<!-- application/views/admin/layouts/topbar.php -->
<header class="h-16 bg-white dark:bg-admin-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-6 z-10">
    <div class="flex items-center">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
            <i class="ph ph-list text-2xl"></i>
        </button>
    </div>
    
    <div class="flex items-center space-x-4">
        <!-- Theme Toggle -->
        <button @click="darkMode = !darkMode" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="ph ph-sun text-xl" x-show="!darkMode"></i>
            <i class="ph ph-moon text-xl" x-show="darkMode" style="display: none;"></i>
        </button>
        
        <!-- User Menu -->
        <div class="flex items-center space-x-3">
            <img src="<?= $this->session->userdata('avatar') ?: 'https://ui-avatars.com/api/?name='.urlencode($this->session->userdata('name')) ?>" alt="Avatar" class="h-8 w-8 rounded-full border border-gray-300 dark:border-gray-600">
            <span class="text-sm font-medium"><?= html_escape($this->session->userdata('name')) ?></span>
            <a href="<?= base_url('Auth/logout') ?>" class="text-red-500 hover:text-red-600 ml-4" title="Logout">
                <i class="ph ph-sign-out text-xl"></i>
            </a>
        </div>
    </div>
</header>
```

- [ ] **Step 4: Create Footer & Master Index**

```php
<!-- application/views/admin/layouts/footer.php -->
<footer class="mt-auto py-4 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
    &copy; <?= date('Y') ?> Dinas Perumahan Rakyat & Kawasan Permukiman Provinsi Jawa Tengah
</footer>
</body>
</html>
```

```php
<!-- application/views/admin/index.php -->
<?php $this->load->view('admin/layouts/head'); ?>

<div x-data="{ sidebarOpen: true }" class="flex h-screen w-full bg-gray-50 dark:bg-admin-900">
    <!-- Sidebar -->
    <?php $this->load->view('admin/layouts/sidebar'); ?>
    
    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Topbar -->
        <?php $this->load->view('admin/layouts/topbar'); ?>
        
        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-admin-900 p-6">
            <?php 
                if($this->session->flashdata('success')) {
                    echo '<div class="mb-4 p-4 rounded bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200">'.$this->session->flashdata('success').'</div>';
                }
                if($this->session->flashdata('error')) {
                    echo '<div class="mb-4 p-4 rounded bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200">'.$this->session->flashdata('error').'</div>';
                }
            ?>
            
            <!-- Injected Content -->
            <?= isset($content) ? $content : '' ?>
            
            <!-- Footer -->
            <?php $this->load->view('admin/layouts/footer'); ?>
        </main>
    </div>
</div>
```

- [ ] **Step 5: Commit changes**

```bash
git add application/views/admin/
git commit -m "feat(admin-ui): create separate dark/light compact admin layout"
```

---

### Task 3: Create Admin Dashboard Overview

**Files:**
- Create: `application/controllers/Admin_Dashboard.php`
- Create: `application/views/admin/dashboard.php`

- [ ] **Step 1: Create Dashboard Controller**

```php
<?php
// application/controllers/Admin_Dashboard.php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data['title'] = 'Overview Dashboard';
        
        // Dummy data for now. Can be replaced with actual model counts
        $data['stats'] = [
            'total_users' => $this->db->count_all('users'),
            'total_antrean' => 0, // Will map to housing_queue later
            'total_diskusi' => $this->db->count_all('tb_diskusi')
        ];

        $this->render_admin('admin/dashboard', $data);
    }
}
```

- [ ] **Step 2: Create Dashboard View**

```php
<!-- application/views/admin/dashboard.php -->
<h2 class="text-2xl font-semibold mb-6">Overview Dashboard</h2>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Stat Card 1 -->
    <div class="bg-white dark:bg-admin-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 mr-4">
            <i class="ph ph-users text-3xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pengguna</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $stats['total_users'] ?></p>
        </div>
    </div>
    
    <!-- Stat Card 2 -->
    <div class="bg-white dark:bg-admin-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex items-center">
        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mr-4">
            <i class="ph ph-list-checks text-3xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Antrean Perumahan</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $stats['total_antrean'] ?></p>
        </div>
    </div>

    <!-- Stat Card 3 -->
    <div class="bg-white dark:bg-admin-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 mr-4">
            <i class="ph ph-chats text-3xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Topik Forum Aktif</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $stats['total_diskusi'] ?></p>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-admin-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold mb-4">Aktivitas Terkini</h3>
    <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada aktivitas yang tercatat hari ini.</p>
</div>
```

- [ ] **Step 3: Commit changes**

```bash
git add application/controllers/Admin_Dashboard.php application/views/admin/dashboard.php
git commit -m "feat(admin-ui): create dashboard overview controller and view"
```

---

### Task 4: Create Basic Content Management (Website Content)

**Files:**
- Create: `application/controllers/Admin_Content.php`
- Create: `application/views/admin/content/index.php`

- [ ] **Step 1: Create Admin Content Controller**

```php
<?php
// application/controllers/Admin_Content.php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Content extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data['title'] = 'Manajemen Konten Publikasi';
        
        // This will eventually load from a publications table
        $data['contents'] = [];

        $this->render_admin('admin/content/index', $data);
    }
}
```

- [ ] **Step 2: Create Admin Content View**

```php
<!-- application/views/admin/content/index.php -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold">Manajemen Konten Website</h2>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
        <i class="ph ph-plus mr-2"></i> Tambah Publikasi Baru
    </button>
</div>

<div class="bg-white dark:bg-admin-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-admin-900/50">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Judul Publikasi</th>
                    <th scope="col" class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Kategori</th>
                    <th scope="col" class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Tanggal Upload</th>
                    <th scope="col" class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if(empty($contents)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        Belum ada konten publikasi yang ditambahkan.
                    </td>
                </tr>
                <?php else: ?>
                    <!-- Looping data will be here -->
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 3: Commit changes**

```bash
git add application/controllers/Admin_Content.php application/views/admin/content/
git commit -m "feat(admin-content): add basic content management interface"
```

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-06-24-super-admin-infra.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration
**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**

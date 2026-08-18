<!doctype html>
<html lang="id">
  <head>
   	<?php $this->load->view('layouts/head'); ?>
  </head>
  <body class="bg-[#0a1a1f] text-[#ecffb6] h-screen overflow-hidden flex flex-col" style="height:100vh;display:flex;flex-direction:column;overflow:hidden;" x-data="globalSystem()">
       <?php $this->load->view('components/notification_center'); $this->load->view('components/file_viewer_modal'); $this->load->view('components/login_modal'); ?>
       <?php $this->load->view('layouts/nav'); ?>
       <?php
         // ---- Determine active tab from current controller/method ----
         $rt_class  = strtolower($this->router->fetch_class());
         $rt_method = strtolower($this->router->fetch_method());
         $active_tab = 'beranda'; // default

         if ($rt_class === 'pengembang' || $rt_method === 'tab_pengembang') {
             $active_tab = 'pengembang';
         } elseif ($rt_method === 'tab_perumahan' || in_array($rt_method, ['simulasi_kpr', 'panduan_desain', 'golek_omah', 'cari_rumah'])) {
             $active_tab = 'perumahan';
         // sebaran_rusun / profil_kumuh / sebaran_sdgs dicabut 29 Jul 2026 (A1).
         } elseif ($rt_method === 'tab_kawasan' || in_array($rt_method, ['sebaran'])) {
             $active_tab = 'kawasan';
         } elseif ($rt_method === 'tab_pertanahan' || in_array($rt_method, ['info_tanah', 'sertifikasi', 'sengketa', 'bank_tanah'])) {
             $active_tab = 'pertanahan';
         } elseif ($rt_method === 'tab_bankdata' || $rt_class === 'statistika') {
             $active_tab = 'bankdata';
         }
       ?>

       <!-- Portal Section: Tab Bar + Main Panel -->
       <section class="w-full relative flex-1 flex flex-col pt-1 overflow-hidden" style="flex:1 1 0%;min-height:0;display:flex;flex-direction:column;overflow:hidden;">
           <!-- Batik Kawung Background -->
           <div class="fixed inset-0 pointer-events-none" style="opacity: 0.08; z-index: 0;">
               <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                 <defs>
                   <pattern id="batik-kawung-portal" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                     <circle cx="0" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                     <circle cx="100" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                     <circle cx="0" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                     <circle cx="100" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                     <line x1="-15" y1="0" x2="15" y2="0" stroke="#00545f" stroke-width="2"/>
                     <line x1="0" y1="-15" x2="0" y2="15" stroke="#00545f" stroke-width="2"/>
                     <circle cx="0" cy="0" r="4.5" fill="#d6fb00"/>
                     <line x1="85" y1="0" x2="115" y2="0" stroke="#00545f" stroke-width="2"/>
                     <line x1="100" y1="-15" x2="100" y2="15" stroke="#00545f" stroke-width="2"/>
                     <circle cx="100" cy="0" r="4.5" fill="#d6fb00"/>
                     <line x1="-15" y1="100" x2="15" y2="100" stroke="#00545f" stroke-width="2"/>
                     <line x1="0" y1="85" x2="0" y2="115" stroke="#00545f" stroke-width="2"/>
                     <circle cx="0" cy="100" r="4.5" fill="#d6fb00"/>
                     <line x1="85" y1="100" x2="115" y2="100" stroke="#00545f" stroke-width="2"/>
                     <line x1="100" y1="85" x2="100" y2="115" stroke="#00545f" stroke-width="2"/>
                     <circle cx="100" cy="100" r="4.5" fill="#d6fb00"/>
                     <polygon points="50,40 60,50 50,60 40,50" fill="none" stroke="#00a3b5" stroke-width="2"/>
                     <circle cx="50" cy="50" r="2.5" fill="#ecffb6"/>
                     <circle cx="50" cy="22" r="2" fill="#00a3b5"/>
                     <circle cx="50" cy="78" r="2" fill="#00a3b5"/>
                     <circle cx="22" cy="50" r="2" fill="#00a3b5"/>
                     <circle cx="78" cy="50" r="2" fill="#00a3b5"/>
                   </pattern>
                 </defs>
                 <rect width="100%" height="100%" fill="url(#batik-kawung-portal)" />
               </svg>
           </div>

           <div class="w-full relative z-10 flex-1 flex flex-col overflow-hidden px-2 sm:px-3 lg:px-4 pt-2 pb-0 theme-light" style="flex:1 1 0%;min-height:0;display:flex;flex-direction:column;overflow:hidden;">
               <!-- Identitas ringkas -->
               <div class="flex items-center gap-3 mb-2 px-1 shrink-0">
                   <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="w-9 h-9 object-contain shrink-0">
                   <div>
                       <h1 class="text-base sm:text-lg font-extrabold text-white tracking-tight leading-tight">Klinik Perumahan & Kawasan Permukiman</h1>
                       <p class="text-[10px] sm:text-[11px] text-[#8aacb0]">Disperakim Provinsi Jawa Tengah</p>
                   </div>
               </div>

               <!-- Tab Bar + Auth (sejajar satu baris) -->
               <div class="flex items-end shrink-0">
                   <!-- Tabs (scrollable) -->
                   <div class="portal-tab-bar no-scrollbar flex-1 min-w-0">
                       <a href="<?= base_url() ?>" data-tab-link data-tab-key="beranda" class="portal-tab-btn <?= $active_tab === 'beranda' ? 'active' : '' ?>">
                           <i class="fa-solid fa-grip"></i> Beranda
                       </a>
                       <!-- Tab Perumahan / Kawasan / Pertanahan disembunyikan 1 Agu 2026 -
                            rute tab/* dan halamannya masih hidup, hanya tidak dipajang di tab bar. -->
                       <a href="<?= base_url('tab/pengembang') ?>" data-tab-link data-tab-key="pengembang" class="portal-tab-btn <?= $active_tab === 'pengembang' ? 'active' : '' ?>">
                           <i class="fa-solid fa-helmet-safety"></i> Pengembang
                       </a>
                       <a href="<?= base_url('tab/bankdata') ?>" data-tab-link data-tab-key="bankdata" class="portal-tab-btn <?= $active_tab === 'bankdata' ? 'active' : '' ?>">
                           <i class="fa-solid fa-chart-pie"></i> Bank Data
                       </a>
                       <?php /* Butir 20 putaran 2: tab "Cek Status Pengajuan" DICABUT dari
                                bilah publik. Status pengajuan kini hanya di dashboard tiap
                                peran - satu tempat, dan tidak lagi bisa ditengok orang lain
                                berbekal nomor tiket. Alasan lengkapnya di
                                Program::cek_status_pengajuan(). */ ?>
                   </div>

                   <!-- Auth Button (sejajar tabs, bukan tab) -->
                   <div class="shrink-0 flex items-end ml-auto pl-1 pr-2 sm:pr-3 mb-1">
                       <?php if ($this->session->userdata('is_logged')): ?>
                           <?php
                               $avatar_src = $this->session->userdata('avatar');
                               if (empty($avatar_src)) {
                                   // Dulu ui-avatars.com - nama pengguna dikirim
                                   // ke pihak ketiga tiap pemuatan halaman.
                                   $avatar_src = avatar_inisial($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                               }
                           ?>
                           <div class="flex items-center gap-2 px-4 py-2 bg-[#0d2228] border border-[#d6fb00]/15 rounded-[14px] h-[38px]">
                               <?php
                               /* Butir 14 putaran 2. Dulu `=== 'admin'` - jadi
                                  hanya superadmin yang punya jalan ke dashboard,
                                  dan lima peran lain tidak punya sama sekali.
                                  Sekarang semua yang masuk mendapatkannya, ke
                                  dashboard peran masing-masing. Teksnya ikut
                                  ditampilkan di layar lebar: ikon gauge sendirian
                                  tidak memberi tahu ini "dashboard". */
                               ?>
                               <?php if ( ! empty($dashboard_home)): ?>
                                   <a href="<?= base_url($dashboard_home) ?>" class="text-[#d6fb00] hover:text-white transition-colors flex items-center gap-1.5 h-full" title="Dashboard">
                                       <i class="fa-solid fa-gauge-high text-xs"></i>
                                       <span class="text-[12px] font-semibold hidden md:inline">Dashboard</span>
                                   </a>
                                   <div class="w-px h-4 bg-[#d6fb00]/20 mx-1"></div>
                               <?php endif; ?>
                               <a href="<?= base_url('akun') ?>" class="flex items-center gap-2 hover:opacity-80 transition-opacity h-full">
                                   <img src="<?= $avatar_src ?>" class="w-6 h-6 rounded object-cover border border-[#d6fb00]/20">
                                   <span class="text-[13px] font-semibold text-[#ecffb6] hidden sm:inline"><?= $this->session->userdata('username') ?: $this->session->userdata('name') ?></span>
                               </a>
                           </div>
                       <?php else: ?>
                           <a href="<?= base_url('Auth/login') ?>" class="flex items-center gap-2 btn-primary text-[13px] font-semibold px-5 py-2 rounded-[14px] h-[38px]">
                               <i class="fa-solid fa-right-to-bracket text-xs"></i>
                               Masuk
                           </a>
                       <?php endif; ?>
                   </div>
               </div>

               <!-- Main Panel -->
               <div class="portal-panel flex-1 flex flex-col overflow-hidden" style="flex:1 1 0%;min-height:0;display:flex;flex-direction:column;overflow:hidden;padding:clamp(.5rem,1.5vw,1rem);">
                   <!-- Masking Wrapper (Fixes the fade effect in place) -->
                   <div class="flex-1 flex flex-col w-full relative" style="-webkit-mask-image: linear-gradient(to bottom, transparent, black 24px, black calc(100% - 24px), transparent); mask-image: linear-gradient(to bottom, transparent, black 24px, black calc(100% - 24px), transparent);">
                       <!-- Scrolling Container -->
                       <div class="absolute inset-0 overflow-y-auto overflow-x-hidden no-scrollbar">
                              <div id="page-content-wrapper" class="relative z-10 mx-auto w-full max-w-6xl min-h-full py-4 sm:py-6">
                               <div id="page-loading-skeleton" class="page-loading-skeleton" aria-hidden="true">
                                   <?php if ($rt_class === 'umum' && in_array($rt_method, ['forum', 'detail'], true)): ?>
                                   <?php if ($rt_method === 'detail'): ?>
                                   <div class="h-4 w-32 mb-3 rounded skeleton"></div>
                                   <div class="rounded-2xl p-5 sm:p-8 mb-5 h-64 skeleton"></div>
                                   <div class="h-4 w-44 mb-3 rounded skeleton"></div>
                                   <div class="space-y-3"><div class="h-20 rounded-2xl skeleton"></div><div class="h-20 rounded-2xl skeleton"></div><div class="h-20 rounded-2xl skeleton"></div></div>
                                   <?php else: ?>
                                   <div class="h-3 w-40 mb-3 rounded skeleton"></div>
                                   <div class="h-8 w-72 mb-2 rounded skeleton"></div>
                                   <div class="h-4 w-full max-w-xl mb-5 rounded skeleton"></div>
                                   <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                                       <div class="rounded-2xl p-4 h-28 skeleton"></div><div class="rounded-2xl p-4 h-28 skeleton"></div><div class="rounded-2xl p-4 h-28 skeleton"></div>
                                   </div>
                                   <div class="h-11 w-full mb-4 rounded-xl skeleton"></div>
                                   <div class="space-y-3"><div class="h-28 rounded-2xl skeleton"></div><div class="h-28 rounded-2xl skeleton"></div></div>
                                   <?php endif; ?>
                                   <?php elseif ($rt_class === 'umum' && $rt_method === 'aduan'): ?>
                                   <div class="mx-auto max-w-2xl text-center">
                                       <div class="skeleton mx-auto mb-4 h-16 w-16 rounded-2xl"></div>
                                       <div class="skeleton mx-auto mb-2 h-3 w-32 rounded"></div>
                                       <div class="skeleton mx-auto mb-3 h-8 w-64 rounded"></div>
                                       <div class="skeleton mx-auto mb-8 h-3 w-80 rounded"></div>
                                   </div>
                                   <div class="mx-auto max-w-2xl space-y-4">
                                       <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                           <div class="skeleton h-12 rounded-xl"></div>
                                           <div class="skeleton h-12 rounded-xl"></div>
                                       </div>
                                       <div class="skeleton h-12 rounded-xl"></div>
                                       <div class="skeleton h-12 rounded-xl"></div>
                                       <div class="skeleton h-32 rounded-xl"></div>
                                       <div class="skeleton h-11 rounded-xl"></div>
                                       <div class="skeleton h-11 w-full rounded-full"></div>
                                   </div>
                                   <?php elseif ($rt_class === 'statistika' || $rt_method === 'tab_bankdata'): ?>
                                   <div class="mx-auto max-w-2xl text-center mb-4">
                                       <div class="skeleton mx-auto mb-2 h-9 w-9 rounded-xl"></div>
                                       <div class="skeleton mx-auto mb-2 h-6 w-56 rounded"></div>
                                       <div class="skeleton mx-auto h-3 w-72 rounded"></div>
                                   </div>
                                   <div class="flex flex-col gap-2.5 lg:flex-row">
                                       <div class="hidden w-48 shrink-0 rounded-2xl border border-[color:var(--portal-border)] p-2.5 lg:block">
                                           <div class="skeleton mb-3 h-4 w-24 rounded"></div>
                                           <div class="space-y-2">
                                               <div class="skeleton h-7 w-full rounded-xl"></div>
                                               <div class="skeleton h-7 w-full rounded-xl"></div>
                                               <div class="skeleton h-7 w-full rounded-xl"></div>
                                               <div class="skeleton h-7 w-full rounded-xl"></div>
                                           </div>
                                       </div>
                                       <div class="flex-1 space-y-4">
                                           <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                               <div class="skeleton h-20 rounded-2xl"></div>
                                               <div class="skeleton h-20 rounded-2xl"></div>
                                               <div class="skeleton h-20 rounded-2xl"></div>
                                               <div class="skeleton h-20 rounded-2xl"></div>
                                           </div>
                                           <div class="skeleton h-56 w-full rounded-2xl"></div>
                                       </div>
                                   </div>
                                   <?php elseif ($rt_class === 'pengembang'): ?>
                                   <div class="skeleton h-3 w-16 mb-2"></div>
                                   <div class="skeleton h-7 w-64 mb-4"></div>
                                   <div class="rounded-2xl border border-[color:var(--portal-border)] p-3 sm:p-4">
                                       <div class="flex items-center justify-between border-b border-[color:var(--portal-border)] pb-3 mb-2">
                                           <div class="skeleton h-4 w-44"></div><div class="skeleton h-7 w-24"></div>
                                       </div>
                                       <div class="space-y-2">
                                           <div class="skeleton h-8 w-full"></div><div class="skeleton h-8 w-full"></div><div class="skeleton h-8 w-full"></div><div class="skeleton h-8 w-full"></div><div class="skeleton h-8 w-full"></div>
                                       </div>
                                   </div>
                                   <?php else: ?>
                                   <div class="skeleton h-4 w-32 mb-2"></div>
                                   <div class="skeleton h-8 w-72 mb-5"></div>
                                   <div class="rounded-2xl border border-white/10 p-4">
                                       <div class="skeleton h-8 w-full mb-2"></div>
                                       <div class="space-y-2">
                                           <div class="skeleton h-7 w-full"></div>
                                           <div class="skeleton h-7 w-full"></div>
                                           <div class="skeleton h-7 w-full"></div>
                                           <div class="skeleton h-7 w-full"></div>
                                       </div>
                                   </div>
                                   <?php endif; ?>
                               </div>
                               <?=$content?>
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Footer tipis di luar panel, sejajar margin kanan -->
               <?php
                   $CI =& get_instance();
                   $CI->load->model('Setting_model');
                   $ftSettings = $CI->Setting_model->get_all();
               ?>
               <div class="shrink-0 flex items-center justify-center px-1 pt-1 pb-1 mb-1">
                   <span class="text-[10px] text-[#8aacb0]">&copy; <?= date('Y') ?> <?= htmlspecialchars($ftSettings['footer_copyright'] ?? 'KLINIK PKP JATENG') ?></span>
               </div>
           </div>
       </section>

       <!-- Scripts (footer.php tanpa visual footer) -->
       <?php $this->load->view('layouts/footer'); ?>
</body>
</html>

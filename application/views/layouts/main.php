<!doctype html>
<html lang="id">
  <head>
   	<?php $this->load->view('layouts/head'); ?>
  </head>
  <body class="bg-[#0a1a1f] text-[#ecffb6] h-screen overflow-hidden flex flex-col" x-data="globalSystem()">
       <?php $this->load->view('layouts/nav'); ?>
       <?php
         // ---- Determine active tab from current controller/method ----
         $rt_class  = strtolower($this->router->fetch_class());
         $rt_method = strtolower($this->router->fetch_method());
         $active_tab = 'beranda'; // default

         if ($rt_method === 'tab_perumahan' || in_array($rt_method, ['simulasi_kpr', 'panduan_desain', 'golek_omah', 'cari_rumah'])) {
             $active_tab = 'perumahan';
         } elseif ($rt_method === 'tab_kawasan' || in_array($rt_method, ['sebaran', 'sebaran_rusun', 'profil_kumuh', 'sebaran_sdgs'])) {
             $active_tab = 'kawasan';
         } elseif ($rt_method === 'tab_pertanahan' || in_array($rt_method, ['info_tanah', 'sertifikasi', 'sengketa', 'bank_tanah'])) {
             $active_tab = 'pertanahan';
         } elseif ($rt_method === 'tab_pengembang' || $rt_class === 'pengembang') {
             $active_tab = 'pengembang';
         } elseif ($rt_method === 'tab_bankdata' || $rt_class === 'statistika') {
             $active_tab = 'bankdata';
         }
       ?>

       <!-- Portal Section: Tab Bar + Main Panel -->
       <section class="w-full relative flex-1 flex flex-col pt-3 overflow-hidden">
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

           <div class="w-full relative z-10 flex-1 flex flex-col overflow-hidden px-3 sm:px-4 lg:px-6 pt-4 pb-0 theme-light">
               <!-- Identitas ringkas -->
               <div class="flex items-center gap-3 mb-4 px-2 shrink-0">
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
                       <a href="<?= base_url('tab/perumahan') ?>" data-tab-link data-tab-key="perumahan" class="portal-tab-btn <?= $active_tab === 'perumahan' ? 'active' : '' ?>">
                           <i class="fa-solid fa-house-chimney"></i> Perumahan
                       </a>
                       <a href="<?= base_url('tab/kawasan') ?>" data-tab-link data-tab-key="kawasan" class="portal-tab-btn <?= $active_tab === 'kawasan' ? 'active' : '' ?>">
                           <i class="fa-solid fa-city"></i> Kawasan
                       </a>
                       <a href="<?= base_url('tab/pertanahan') ?>" data-tab-link data-tab-key="pertanahan" class="portal-tab-btn <?= $active_tab === 'pertanahan' ? 'active' : '' ?>">
                           <i class="fa-solid fa-mountain-sun"></i> Pertanahan
                       </a>
                       <a href="<?= base_url('tab/pengembang') ?>" data-tab-link data-tab-key="pengembang" class="portal-tab-btn <?= $active_tab === 'pengembang' ? 'active' : '' ?>">
                           <i class="fa-solid fa-helmet-safety"></i> Pengembang
                       </a>
                       <a href="<?= base_url('tab/bankdata') ?>" data-tab-link data-tab-key="bankdata" class="portal-tab-btn <?= $active_tab === 'bankdata' ? 'active' : '' ?>">
                           <i class="fa-solid fa-chart-pie"></i> Bank Data
                       </a>
                   </div>

                   <!-- Auth Button (sejajar tabs, bukan tab) -->
                   <div class="shrink-0 flex items-end ml-auto pl-2 pr-[1.25rem] sm:pr-[1.5rem] mb-1.5">
                       <?php if ($this->session->userdata('is_logged')): ?>
                           <?php
                               $avatar_src = $this->session->userdata('avatar');
                               if (empty($avatar_src)) {
                                   $fallback_name = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                                   $avatar_src = "https://ui-avatars.com/api/?name={$fallback_name}&background=d6fb00&color=0a1a1f&bold=true";
                               }
                           ?>
                           <div class="flex items-center gap-2 px-4 py-2 bg-[#0d2228] border border-[#d6fb00]/15 rounded-[14px] h-[38px]">
                               <?php if ($this->session->userdata('role') === 'admin'): ?>
                                   <a href="<?= base_url('Admin_Dashboard') ?>" class="text-[#d6fb00] hover:text-white transition-colors flex items-center h-full" title="Dashboard">
                                       <i class="fa-solid fa-gauge-high text-xs"></i>
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

               <!-- Main Panel (scrollable content) -->
               <div class="portal-panel flex-1 flex flex-col overflow-y-auto overflow-x-hidden no-scrollbar">
                   <div id="page-content-wrapper" class="relative z-10 flex-1 w-full">
                       <?=$content?>
                   </div>
               </div>

               <!-- Footer tipis di luar panel, sejajar margin kanan -->
               <?php
                   $CI =& get_instance();
                   $CI->load->model('Setting_model');
                   $ftSettings = $CI->Setting_model->get_all();
               ?>
               <div class="shrink-0 flex items-center justify-center px-2 pt-1.5 pb-2">
                   <span class="text-[10px] text-[#8aacb0]">&copy; <?= date('Y') ?> <?= htmlspecialchars($ftSettings['footer_copyright'] ?? 'KLINIK PKP JATENG') ?></span>
               </div>
           </div>
       </section>

       <!-- Scripts (footer.php tanpa visual footer) -->
       <?php $this->load->view('layouts/footer'); ?>
</body>
</html>

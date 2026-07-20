<!doctype html>
<html lang="id">
  <head>
   	<?php $this->load->view('layouts/head'); ?>
  </head>
  <body class="bg-[#0a1a1f] text-[#ecffb6] min-h-screen flex flex-col" x-data="globalSystem()">
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
       <section class="w-full relative flex-1 flex flex-col pt-20 pb-10 px-3 sm:px-6 lg:px-8">
           <!-- Batik Kawung Background -->
           <div class="fixed inset-0 pointer-events-none" style="opacity: 0.08; z-index: 0; -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%); mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%);">
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

           <div class="max-w-7xl mx-auto w-full relative z-10 flex-1 flex flex-col">
               <!-- Identitas ringkas -->
               <div class="flex items-center gap-3 mb-4 px-2">
                   <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="w-9 h-9 object-contain shrink-0">
                   <div>
                       <h1 class="text-base sm:text-lg font-extrabold text-white tracking-tight leading-tight">Klinik Perumahan & Kawasan Permukiman</h1>
                       <p class="text-[10px] sm:text-[11px] text-[#8aacb0]">Disperakim Provinsi Jawa Tengah</p>
                   </div>
               </div>

               <!-- Tab Bar -->
               <div class="portal-tab-bar no-scrollbar">
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

               <!-- Main Panel -->
               <div class="portal-panel flex-1 flex flex-col">
                   <div id="page-content-wrapper" class="relative z-10 flex-1 flex flex-col w-full">
                       <?=$content?>
                   </div>
               </div>
           </div>
       </section>

       <?php $this->load->view('layouts/footer'); ?>
</body>
</html>

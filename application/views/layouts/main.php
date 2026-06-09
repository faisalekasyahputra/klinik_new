<!doctype html>
<html lang="id">
  <head>
   	<?php $this->load->view('layouts/head'); ?>
  </head>
  <body class="bg-[#0a1a1f] text-[#ecffb6] min-h-screen flex flex-col" x-data="globalSystem()">
       <?php $this->load->view('layouts/nav'); ?>
       <?php 
         $is_home = (strtolower($this->router->fetch_class()) == 'index' && strtolower($this->router->fetch_method()) == 'index');
       ?>
       <main class="flex-1 flex flex-col relative" <?= $is_home ? '' : 'style="padding-top: 80px;"' ?>>
           <?php if (!$is_home): ?>
           <!-- Batik Kawung Fixed Background Pattern for Sub Pages -->
           <div class="fixed inset-0 pointer-events-none" style="opacity: 0.08; z-index: 0; -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%); mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%);">
               <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                 <defs>
                   <pattern id="batik-kawung-subpage" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
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
                 <rect width="100%" height="100%" fill="url(#batik-kawung-subpage)" />
               </svg>
           </div>
           <?php endif; ?>
           
           <div id="page-content-wrapper" class="relative z-10 flex-1 flex flex-col w-full">
               <?=$content?>
           </div>
       </main>
       <?php $this->load->view('layouts/footer'); ?>
</body>
</html>

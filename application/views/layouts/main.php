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
       <main class="flex-1 flex flex-col" <?= $is_home ? '' : 'style="padding-top: 80px;"' ?>>
           <?=$content?>
       </main>
       <?php $this->load->view('layouts/footer'); ?>
</body>
</html>

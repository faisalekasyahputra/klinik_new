<footer class="h-12 bg-white dark:bg-[#0a1a1f] border-t border-gray-200 dark:border-white/5 flex items-center justify-between px-6 text-xs font-medium text-gray-500 dark:text-brand-muted z-10 shrink-0">
    <div>&copy; <?= date('Y') ?> Dinas Perumahan Rakyat & Kawasan Permukiman Provinsi Jawa Tengah</div>
    <?php /* Tautan Bantuan dicabut 2 Okt 2026 (belum ada halamannya). Kebijakan Privasi dan
             Syarat dan Ketentuan dipasang lagi 4 Okt 2026 setelah halamannya ada. */ ?>
    <div class="flex items-center gap-4">
        <a href="<?= base_url('kebijakan-privasi') ?>" class="hover:underline">Kebijakan Privasi</a>
        <a href="<?= base_url('syarat-ketentuan') ?>" class="hover:underline">Syarat dan Ketentuan</a>
    </div>
</footer>
</body>
</html>

<?php
/**
 * Kontrol unggah berkas berlabel Indonesia untuk semua layar admin (audit UI 2 Okt 2026).
 * Input file bawaan peramban menulis "Choose File / No file chosen" sesuai bahasa
 * peramban, dan gayanya tidak bisa disamakan dengan isian lain. Input aslinya tetap
 * ada (transparan, menutupi kotak), jadi klik, keyboard, `required`, dan validasi
 * peramban tetap bekerja seperti biasa; gaya di admin/layouts/head.php (.input-berkas).
 *
 * Variabel view CodeIgniter menempel ke pemanggilan berikutnya, jadi SELALU kirim
 * keempat kuncinya walau kosong.
 *
 * @param string $ib_name      nama field (yang dibaca controller, jangan diubah)
 * @param string $ib_accept    nilai atribut accept
 * @param bool   $ib_required  wajib diisi
 * @param string $ib_attr      atribut tambahan tepercaya, mis. aria-describedby="..."
 */
?>
<label class="input-berkas" x-data="{ nama: '' }">
    <input type="file" name="<?= html_escape($ib_name) ?>" accept="<?= html_escape($ib_accept) ?>"<?= ! empty($ib_required) ? ' required' : '' ?> <?= $ib_attr ?? '' ?>
           @change="nama = Array.from($event.target.files).map(f => f.name).join(', ')">
    <span class="input-berkas-tombol"><i class="ph ph-upload-simple" aria-hidden="true"></i> Pilih berkas</span>
    <span class="input-berkas-nama" x-text="nama || 'Belum ada berkas dipilih'">Belum ada berkas dipilih</span>
</label>

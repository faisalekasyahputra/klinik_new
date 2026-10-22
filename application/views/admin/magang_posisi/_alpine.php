<?php
/**
 * Keadaan Alpine modal tambah/ubah posisi, dipakai layar Posisi Magang dan detail slot bidang.
 * Pemanggil mencetaknya di atribut x-data lewat html_escape(), jadi nilai di sini cukup json_encode.
 *
 * @var string $bidang_awal kode bidang terpilih saat menambah
 */
?>{ buka: false, id: '', bidang: <?= json_encode((string) $bidang_awal) ?>, posisi: '', kual: [''], kuota: 1, urutan: '', aktif: true, maksButir: <?= (int) KUALIFIKASI_MAKS_BUTIR ?>,
        tambahButir() { if (this.kual.length < this.maksButir) { this.kual.push(''); this.$nextTick(() => { const k = this.$refs.daftarKual.querySelectorAll('input'); k[k.length - 1].focus(); }); } },
        hapusButir(i) { this.kual.splice(i, 1); if (!this.kual.length) this.kual.push(''); },
        tambah() { this.id = ''; this.posisi = ''; this.kual = ['']; this.kuota = 1; this.urutan = ''; this.aktif = true; this.buka = true; this.$nextTick(() => this.$refs.posisi.focus()); },
        ubah(d) { this.id = d.id; this.bidang = d.bidang; this.posisi = d.posisi; this.kual = JSON.parse(d.kual || '[]'); if (!this.kual.length) this.kual = ['']; this.kuota = d.kuota; this.urutan = d.urutan; this.aktif = d.aktif === '1'; this.buka = true; this.$nextTick(() => this.$refs.posisi.focus()); } }
<?php
/**
 * Butir 4 putaran 2: desain prototipe beserta RAB.
 *
 * Dinas menyebut tiga kelompok: desain prototype, desain KRS Jawa 3, dan rumah
 * liliput UGM. Dua yang pertama sumbernya SUDAH ADA di API KRS Jawa 3 yang
 * dipakai halaman ini; yang ketiga tidak ada di sana sama sekali.
 *
 * Kelompok ketiga TIDAK dibuat kosong dan TIDAK diisi data yang kebetulan ada.
 * Ia disebut sebagai keterangan, supaya dinas tahu persis apa yang kurang dan
 * kenapa - bukan menemukan sendiri kelompok yang selalu nihil lalu mengira
 * halamannya rusak.
 *
 * RAB boleh diunduh publik (keputusan user 11 Agt 2026), jadi tautannya
 * langsung, tanpa gerbang.
 */
$prototipe = isset($prototipe) && is_array($prototipe) ? $prototipe : [];
?>
<section class="mx-auto max-w-6xl px-3 pt-4">
  <div class="mb-4">
    <h2 class="text-xl font-black text-[color:var(--portal-text)] sm:text-2xl">Desain Prototipe</h2>
    <p class="mt-1 text-sm text-[color:var(--portal-text-muted)]">
      Rumah contoh siap pakai dari KRS Jawa 3, sebagian besar sudah dilengkapi Rencana Anggaran Biaya (RAB).
    </p>
  </div>

  <?php if ($prototipe): ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($prototipe as $d): ?>
        <?php
        /* path_image/path_rab/path_file dari API KRS Jawa 3 adalah path
           RELATIF ("uploads/images/...", bukan URL utuh) - dibuktikan
           langsung dari respons API 20 Agt 2026. Tanpa awalan host, browser
           mencoba memuatnya relatif terhadap domain KITA (mis.
           floralwhite-lion-710022.hostingersite.com/uploads/images/...),
           yang tidak pernah ada di sana - ikon gambar patah di setiap kartu
           yang isinya bukan null. detail_desain.php di folder yang sama
           SUDAH menambahkan awalan ini dengan benar; halaman ini yang
           ketinggalan. Diikat ke SATU closure supaya ketiganya (gambar,
           RAB, berkas kerja) tidak bisa lupa disamakan lagi kalau nanti
           ada field serupa yang keempat. */
        $tautan_ternak = static function ($path) {
            $path = trim((string) $path);
            return $path === '' ? '' : 'https://apiternak.krsjawa3.com/' . ltrim($path, '/');
        };
        $judul = (string) ($d['title'] ?? 'Tanpa judul');
        $gbr    = $tautan_ternak($d['path_image'] ?? '');
        $rab    = $tautan_ternak($d['path_rab'] ?? '');
        $berkas = $tautan_ternak($d['path_file'] ?? '');
        ?>
        <article class="overflow-hidden rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)]">
          <?php if ($gbr !== ''): ?>
            <img src="<?= html_escape($gbr) ?>" alt="<?= html_escape($judul) ?>"
                 class="h-44 w-full object-cover" loading="lazy">
          <?php endif; ?>
          <div class="p-4">
            <h3 class="text-sm font-black text-[color:var(--portal-text)]"><?= html_escape($judul) ?></h3>
            <?php if ( ! empty($d['description'])): ?>
              <p class="mt-1 line-clamp-2 text-xs text-[color:var(--portal-text-muted)]"><?= html_escape(strip_tags((string) $d['description'])) ?></p>
            <?php endif; ?>
            <div class="mt-3 flex flex-wrap gap-2">
              <?php if ($berkas !== ''): ?>
                <a href="<?= html_escape($berkas) ?>" target="_blank" rel="noopener"
                   class="rounded-xl border border-[color:var(--portal-border)] px-3 py-1.5 text-xs font-bold text-[color:var(--portal-text)]">Lihat gambar kerja</a>
              <?php endif; ?>
              <?php /* RAB yang TIDAK ada tidak diberi tombol mati. Satu dari
                       sembilan memang belum punya, dan tombol yang menuju
                       ke mana-mana lebih membingungkan daripada tidak ada. */ ?>
              <?php if ($rab !== ''): ?>
                <a href="<?= html_escape($rab) ?>" target="_blank" rel="noopener"
                   class="rounded-xl bg-[color:var(--portal-brand)] px-3 py-1.5 text-xs font-bold text-[#0a1a1f]">Unduh RAB</a>
              <?php else: ?>
                <span class="rounded-xl px-3 py-1.5 text-xs text-[color:var(--portal-text-muted)]">RAB belum tersedia</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="rounded-2xl border border-[color:var(--portal-border)] p-6 text-center text-sm text-[color:var(--portal-text-muted)]">
      Desain prototipe belum dapat dimuat dari sumbernya saat ini.
    </p>
  <?php endif; ?>

  <p class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
     style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
    <strong>Rumah liliput UGM belum tersedia.</strong> Dinas menyebutnya sebagai kelompok ketiga, tetapi
    datanya belum ada di sumber KRS Jawa 3 yang dipakai halaman ini. Kami sengaja tidak mengisinya dengan
    desain lain yang kebetulan ada. Begitu sumbernya diberikan, kelompoknya menyusul.
  </p>

  <div class="mt-6 border-t border-[color:var(--portal-border)] pt-4">
    <h2 class="text-xl font-black text-[color:var(--portal-text)] sm:text-2xl">Desain KRS Jawa 3</h2>
    <p class="mt-1 text-sm text-[color:var(--portal-text-muted)]">Katalog desain rumah lengkap.</p>
  </div>
</section>

<div class="design-catalog px-1 pb-4 sm:px-2 sm:pb-6 relative font-outfit">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-6 gap-4">
        <div>
            <h2 class="text-3xl sm:text-4xl font-black text-[color:var(--portal-text)] tracking-tighter font-jakarta"><span style="color: var(--portal-brand);">Bank</span> Desain</h2>
            <p class="text-[color:var(--portal-text-muted)] text-sm mt-2">Temukan desain rumah yang sesuai kebutuhan Anda</p>
        </div>
    </div>

    <div id="designs-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
        <?php if (empty($is_ajax_load)): ?>
            <!-- Direct load only; SPA navigation already has a global skeleton. -->
            <?php for ($i = 0; $i < 5; $i++): ?>
                <div><div class="skeleton h-80"></div></div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .design-catalog .card-content-wrapper {
        transform: translateY(0);
    }
    .design-catalog .card-details {
        height: auto;
        opacity: 1;
    }
    .design-catalog #designs-container > div {
        min-width: 0;
    }
</style>

<script>
// Halaman mandiri: tampilkan katalog penuh dalam grid responsif.
document.addEventListener('DOMContentLoaded', function() {
    $.ajax({
        url: '<?= base_url("ajax_house_designs") ?>',
        success: function(html) {
            $('#designs-container').html(html);
        },
        error: function() {
            $('#designs-container').html('<p class="col-span-full text-center text-[#5a7a80] py-8">Gagal memuat data desain rumah.</p>');
        }
    });
});
</script>

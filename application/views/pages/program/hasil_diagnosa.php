<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$read_value = static function ($source, $key, $default = '') {
    if (is_array($source)) {
        return array_key_exists($key, $source) ? $source[$key] : $default;
    }

    if (is_object($source)) {
        return isset($source->{$key}) ? $source->{$key} : $default;
    }

    return $default;
};

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$hasil = isset($hasil) && is_array($hasil) ? $hasil : [];
$programs = isset($hasil['eligible_programs']) && is_array($hasil['eligible_programs']) ? $hasil['eligible_programs'] : [];
$desil = $read_value($hasil, 'desil', '-');
$survey = isset($data_survey) && is_array($data_survey) ? $data_survey : [];

$guides = [
    'flpp' => [
        'requirements' => ['WNI dengan KTP aktif', 'Belum memiliki rumah', 'Memenuhi ketentuan penghasilan MBR', 'Memilih rumah dari pengembang mitra'],
        'steps' => ['Pilih rumah subsidi', 'Ajukan KPR ke bank penyalur', 'Bank memverifikasi dokumen', 'Tanda tangan akad kredit'],
    ],
    'oemah_lestari' => [
        'requirements' => ['WNI dengan KTP aktif', 'Menyiapkan dokumen penghasilan', 'Memilih rumah sesuai kemampuan bayar', 'Memenuhi ketentuan bank penyalur'],
        'steps' => ['Konsultasi pilihan pembiayaan', 'Lengkapi dokumen', 'Ajukan ke bank/BPR mitra', 'Tunggu hasil verifikasi'],
    ],
    'pb' => [
        'requirements' => ['WNI dengan KTP aktif', 'Memiliki atau menyiapkan lahan yang jelas', 'Masuk kriteria MBR', 'Bersedia mengikuti verifikasi lapangan'],
        'steps' => ['Siapkan dokumen lahan', 'Ajukan ke pemerintah daerah', 'Verifikasi administrasi dan lapangan', 'Penetapan penerima bantuan'],
    ],
    'rtlh' => [
        'requirements' => ['WNI dengan KTP aktif', 'Memiliki rumah tidak layak huni', 'Masuk kriteria penerima bantuan', 'Bersedia mengikuti verifikasi lapangan'],
        'steps' => ['Daftar melalui pemerintah daerah', 'Verifikasi kondisi rumah', 'Penetapan penerima bantuan', 'Pelaksanaan perbaikan rumah'],
    ],
    'omah_sekeng' => [
        'requirements' => ['WNI dengan KTP aktif', 'Masuk kriteria Desil 4', 'Memenuhi ketentuan program kolaborasi', 'Bersedia diverifikasi'],
        'steps' => ['Lengkapi data pengajuan', 'Verifikasi calon penerima', 'Pencocokan dukungan program', 'Penetapan bantuan'],
    ],
    'rumah_apung' => [
        'requirements' => ['WNI dengan KTP aktif', 'Berdomisili di kawasan pesisir/rawan rob', 'Memenuhi hasil verifikasi lokasi', 'Bersedia mengikuti pendampingan'],
        'steps' => ['Ajukan minat program', 'Verifikasi lokasi hunian', 'Penyusunan kebutuhan rumah', 'Pelaksanaan bersama pendamping'],
    ],
    'default' => [
        'requirements' => ['WNI dengan KTP aktif', 'Data pengajuan sesuai kondisi sebenarnya', 'Memenuhi ketentuan program', 'Bersedia mengikuti verifikasi'],
        'steps' => ['Pelajari program', 'Lengkapi dokumen', 'Ajukan ke instansi/bank terkait', 'Tunggu hasil verifikasi'],
    ],
];
?>

<div class="theme-light min-h-full bg-[color:var(--portal-bg)] px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <div class="relative overflow-hidden rounded-[2rem] border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-5 py-7 shadow-[var(--portal-shadow)] sm:px-8 sm:py-10">
            <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-[#d6fb00]/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-16 h-52 w-52 rounded-full bg-cyan-300/25 blur-3xl"></div>

            <div class="relative">
                <a href="<?= base_url('solusi_pembiayaan') ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-[color:var(--portal-text-muted)] transition hover:text-[color:var(--portal-brand)]">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali ke diagnosa
                </a>

                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="mb-2 inline-flex items-center gap-2 rounded-full bg-[#d6fb00]/35 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-[#00545f]">
                            <i class="fa-solid fa-sparkles"></i> Hasil Diagnosa
                        </p>
                        <?php
                        /**
                         * Judul menyebut NAMA PROGRAMNYA, bukan kalimat umum.
                         *
                         * Revisi dinas 3 Agt 2026: "hasil diagnosa nanti cukup
                         * program yang cocok untuk anda adalah x, tata caranya ini."
                         * Halaman lama membuka dengan "Program yang sesuai dengan
                         * Anda" lalu memaksa pembaca memindai kartu untuk tahu
                         * jawabannya — padahal jawabannya sudah dihitung.
                         */
                        $program_utama = '';
                        if ( ! empty($programs)) {
                            $program_utama = (string) $read_value($programs[0], 'title', '');
                        }
                        ?>
                        <h1 class="max-w-2xl text-3xl font-black leading-tight text-[color:var(--portal-text)] sm:text-4xl">
                            <?php if ($program_utama !== ''): ?>
                                Program yang cocok untuk Anda adalah
                                <span class="text-[color:var(--portal-brand)]"><?= $escape($program_utama) ?></span>
                            <?php else: ?>
                                Hasil diagnosa Anda
                            <?php endif; ?>
                        </h1>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
                            <?php if (count($programs) > 1): ?>
                                Tata caranya ada di bawah. Ada <?= count($programs) - 1 ?> program lain yang juga cocok — bisa Anda bandingkan sebelum memilih.
                            <?php else: ?>
                                Tata caranya ada di bawah. Rekomendasi ini disusun dari jawaban survei Anda.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="shrink-0 rounded-2xl border border-cyan-500/20 bg-cyan-50 px-4 py-3 text-center">
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-cyan-700">Kategori kesejahteraan</span>
                        <strong class="mt-0.5 block text-2xl font-black text-[#00545f]">Desil <?= $escape($desil) ?></strong>
                    </div>
                </div>

                <?php if (!empty($survey)): ?>
                    <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <?php foreach (['penghasilan' => 'Penghasilan', 'pekerjaan' => 'Pekerjaan', 'status_kepemilikan' => 'Status hunian', 'alasan_pengajuan' => 'Kebutuhan'] as $key => $label): ?>
                            <?php $value = isset($survey[$key]) && $survey[$key] !== '' ? $survey[$key] : 'Belum diisi'; ?>
                            <div class="rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-3 py-2.5">
                                <span class="block text-[10px] font-bold uppercase tracking-wide text-[color:var(--portal-text-muted)]"><?= $escape($label) ?></span>
                                <span class="mt-1 block truncate text-xs font-semibold text-[color:var(--portal-text)]" title="<?= $escape($value) ?>"><?= $escape($value) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-9 space-y-6">
                    <?php if (empty($programs)): ?>
                        <div class="rounded-2xl border border-dashed border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] p-8 text-center">
                            <i class="fa-solid fa-compass text-3xl text-[color:var(--portal-brand)]"></i>
                            <h2 class="mt-3 text-lg font-bold text-[color:var(--portal-text)]">Rekomendasi belum tersedia</h2>
                            <p class="mt-1 text-sm text-[color:var(--portal-text-muted)]">Silakan ulangi diagnosa agar kami dapat menyusun rekomendasi program.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($programs)): ?>
                        <form action="<?= base_url('solusi_pembiayaan/ajukan') ?>" method="post" class="space-y-6">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <?php endif; ?>

                    <?php foreach ($programs as $index => $program): ?>
                        <?php
                        $kode = strtolower((string) $read_value($program, 'kode', 'default'));
                        $guide = isset($guides[$kode]) ? $guides[$kode] : $guides['default'];
                        $title = $read_value($program, 'title', 'Program Perumahan');
                        $badge = $read_value($program, 'badge', 'Rekomendasi');
                        $description = $read_value($program, 'desc', 'Program yang direkomendasikan berdasarkan hasil diagnosa Anda.');
                        $icon = (string) $read_value($program, 'icon', 'fa-house');
                        $icon = preg_match('/^fa-[a-z0-9-]+$/', $icon) ? $icon : 'fa-house';
                        $color = (string) $read_value($program, 'color', '#00a3b5');
                        $color = preg_match('/^#[a-fA-F0-9]{6}$/', $color) ? $color : '#00a3b5';
                        ?>
                        <article class="grid overflow-hidden rounded-[1.5rem] border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] shadow-[var(--portal-shadow)] lg:grid-cols-[1.05fr_.95fr]">
                            <div class="relative overflow-hidden p-6 sm:p-8">
                                <div class="absolute inset-0 opacity-60" style="background: radial-gradient(circle at 10% 0%, <?= $escape($color) ?>28, transparent 45%), linear-gradient(145deg, #ffffff, #f0f6f7);"></div>
                                <div class="relative">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm" style="color: <?= $escape($color) ?>;">
                                            <i class="fa-solid <?= $escape($icon) ?>"></i>
                                        </div>
                                        <span class="rounded-full border bg-white/75 px-3 py-1 text-[10px] font-bold uppercase tracking-widest" style="border-color: <?= $escape($color) ?>55; color: <?= $escape($color) ?>;">
                                            <?= $escape($badge) ?>
                                        </span>
                                    </div>

                                    <?php // Program pertama = yang disebut di judul; sisanya jelas ditandai
                                          // sebagai alternatif supaya pembaca tahu mana jawaban utamanya. ?>
                                    <p class="mt-8 text-xs font-bold uppercase tracking-[0.18em] text-[color:var(--portal-text-muted)]"><?= $index === 0 ? 'Program yang cocok' : 'Alternatif · ' . ($index + 1) ?></p>
                                    <h2 class="mt-2 text-2xl font-black leading-tight text-[color:var(--portal-text)] sm:text-3xl"><?= $escape($title) ?></h2>
                                    <p class="mt-3 max-w-md text-sm leading-relaxed text-[color:var(--portal-text-muted)]"><?= $escape($description) ?></p>
                                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold" style="color: <?= $escape($color) ?>;">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Direkomendasikan untuk kondisi Anda
                                    </div>
                                    <label class="mt-6 inline-flex cursor-pointer items-center gap-2 rounded-xl border border-[color:var(--portal-border)] bg-white px-3 py-2.5 text-xs font-bold text-[color:var(--portal-text)] transition hover:border-[color:var(--portal-brand)]">
                                        <input type="radio" name="program_kode" value="<?= $escape($kode) ?>" required class="h-4 w-4 accent-[color:var(--portal-brand)]">
                                        Pilih program ini
                                    </label>
                                </div>
                            </div>

                            <div class="border-t border-[color:var(--portal-border)] bg-white p-6 sm:p-8 lg:border-l lg:border-t-0">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#d6fb00]/35 text-[#00545f]"><i class="fa-solid fa-list-check"></i></div>
                                    <div>
                                        <h3 class="font-black text-[color:var(--portal-text)]">Syarat &amp; Tata Cara</h3>
                                        <p class="text-xs text-[color:var(--portal-text-muted)]">Untuk <?= $escape($title) ?></p>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                    <div>
                                        <h4 class="text-xs font-black uppercase tracking-widest text-[#00545f]">Siapkan</h4>
                                        <ul class="mt-3 space-y-2.5">
                                            <?php foreach ($guide['requirements'] as $requirement): ?>
                                                <li class="flex gap-2 text-sm leading-snug text-[color:var(--portal-text-muted)]"><i class="fa-solid fa-check mt-0.5 text-[#10b981]"></i><span><?= $escape($requirement) ?></span></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase tracking-widest text-[#00545f]">Langkah berikutnya</h4>
                                        <ol class="mt-3 space-y-2.5">
                                            <?php foreach ($guide['steps'] as $step => $instruction): ?>
                                                <li class="flex gap-2 text-sm leading-snug text-[color:var(--portal-text-muted)]"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-[10px] font-black text-cyan-700"><?= $step + 1 ?></span><span><?= $escape($instruction) ?></span></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if (!empty($programs)): ?>
                            <div class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-5 py-4 sm:flex-row">
                                <?php
                                /**
                                 * "Simpan Data", bukan "Ajukan/Usulkan/Daftar" —
                                 * revisi dinas 3 Agt 2026.
                                 *
                                 * Yang terjadi di belakang TIDAK berubah (keputusan
                                 * user): datanya tetap masuk antrean + terbit nomor
                                 * tiket supaya admin bisa menindaklanjuti. Yang
                                 * diperbaiki adalah janjinya — "Ajukan" terdengar
                                 * seperti melamar program dan bisa membuat warga
                                 * mengira dirinya sudah terdaftar sebagai penerima.
                                 * Keterangan di sebelahnya menyebutkan tiket, supaya
                                 * "simpan" tidak lantas terbaca sebagai "cuma catatan
                                 * pribadi".
                                 */
                                ?>
                                <p class="text-center text-sm text-[color:var(--portal-text-muted)] sm:text-left"><i class="fa-solid fa-circle-info mr-1 text-[color:var(--portal-brand)]"></i> Pilih satu program, lalu simpan. Anda akan menerima nomor tiket untuk memantau tindak lanjutnya.</p>
                                <button type="submit" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5 hover:brightness-95">
                                    Simpan Data <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="mt-8 flex flex-col items-center justify-between gap-4 rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-5 py-4 sm:flex-row">
                    <p class="text-center text-sm text-[color:var(--portal-text-muted)] sm:text-left"><i class="fa-solid fa-circle-info mr-1 text-[color:var(--portal-brand)]"></i> Kelayakan akhir mengikuti verifikasi dokumen dan ketentuan penyelenggara program.</p>
                    <a href="<?= base_url('solusi_pembiayaan') ?>" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5 hover:brightness-95">
                        Ulangi Diagnosa <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

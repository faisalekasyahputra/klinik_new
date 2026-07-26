<section class="w-full px-4 py-5 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)"
    x-data="srp2Wizard(<?= htmlspecialchars(json_encode([
        "isLogged" => (bool) $is_logged,
        "isPengembang" => (bool) $is_pengembang,
        "wrongRole" => (bool) $wrong_role,
        "namaUser" => $nama_user ?? "",
        "registrationId" => $registration_id,
        "dokumen" => $dokumen,
        "uploadedKeys" => array_values($uploaded_keys),
        "statusVerifikasi" => $status_verifikasi,
        "catatanAdmin" => $catatan_admin,
        "csrfName" => $this->security->get_csrf_token_name(),
        "csrfHash" => $this->security->get_csrf_hash(),
        "baseUrl" => base_url(),
        "dashboardUrl" => $dashboard_url,
        "isPengelola" => (bool) $is_pengelola,
    ]), ENT_QUOTES) ?>)" x-init="init()">
    <div class="mx-auto max-w-4xl">

        <nav class="mb-3 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">
            <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list" class="hover:underline" style="color:var(--teal)">Sertifikasi Pengembang</a><span class="mx-2">/</span><span>Daftar SRP2</span>
        </nav>

        <!-- Indikator langkah -->
        <div class="mb-5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide" style="color:var(--portal-text-muted)">
            <!-- Penanda langkah memakai stepTampil, bukan `step` mentah. `step`
                 adalah panel yang sedang DILIHAT; kemajuan sebenarnya ditentukan
                 status pengajuan. Dulu keduanya disamakan, sehingga pengajuan yang
                 sudah dikirim (bahkan sudah Diterima) tetap menampilkan langkah 4
                 "Selesai" dalam keadaan mati — seolah pemohon belum tuntas. -->
            <template x-for="(label, i) in ['Syarat', 'Akun', 'Unggah', 'Selesai']" :key="i">
                <span class="flex items-center gap-1.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px]" :style="stepTampil >= (i + 1) ? 'background:var(--teal);color:#fff' : 'background:var(--portal-border);color:var(--portal-text-muted)'" x-text="i + 1"></span>
                    <span :style="stepTampil >= (i + 1) ? 'color:var(--portal-text)' : ''" x-text="label"></span>
                    <i x-show="i < 3" class="fa-solid fa-chevron-right text-[9px] mx-0.5" style="opacity:.4"></i>
                </span>
            </template>
        </div>

        <!-- Toast progres — di-teleport ke <body> supaya posisinya beneran pojok layar,
             bukan relatif ke panel wizard (ancestor dengan transform/animasi bikin
             "fixed" biasa jadi ke-jebak di dalam panel, bukan viewport). -->
        <template x-teleport="body">
            <div x-show="toast.show" x-transition x-cloak class="fixed bottom-5 left-5 z-[9999] rounded-xl px-4 py-3 text-xs font-semibold shadow-lg" style="background:#0a1a1f;color:#fff">
                <i class="fa-solid fa-circle-notch fa-spin mr-1.5" x-show="uploading"></i><span x-text="toast.message"></span>
            </div>
        </template>

        <!-- ================= STEP 1: SYARAT ================= -->
        <div x-show="step === 1">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div><h1 class="text-xl font-black tracking-tight sm:text-2xl" style="color:var(--portal-text)">Syarat Pendaftaran SRP2</h1><p class="mt-1 text-xs leading-relaxed" style="color:var(--portal-text-muted)">Siapkan dokumen sebelum mendaftar.</p></div>
                <a href="https://s.id/lampiran_SRPP" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-[11px] font-bold" style="color:var(--teal);background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.18)"><i class="fa-solid fa-download"></i> Template Dokumen</a>
            </div>
            <div class="mb-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <div class="rounded-xl p-3" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)"><span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-black" style="background:rgba(0,163,181,.1);color:var(--teal)">1</span><h2 class="mt-2 text-xs font-extrabold">Baca syarat</h2><p class="mt-1 text-[10px]" style="color:var(--portal-text-muted)">Unduh dan siapkan berkas.</p></div>
                <div class="rounded-xl p-3" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)"><span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-black" style="background:rgba(0,163,181,.1);color:var(--teal)">2</span><h2 class="mt-2 text-xs font-extrabold">Masuk / daftar</h2><p class="mt-1 text-[10px]" style="color:var(--portal-text-muted)">Cukup nama perusahaan, email, dan kata sandi.</p></div>
                <div class="rounded-xl p-3" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)"><span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-black" style="background:rgba(0,163,181,.1);color:var(--teal)">3</span><h2 class="mt-2 text-xs font-extrabold">Unggah & kirim</h2><p class="mt-1 text-[10px]" style="color:var(--portal-text-muted)">Semua di halaman ini, tanpa pindah-pindah.</p></div>
            </div>
            <article class="rounded-2xl p-4 sm:p-5" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)">
                <h2 class="text-sm font-extrabold">Syarat Pendaftaran SRP2</h2>
                <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Mendaftar SRP2 dan melengkapi formulir beserta dokumen pendukung berikut.</p>
                <div class="mt-4 space-y-4 text-xs leading-relaxed" style="color:var(--portal-text-muted)">
                    <div><p class="font-bold" style="color:var(--portal-text)">Form 1 – Surat Permohonan SRP2</p><p>Unduh dan isi Form 1 pada lampiran.</p></div>
                    <div><p class="font-bold" style="color:var(--portal-text)">Form 2.A – Data Administrasi dan Identitas Pengembang Perumahan</p><p>Lengkapi dengan:</p><ul class="mt-1 list-disc space-y-0.5 pl-5"><li>Akta notaris</li><li>Pengesahan perusahaan oleh Kemenkumham</li><li>Kartu NPWP pengembang perumahan</li><li>Bukti keanggotaan asosiasi</li><li>Nomor Induk Berusaha (NIB)</li></ul></div>
                    <div class="grid gap-3 sm:grid-cols-2"><p><strong style="color:var(--portal-text)">Form 2.B</strong> – Data administrasi dan data pengurus.</p><p><strong style="color:var(--portal-text)">Form 3</strong> – Surat pernyataan bukan sebagai ASN.</p><p><strong style="color:var(--portal-text)">Form 4</strong> – Laporan keuangan dan data kepemilikan.</p><p><strong style="color:var(--portal-text)">Form 5</strong> – Ketersediaan SDM dan sertifikat kompetensi.</p><p><strong style="color:var(--portal-text)">Form 6</strong> – Pengalaman pekerjaan, lampirkan siteplan.</p><p><strong style="color:var(--portal-text)">Form 7–9</strong> – Kesanggupan laporan, kebenaran data, dan pakta integritas.</p></div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="step = 2" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f">Mulai Pendaftaran <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </article>
        </div>

        <!-- ================= STEP 2: AKUN ================= -->
        <div x-show="step === 2" x-cloak class="mx-auto max-w-md">
            <button type="button" @click="back()" class="mb-3 inline-flex items-center gap-1.5 text-[11px] font-bold" style="color:var(--portal-text-muted)"><i class="fa-solid fa-arrow-left"></i> Kembali ke Syarat</button>
            <div class="rounded-2xl p-5 sm:p-6" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)">

                <!-- Draft. Kalau ada catatanAdmin, artinya pengajuan ini DIBUKA
                     KEMBALI oleh admin lewat "Minta Perbaikan" — bukan draft baru.
                     Catatannya wajib ditampilkan, kalau tidak pemohon tidak tahu
                     apa yang harus diperbaiki. -->
                <template x-if="isPengembang && (status === 'Draft' || !status)">
                    <div class="py-3 text-center">
                        <template x-if="!catatanAdmin">
                            <div>
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(16,185,129,.1);color:#059669"><i class="fa-solid fa-circle-check"></i></div>
                                <h2 class="mt-4 text-lg font-black">Anda sudah terdaftar</h2>
                                <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed" style="color:var(--portal-text-muted)">Masuk sebagai <strong style="color:var(--portal-text)" x-text="namaUser"></strong>. Lanjut unggah dokumen persyaratan.</p>
                            </div>
                        </template>

                        <template x-if="catatanAdmin">
                            <div>
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(234,179,8,.12);color:#a16207"><i class="fa-solid fa-pen-to-square"></i></div>
                                <h2 class="mt-4 text-lg font-black">Perbaikan diminta admin</h2>
                                <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed" style="color:var(--portal-text-muted)">Pengajuan Anda dibuka kembali supaya bisa diperbaiki. Silakan sesuaikan dokumen sesuai catatan berikut, lalu kirim ulang.</p>
                                <div class="mx-auto mt-3 max-w-sm rounded-lg p-3 text-left text-xs leading-relaxed" style="background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.25);color:var(--portal-text)">
                                    <span class="font-bold">Catatan admin:</span> <span x-text="catatanAdmin"></span>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="step = 3" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f"><span x-text="catatanAdmin ? 'Perbaiki Dokumen' : 'Lanjut Unggah Dokumen'"></span> <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </template>

                <template x-if="isPengembang && status === 'Pending'">
                    <div class="py-3 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(0,163,181,.1);color:var(--teal)"><i class="fa-solid fa-hourglass-half"></i></div>
                        <h2 class="mt-4 text-lg font-black">Sedang Ditinjau Admin</h2>
                        <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed" style="color:var(--portal-text-muted)">Pengajuan Anda sudah terkirim dan sedang diperiksa. Dokumen tidak bisa diubah sampai ada keputusan.</p>
                        <button type="button" @click="step = 3" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:var(--portal-border);color:var(--portal-text)">Lihat Dokumen yang Dikirim <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </template>

                <template x-if="isPengembang && status === 'Ditolak'">
                    <div class="py-3 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(220,38,38,.1);color:#dc2626"><i class="fa-solid fa-circle-xmark"></i></div>
                        <h2 class="mt-4 text-lg font-black">Pengajuan Ditolak</h2>
                        <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed" style="color:var(--portal-text-muted)" x-show="!catatanAdmin">Silakan periksa kembali dan perbaiki dokumen Anda.</p>
                        <p class="mx-auto mt-1 max-w-xs rounded-lg p-2.5 text-left text-xs leading-relaxed" style="background:rgba(220,38,38,.06);color:var(--portal-text)" x-show="catatanAdmin"><strong>Catatan admin:</strong> <span x-text="catatanAdmin"></span></p>
                        <button type="button" @click="step = 3" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f">Perbaiki & Kirim Ulang <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </template>

                <template x-if="isPengembang && status === 'Diterima'">
                    <div class="py-3 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(16,185,129,.1);color:#059669"><i class="fa-solid fa-award"></i></div>
                        <h2 class="mt-4 text-lg font-black">Pengajuan Diterima</h2>
                        <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed" style="color:var(--portal-text-muted)">Selamat, sertifikasi Anda sudah disetujui. Kelola data perusahaan lewat halaman profil.</p>
                        <a href="<?= base_url('akun/profil') ?>" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f">Ke Profil Saya <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </template>

                <!-- Kartu salah-role. Pesan & tujuan tombol sadar-role: akun pengelola
                     (admin/admin_kabkota/admin_bidang) tidak perlu disuruh "daftar akun
                     pengembang" — mereka justru sisi yang memverifikasi SRP2. Tujuan
                     tombol diturunkan dari registry, bukan hardcode ke `akun`; superadmin
                     sengaja tidak punya menu "Status Pengajuan" sehingga dikirim ke sana
                     berarti mendarat di halaman yang tidak ada di menunya sendiri. -->
                <template x-if="wrongRole">
                    <div class="py-3 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:rgba(220,38,38,.1);color:#dc2626"><i class="fa-solid fa-triangle-exclamation"></i></div>

                        <template x-if="isPengelola">
                            <div>
                                <h2 class="mt-4 text-lg font-black">Akun pengelola, bukan pemohon</h2>
                                <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed" style="color:var(--portal-text-muted)">Pendaftaran SRP2 diisi oleh akun pengembang. Akun Anda berperan sebagai pengelola — pengajuan SRP2 yang masuk ditinjau dari panel admin, bukan dari halaman ini.</p>
                                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                                    <a :href="baseUrl + 'Admin_Srp2/pending'" class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:var(--teal);color:#fff">Tinjau Pengajuan SRP2</a>
                                    <a :href="baseUrl + dashboardUrl" class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:var(--portal-border);color:var(--portal-text)">Ke Dashboard Saya</a>
                                </div>
                            </div>
                        </template>

                        <template x-if="!isPengelola">
                            <div>
                                <h2 class="mt-4 text-lg font-black">Akun ini bukan akun pengembang</h2>
                                <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed" style="color:var(--portal-text-muted)">Layanan SRP2 hanya untuk akun dengan peran pengembang. Silakan keluar dan daftar akun pengembang baru.</p>
                                <a :href="baseUrl + dashboardUrl" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:var(--portal-border);color:var(--portal-text)">Ke Dashboard Saya</a>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!isLogged">
                    <div>
                        <div class="mb-5 grid grid-cols-2 gap-1.5 rounded-xl p-1" style="background:var(--portal-bg)">
                            <button type="button" @click="authTab = 'masuk'" :style="authTab === 'masuk' ? 'background:var(--portal-bg-card);color:var(--portal-text)' : 'color:var(--portal-text-muted)'" class="rounded-lg py-2 text-xs font-extrabold uppercase tracking-wide transition-all">Masuk</button>
                            <button type="button" @click="authTab = 'daftar'" :style="authTab === 'daftar' ? 'background:var(--portal-bg-card);color:var(--portal-text)' : 'color:var(--portal-text-muted)'" class="rounded-lg py-2 text-xs font-extrabold uppercase tracking-wide transition-all">Daftar Cepat</button>
                        </div>

                        <!-- Panel Masuk -->
                        <form x-show="authTab === 'masuk'" x-cloak @submit.prevent="doLogin()" class="space-y-3">
                            <?php // Kredensial demo — dikembalikan atas permintaan user 27 Jul 2026
                                  // supaya dinas bisa menelusuri alur SRP2 saat uji coba. Syarat
                                  // pemakaiannya di AGENTS.md §17 poin 12. ?>
                            <button type="button" @click="$refs.loginEmail.value='pengembang@example.com'; $refs.loginPassword.value='password'"
                                    class="w-full rounded-lg p-2.5 text-left transition-colors" style="background:rgba(0,163,181,.06);border:1px dashed rgba(0,163,181,.3)"
                                    onmouseover="this.style.background='rgba(0,163,181,.12)'" onmouseout="this.style.background='rgba(0,163,181,.06)'">
                                <span class="text-[9px] font-bold uppercase tracking-wider" style="color:var(--teal)"><i class="fa-solid fa-flask mr-1"></i>Kredensial Demo (klik untuk isi otomatis)</span>
                                <span class="mt-0.5 block text-[10px] font-semibold" style="color:var(--portal-text)">E: pengembang@example.com &nbsp;·&nbsp; P: password</span>
                            </button>
                            <div x-show="authError" x-cloak class="rounded-lg px-3 py-2 text-[11px] font-semibold" style="background:rgba(220,38,38,.08);color:#dc2626" x-text="authError"></div>
                            <label class="block text-[11px] font-bold">Email atau Username <span style="color:#dc2626">*</span>
                                <input x-ref="loginEmail" autocomplete="username" required placeholder="Email atau username" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <label class="block text-[11px] font-bold">Kata Sandi <span style="color:#dc2626">*</span>
                                <input x-ref="loginPassword" type="password" autocomplete="current-password" required placeholder="Kata sandi" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <button type="submit" :disabled="authLoading" class="w-full rounded-lg py-2.5 text-[11px] font-extrabold uppercase disabled:opacity-60" style="background:#d6fb00;color:#0a1a1f">
                                <span x-show="!authLoading">Masuk & Lanjutkan</span>
                                <span x-show="authLoading" x-cloak><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memproses...</span>
                            </button>
                        </form>

                        <!-- Panel Daftar Cepat -->
                        <form x-show="authTab === 'daftar'" x-cloak @submit.prevent="doRegister()" class="space-y-3">
                            <div x-show="regError" x-cloak class="rounded-lg px-3 py-2 text-[11px] font-semibold" style="background:rgba(220,38,38,.08);color:#dc2626" x-text="regError"></div>
                            <p class="text-[11px]" style="color:var(--portal-text-muted)">Cukup isi data dasar, dokumen dilengkapi di langkah berikutnya.</p>
                            <label class="block text-[11px] font-bold">Nama Perusahaan <span style="color:#dc2626">*</span>
                                <input x-ref="regNama" autocomplete="organization" required placeholder="Nama perusahaan" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <label class="block text-[11px] font-bold">Email Perusahaan <span style="color:#dc2626">*</span>
                                <input x-ref="regEmail" type="email" autocomplete="email" required placeholder="Email perusahaan" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <label class="block text-[11px] font-bold">Kata Sandi <span style="color:#dc2626">*</span>
                                <input x-ref="regPassword" type="password" autocomplete="new-password" required minlength="8" placeholder="Kata sandi" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <label class="block text-[11px] font-bold">Konfirmasi Kata Sandi <span style="color:#dc2626">*</span>
                                <input x-ref="regPasswordConfirm" type="password" autocomplete="new-password" required minlength="8" placeholder="Konfirmasi kata sandi" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                            </label>
                            <p class="text-[11px]" style="color:var(--portal-text-muted)"><i class="fa-solid fa-circle-info mr-1" style="color:var(--teal)"></i>Minimal 8 karakter, huruf besar, angka, dan simbol.</p>
                            <button type="submit" :disabled="authLoading" class="w-full rounded-lg py-2.5 text-[11px] font-extrabold uppercase disabled:opacity-60" style="background:#d6fb00;color:#0a1a1f">
                                <span x-show="!authLoading">Daftar & Lanjutkan</span>
                                <span x-show="authLoading" x-cloak><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memproses...</span>
                            </button>
                        </form>

                        <?php if (!empty($recaptcha_site_key)): ?><div class="mt-3 g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptcha_site_key, ENT_QUOTES, 'UTF-8') ?>"></div><?php endif; ?>
                    </div>
                </template>
            </div>
        </div>

        <!-- ================= STEP 3: UNGGAH ================= -->
        <div x-show="step === 3" x-cloak>
            <button type="button" @click="back()" class="mb-3 inline-flex items-center gap-1.5 text-[11px] font-bold" style="color:var(--portal-text-muted)"><i class="fa-solid fa-arrow-left"></i> Kembali ke Akun</button>
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><h1 class="text-lg font-black sm:text-xl" style="color:var(--portal-text)" x-text="readOnly ? 'Dokumen yang Dikirim' : 'Unggah Persyaratan SRP2'"></h1><p class="mt-1 text-xs" style="color:var(--portal-text-muted)" x-show="!readOnly">Pilih semua berkas dulu, lalu satu tombol untuk mengunggah semuanya satu per satu.</p></div>
                <span class="rounded-full px-3 py-1.5 text-[11px] font-bold self-start" style="background:rgba(0,163,181,.1);color:var(--teal)"><span x-text="uploadedCount"></span> / <span x-text="totalCount"></span> dokumen</span>
            </div>

            <div x-show="readOnly" class="mb-4 flex items-start gap-2 rounded-xl px-3.5 py-2.5 text-[11px] font-semibold" style="background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.2);color:var(--portal-text)">
                <i class="fa-solid fa-lock mt-0.5 shrink-0" style="color:var(--teal)"></i>
                <span x-text="status === 'Diterima' ? 'Pengajuan sudah disetujui — dokumen terkunci, tidak bisa diubah lagi.' : 'Sedang ditinjau admin — dokumen terkunci sampai ada keputusan.'"></span>
            </div>

            <div x-show="!readOnly" class="mb-4 flex items-start gap-2 rounded-xl px-3.5 py-2.5 text-[11px] font-semibold" style="background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.2);color:var(--portal-text)">
                <i class="fa-solid fa-circle-info mt-0.5 shrink-0" style="color:var(--teal)"></i>
                <span>Format PDF, JPG, atau PNG — maksimal <strong>2 MB</strong> per dokumen. Berkas yang lebih besar akan ditolak, kompres atau scan ulang dengan resolusi lebih rendah dulu.</span>
            </div>

            <div class="rounded-2xl p-3 sm:p-4" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
                <div class="grid gap-2 sm:grid-cols-2">
                    <template x-for="key in docKeys" :key="key">
                        <div class="rounded-lg p-2.5" style="background:var(--portal-bg);border:1px solid var(--portal-border)" :style="fileStatus[key] === 'error' ? 'border-color:rgba(220,38,38,.35)' : ''">
                        <div class="flex items-center gap-2">
                            <span class="min-w-0 flex-1 text-[11px] font-semibold truncate" style="color:var(--portal-text)" x-text="dokumen[key]"></span>

                            <template x-if="fileStatus[key] === 'done'">
                                <span class="flex shrink-0 items-center gap-2">
                                    <span class="text-[10px] font-bold" style="color:#059669"><i class="fa-solid fa-circle-check"></i> Tersimpan</span>
                                    <label x-show="!readOnly" class="cursor-pointer rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:var(--portal-text-muted);background:var(--portal-bg-card);border:1px solid var(--portal-border)" title="Ganti dengan berkas lain">
                                        <input type="file" @change="pickFile(key, $event)" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                                        <i class="fa-solid fa-rotate"></i> Ganti
                                    </label>
                                </span>
                            </template>
                            <template x-if="fileStatus[key] === 'uploading'">
                                <span class="shrink-0 text-[10px] font-bold" style="color:var(--teal)"><i class="fa-solid fa-circle-notch fa-spin"></i> Mengunggah</span>
                            </template>
                            <template x-if="fileStatus[key] === 'error'">
                                <span class="flex shrink-0 items-center gap-1.5">
                                    <button type="button" @click="retryOne(key)" class="rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:#dc2626;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2)" :title="fileMessage[key]"><i class="fa-solid fa-rotate-right"></i> Ulangi</button>
                                    <label class="cursor-pointer rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:var(--teal);background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.18)" title="Pilih berkas lain">
                                        <input type="file" @change="pickFile(key, $event)" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                                        <i class="fa-solid fa-file"></i>
                                    </label>
                                </span>
                            </template>
                            <template x-if="fileStatus[key] === 'selected'">
                                <span class="flex shrink-0 items-center gap-1.5">
                                    <label class="max-w-[110px] cursor-pointer truncate rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:var(--portal-text-muted);background:var(--portal-bg-card);border:1px solid var(--portal-border)" :title="'Klik untuk ganti — ' + (files[key] ? files[key].name : '')">
                                        <input type="file" @change="pickFile(key, $event)" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                                        <i class="fa-solid fa-paperclip"></i> <span x-text="files[key] ? files[key].name : 'Siap unggah'"></span>
                                    </label>
                                    <button type="button" @click="clearFile(key)" class="rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:#dc2626;background:rgba(220,38,38,.06)" title="Batalkan pilihan"><i class="fa-solid fa-xmark"></i></button>
                                </span>
                            </template>
                            <template x-if="(!fileStatus[key] || fileStatus[key] === 'idle') && !readOnly">
                                <label class="shrink-0 cursor-pointer rounded-md px-2 py-1.5 text-[10px] font-bold" style="color:var(--teal);background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.18)">
                                    <input type="file" @change="pickFile(key, $event)" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                                    <i class="fa-solid fa-upload"></i> Pilih
                                </label>
                            </template>
                            <template x-if="(!fileStatus[key] || fileStatus[key] === 'idle') && readOnly">
                                <span class="shrink-0 text-[10px] font-bold" style="color:var(--portal-text-muted)">Belum diunggah</span>
                            </template>
                        </div>
                        <p x-show="fileStatus[key] === 'error'" x-cloak class="mt-1.5 text-[10px] font-semibold" style="color:#dc2626" x-text="fileMessage[key]"></p>
                        </div>
                    </template>
                </div>

                <div x-show="!readOnly" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p x-show="!allUploaded" class="text-[11px]" style="color:var(--portal-text-muted)"><i class="fa-solid fa-circle-info mr-1" style="color:var(--teal)"></i>Belum lengkap boleh ditinggal — sisanya bisa dilengkapi lewat dashboard <span x-text="uploadedCount"></span>/<span x-text="totalCount"></span>.</p>
                    <div class="flex gap-2 sm:ml-auto">
                        <button type="button" @click="uploadAll()" :disabled="uploading || selectedPendingCount === 0" class="rounded-lg px-4 py-2 text-[11px] font-extrabold uppercase disabled:opacity-50" style="background:var(--teal);color:#fff">
                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Unggah Semua (<span x-text="selectedPendingCount"></span>)
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="submitError" x-cloak class="mt-4 rounded-xl px-4 py-3 text-xs font-semibold" style="background:rgba(220,38,38,.08);color:#dc2626" x-text="submitError"></div>

            <div x-show="!readOnly" class="mt-4 flex justify-end">
                <button type="button" @click="submitPengajuan()" :disabled="!allUploaded || submitLoading" class="rounded-lg px-4 py-2.5 text-[11px] font-extrabold uppercase disabled:opacity-50" style="background:#00545f;color:#fff" :title="!allUploaded ? 'Lengkapi semua dokumen dulu (atau dari dashboard)' : ''">
                    <span x-show="!submitLoading"><i class="fa-solid fa-paper-plane mr-1"></i> Kirim Pengajuan</span>
                    <span x-show="submitLoading" x-cloak><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Mengirim...</span>
                </button>
            </div>
            <div x-show="readOnly" class="mt-4 flex justify-end">
                <a href="<?= base_url('akun') ?>" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-[11px] font-extrabold uppercase" style="background:var(--portal-border);color:var(--portal-text)"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Status Pengajuan</a>
            </div>
        </div>

        <!-- ================= STEP 4: SELESAI ================= -->
        <div x-show="step === 4" x-cloak>
            <div class="mx-auto max-w-md rounded-2xl p-8 text-center sm:p-10" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full text-2xl" style="background:rgba(16,185,129,.1);color:#059669"><i class="fa-solid fa-check"></i></div>
                <h1 class="mt-4 text-xl font-black sm:text-2xl">Pengajuan Terkirim</h1>
                <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed" style="color:var(--portal-text-muted)">Pengajuan SRP2 Anda sudah masuk dan sedang menunggu verifikasi. Anda bisa memantau statusnya kapan saja lewat dashboard.</p>
                <a href="<?= base_url('akun') ?>" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f"><i class="fa-solid fa-gauge-high"></i> Cek Status Pengajuan</a>
            </div>
        </div>

    </div>
</section>

<?php if (!empty($recaptcha_site_key)): ?><script src="https://www.google.com/recaptcha/api.js" async defer></script><?php endif; ?>
<script>
function srp2Wizard(config) {
    return {
        step: 1,
        isLogged: config.isLogged,
        isPengembang: config.isPengembang,
        wrongRole: config.wrongRole,
        // Diperbarui dari respons login AJAX juga — kalau user masuk lewat
        // wizard dengan akun non-pengembang, nilai dari server saat halaman
        // dimuat masih milik tamu, bukan role yang baru login.
        dashboardUrl: config.dashboardUrl,
        isPengelola: config.isPengelola,
        namaUser: config.namaUser,
        registrationId: config.registrationId,
        dokumen: config.dokumen,
        status: config.statusVerifikasi,
        catatanAdmin: config.catatanAdmin,
        csrfName: config.csrfName,
        csrfHash: config.csrfHash,
        baseUrl: config.baseUrl,

        authTab: 'masuk',
        authLoading: false,
        authError: '',
        regError: '',

        files: {},
        fileStatus: {},
        // Dokumen yang benar-benar sudah tersimpan di server. Dipakai clearFile()
        // untuk tahu harus kembali ke 'done' atau 'idle' saat pemohon membatalkan
        // pilihan berkas. Dideklarasikan di sini supaya reaktif di Alpine.
        tersimpanDiServer: {},
        fileMessage: {},
        uploading: false,
        toast: { show: false, message: '' },
        submitLoading: false,
        submitError: '',

        init() {
            this.tandaiBerkasTerunggah(config.uploadedKeys);
            // Sudah pernah mulai pendaftaran -> langsung ke langkah unggah, bukan
            // diulang dari halaman syarat tiap kali buka.
            //
            // KECUALI kalau ada yang harus DIBACA dulu di langkah 2. Lompatan ini
            // dulu tanpa syarat, sehingga kartu "Perbaikan diminta admin" dan
            // "Pengajuan Ditolak" — dua-duanya memuat catatan admin — tidak pernah
            // terlihat: pemohon mendarat di panel unggah yang tidak menampilkan
            // catatan sama sekali, jadi tidak tahu apa yang harus diperbaiki.
            if (this.isPengembang && this.registrationId) {
                this.step = this.perluPerbaikan ? 2 : 3;
            }
        },

        /**
         * Tandai dokumen yang SUDAH tersimpan di server sebagai selesai.
         * Dipakai saat halaman dimuat maupun setelah login lewat wizard —
         * keduanya harus menghasilkan tampilan yang sama.
         */
        tandaiBerkasTerunggah(keys) {
            this.fileStatus = {};
            this.tersimpanDiServer = {};
            (keys || []).forEach(k => { this.fileStatus[k] = 'done'; this.tersimpanDiServer[k] = true; });
        },

        back() {
            if (this.step > 1) { this.step -= 1; }
        },

        get readOnly() { return this.status === 'Pending' || this.status === 'Diterima'; },

        /**
         * Ada keputusan admin yang menunggu dibaca pemohon sebelum dia lanjut
         * mengunggah. Dua keadaan yang memenuhi:
         *   - Ditolak            -> kartu penolakan (dengan atau tanpa catatan)
         *   - Draft + catatan    -> dibuka kembali admin lewat "Minta Perbaikan"
         *
         * Draft TANPA catatan sengaja tidak termasuk: itu draft biasa yang belum
         * pernah dikirim, tidak ada yang perlu dibaca. Pending/Diterima juga tidak,
         * catatan lamanya cuma riwayat — pemohon sudah mengirim ulang.
         */
        get perluPerbaikan() {
            if (this.status === 'Ditolak') { return true; }
            return this.status === 'Draft' && !!this.catatanAdmin;
        },

        /**
         * Kemajuan yang ditandai di indikator langkah — dibedakan dari `step`
         * (panel yang sedang dilihat). Pemohon yang pengajuannya sudah dikirim
         * memang sudah tuntas dari sisinya, walaupun dia sedang menengok kembali
         * daftar dokumen di panel 3.
         *
         * Ditolak SENGAJA tidak dianggap tuntas: pekerjaannya belum selesai,
         * dokumennya masih harus diperbaiki dan dikirim ulang.
         */
        get stepTampil() {
            const terkirim = this.status === 'Pending' || this.status === 'Diterima';
            return terkirim ? Math.max(this.step, 4) : this.step;
        },
        get docKeys() { return Object.keys(this.dokumen); },
        get uploadedCount() { return this.docKeys.filter(k => this.fileStatus[k] === 'done').length; },
        get totalCount() { return this.docKeys.length; },
        get allUploaded() { return this.totalCount > 0 && this.uploadedCount === this.totalCount; },
        get selectedPendingCount() { return this.docKeys.filter(k => this.files[k] && this.fileStatus[k] !== 'done').length; },

        pickFile(key, event) {
            const f = event.target.files[0];
            if (!f) return;
            this.files[key] = f;
            this.fileStatus[key] = 'selected';
        },

        clearFile(key) {
            delete this.files[key];
            // Kembali ke keadaan SEBELUM memilih berkas, bukan selalu 'idle'.
            // Dokumen yang sudah tersimpan di server tetap 'done' — dulu Batal
            // menurunkannya jadi 'idle', hitungan jatuh ke 13/14 dan tombol Kirim
            // terkunci padahal server memegang 14/14. Yang paling sering menekan
            // Ganti justru pemohon yang sedang memperbaiki dokumen, jadi bug ini
            // menghalangi persis alur yang dibangun fitur Minta Perbaikan.
            // Roadmap T1b butir 5.
            this.fileStatus[key] = this.tersimpanDiServer[key] ? 'done' : 'idle';
        },

        showToast(message, sticky) {
            this.toast = { show: true, message: message };
            clearTimeout(this._toastTimer);
            if (!sticky) { this._toastTimer = setTimeout(() => { this.toast.show = false; }, 3000); }
        },

        async doLogin() {
            this.authLoading = true; this.authError = '';
            try {
                const fd = new FormData();
                fd.append('email', this.$refs.loginEmail.value);
                fd.append('password', this.$refs.loginPassword.value);
                fd.append(this.csrfName, this.csrfHash);
                const res = await fetch(this.baseUrl + 'Auth/do_login', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.status === 'success') {
                    this.isLogged = true;
                    if (data.role !== 'pengembang') {
                        // Ambil tujuan dashboard & jenis akun dari server, supaya
                        // kartu salah-role menunjuk tempat yang benar untuk role
                        // yang BARU login (bukan nilai bawaan tamu).
                        this.dashboardUrl = data.dashboard_url || this.dashboardUrl;
                        this.isPengelola = !!data.is_pengelola;
                        this.wrongRole = true;
                        return;
                    }
                    this.isPengembang = true;
                    this.namaUser = data.name || this.namaUser;
                    this.registrationId = data.registration_id;
                    // Segarkan keadaan pengajuan dari server. Tanpa ini, nilai yang
                    // dipakai masih milik TAMU (dari saat halaman dimuat): status
                    // kosong sehingga panel unggah tampak bisa diedit walau server
                    // menolak, 0/14 dokumen walau sudah lengkap, dan catatan admin
                    // tidak muncul.
                    const s = data.srp2 || {};
                    this.status = s.status || null;
                    this.catatanAdmin = s.catatan_admin || null;
                    this.tandaiBerkasTerunggah(s.uploaded_keys);
                    this.showToast('Berhasil masuk!');
                } else {
                    this.authError = data.message || 'Gagal masuk.';
                }
            } catch (e) {
                this.authError = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
            } finally {
                this.authLoading = false;
            }
        },

        async doRegister() {
            this.authLoading = true; this.regError = '';
            try {
                const fd = new FormData();
                fd.append('nama_perusahaan', this.$refs.regNama.value);
                fd.append('email', this.$refs.regEmail.value);
                fd.append('password', this.$refs.regPassword.value);
                fd.append('password_confirm', this.$refs.regPasswordConfirm.value);
                fd.append('srp2_pengembang', '1');
                fd.append(this.csrfName, this.csrfHash);
                const res = await fetch(this.baseUrl + 'Auth/do_register', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.status === 'success') {
                    this.isLogged = true; this.isPengembang = true;
                    this.registrationId = data.registration_id;
                    this.showToast('Akun berhasil dibuat!');
                } else {
                    this.regError = data.message || 'Gagal mendaftar.';
                }
            } catch (e) {
                this.regError = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
            } finally {
                this.authLoading = false;
            }
        },

        async uploadOne(key) {
            if (!this.files[key]) return;
            this.fileStatus[key] = 'uploading';
            try {
                const fd = new FormData();
                fd.append(key, this.files[key]);
                fd.append(this.csrfName, this.csrfHash);
                const res = await fetch(this.baseUrl + 'Pengembang/simpan_dokumen/' + this.registrationId, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.status === 'success') {
                    this.fileStatus[key] = 'done';
                    // Sejak titik ini berkasnya ADA di server, jadi Batal pada
                    // pilihan berikutnya harus kembali ke 'done', bukan 'idle'.
                    this.tersimpanDiServer[key] = true;
                } else {
                    this.fileStatus[key] = 'error';
                    this.fileMessage[key] = data.message || 'Gagal diunggah.';
                }
            } catch (e) {
                this.fileStatus[key] = 'error';
                this.fileMessage[key] = 'Koneksi terputus.';
            }
        },

        async retryOne(key) {
            this.showToast('Mengunggah ulang...', true);
            await this.uploadOne(key);
            this.toast.show = false;
        },

        async uploadAll() {
            const keys = this.docKeys.filter(k => this.files[k] && this.fileStatus[k] !== 'done');
            if (keys.length === 0) return;
            this.uploading = true;
            for (let i = 0; i < keys.length; i++) {
                this.showToast('Mengunggah ' + (i + 1) + ' dari ' + keys.length + '...', true);
                await this.uploadOne(keys[i]);
            }
            this.uploading = false;
            this.showToast(this.allUploaded ? 'Semua dokumen berhasil diunggah!' : 'Sebagian dokumen gagal — silakan ulangi, atau lengkapi lewat dashboard.');
        },

        async submitPengajuan() {
            this.submitLoading = true; this.submitError = '';
            try {
                const fd = new FormData();
                fd.append(this.csrfName, this.csrfHash);
                const res = await fetch(this.baseUrl + 'Pengembang/kirim_pengajuan/' + this.registrationId, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.status === 'success') {
                    this.step = 4;
                } else {
                    this.submitError = data.message || 'Gagal mengirim pengajuan.';
                }
            } catch (e) {
                this.submitError = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
            } finally {
                this.submitLoading = false;
            }
        },
    };
}
</script>

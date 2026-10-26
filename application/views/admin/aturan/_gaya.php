<style>
/* Gaya layar Aturan rekomendasi (index dan halaman program). Warna mengikuti tema terang/gelap admin. */
.lencana-draf { border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 700; background: rgba(245,158,11,.15); color: #b45309; }
.lencana-ya { border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; background: rgba(16,185,129,.15); color: #047857; white-space: nowrap; }
.lencana-tidak { border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; background: rgba(148,163,184,.18); color: #64748b; white-space: nowrap; }
.dark .lencana-draf { color: #fbbf24; } .dark .lencana-ya { color: #6ee7b7; } .dark .lencana-tidak { color: #94a3b8; }
.kartu-program { display: flex; flex-direction: column; padding: 18px 20px 14px; }
.kartu-program-kepala { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.thumb-program { width: 64px; height: 44px; flex-shrink: 0; border-radius: 8px; object-fit: cover; border: 1px solid rgba(148,163,184,.25); }
.kartu-program-isi { margin: 16px 0; display: grid; gap: 12px; font-size: 13px; }
.kartu-program-isi dt { font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; }
.dark .kartu-program-isi dt { color: #8aa0a8; }
.kartu-program-isi dd { color: #1f2937; line-height: 1.45; }
.dark .kartu-program-isi dd { color: #e5e7eb; }
.kartu-program-kaki { margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(148,163,184,.2); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.desil-mini { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 11px;
    border: 1px solid rgba(148,163,184,.35); color: #94a3b8; }
.desil-mini.nyala { background: rgba(59,130,246,.18); border-color: rgba(59,130,246,.6); color: #2563eb; font-weight: 700; }
.dark .desil-mini.nyala { color: #93c5fd; }
.chip-desil { position: relative; display: inline-flex; }
.chip-desil input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
.chip-desil span { width: 46px; height: 46px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 16px;
    border: 1px solid rgba(148,163,184,.45); color: inherit; transition: background .15s, border-color .15s; }
.chip-desil input:checked + span { background: rgba(59,130,246,.2); border-color: rgba(59,130,246,.8); color: #2563eb; font-weight: 700; }
.dark .chip-desil input:checked + span { color: #93c5fd; }
.chip-desil input:focus-visible + span { outline: 2px solid #3b82f6; outline-offset: 2px; }
.chip-desil input:disabled + span { opacity: .7; cursor: default; }
.pilih-cek { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.3); cursor: pointer; }
.pilih-cek:has(input:checked) { border-color: rgba(59,130,246,.7); background: rgba(59,130,246,.08); }
.teks-ubah { color: #b45309; } .dark .teks-ubah { color: #fbbf24; }
</style>

# 🧪 Database Staging — Klinik PKP

Dokumen ini menjelaskan database terpisah yang dipakai khusus untuk environment staging (preview UI/UX ke customer), supaya perubahan yang belum matang tidak menyentuh data production.

## Kenapa Terpisah dari Production

Awalnya staging sempat direncanakan pakai database production yang sama. Diputuskan pindah ke database salinan sendiri supaya perubahan (query baru, migrasi kolom, eksperimen data) selama pengembangan UI/UX tidak bisa merusak data production secara tidak sengaja.

## Info Koneksi

| | |
|---|---|
| **Database** | `u504551489_klinikstg` |
| **User** | `u504551489_klinikstg` |
| **Host** | Sama dengan production (lihat `.env`) |
| **Dibuat** | 2026-07-19, sebagai salinan satu kali dari `u504551489_klinikpkp` |

Password **tidak** ditulis di sini — hanya ada di `.env` server staging dan `.env` lokal (blok yang di-comment). Jangan pernah commit credential ke git.

## Status Data

Ini adalah **snapshot**, bukan replika live — isinya adalah kondisi database production per tanggal dibuat, dan **tidak otomatis sinkron** dengan perubahan data production setelahnya. Kalau butuh data terbaru, ulangi proses export/import manual dari production (lihat § Cara Sync Ulang).

## Cara Sync Ulang (kalau data staging sudah terlalu usang)

1. Buka phpMyAdmin untuk database production (`u504551489_klinikpkp`) via hPanel
2. Tab **Export** → Method `Quick` → Format `SQL` → **Go**
3. Buka phpMyAdmin untuk database staging (`u504551489_klinikstg`)
4. Tab **Import** → upload file `.sql` tadi → **Go**

Ini akan menimpa seluruh isi database staging dengan kondisi production terbaru — perubahan yang sempat dibuat di staging akan hilang.

## Pemakaian

- **Server staging** (`darkseagreen-hamster-214338.hostingersite.com`): `.env` di server itu connect ke database ini.
- **Lokal**: `.env` lokal masih connect ke database production seperti biasa (lihat [`SETUP_DATABASE.md`](SETUP_DATABASE.md)); blok koneksi ke database staging ini ada di `.env` tapi di-comment — uncomment kalau mau develop langsung terhadap data staging.

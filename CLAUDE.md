# CLAUDE.md

Panduan teknis komprehensif untuk proyek ini ada di [`AGENTS.md`](AGENTS.md) — baca itu dulu sebelum menjelajah kode. File ini sengaja hanya pointer, supaya tidak ada dua sumber kebenaran yang bisa saling tidak sinkron.

Ringkas: CodeIgniter 3 (PHP 8.x), MySQL. Jangan edit `system/` atau `vendor/`. Jangan longgarkan fondasi keamanan (CSRF, anti-IDOR, enkripsi AES-256-GCM, security headers) tanpa alasan eksplisit dari user — lihat §9 di `AGENTS.md`.

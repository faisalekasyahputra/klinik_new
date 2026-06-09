<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profanity Filter Configuration
 * 
 * Daftar kata-kata yang dilarang di Forum Diskusi Klinik PKP.
 * Case-insensitive. Sistem mencocokkan menggunakan regex word-boundary.
 */

// Kata kasar / makian / spam
$config['profanity_words'] = [
    // =============================================
    // JUDI ONLINE & TARUHAN
    // =============================================
    'slot', 'gacor', 'togel', 'casino', 'jackpot', 'maxwin',
    'betting', 'taruhan', 'judi online', 'judi bola', 'bandar togel',
    'poker online', 'domino online', 'roulette', 'baccarat',
    'scatter', 'wild scatter', 'pragmatic', 'pgsoft', 'habanero',
    'rtp live', 'bocoran slot', 'pola slot', 'cheat slot',
    'deposit pulsa', 'agen slot', 'situs slot', 'daftar slot',
    'link alternatif', 'slot dana', 'slot ovo', 'slot gopay',
    'slot88', 'slot777', 'joker123', 'joker388', 'sv388',
    'sbobet', 'ibcbet', 'maxbet', 'nova88', 'cmd368',
    'toto macau', 'toto sgp', 'toto hk', 'keluaran hk',
    'prediksi togel', 'angka jitu', 'syair togel', 'data sgp',
    'bandar bola', 'parlay', 'mix parlay', 'handicap',

    // =============================================
    // PINJAMAN ONLINE / PENIPUAN FINANSIAL
    // =============================================
    'pinjol', 'pinjaman online', 'dana cair', 'loan shark',
    'pinjaman cepat', 'tanpa jaminan', 'tanpa agunan',
    'cair 5 menit', 'langsung cair', 'bunga rendah',
    'modal gratis', 'investasi bodong', 'money game',
    'skema ponzi', 'arisan online', 'kripto gratis',
    'bitcoin gratis', 'airdrop gratis', 'trading otomatis',

    // =============================================
    // SPAM, PHISHING & PROMOSI ILEGAL
    // =============================================
    'klik disini', 'click here', 'free money', 'hubungi wa',
    'promo terbatas', 'limited offer', 'gratis modal',
    'daftar sekarang', 'join now', 'gabung sekarang',
    'whatsapp kami', 'order sekarang', 'beli sekarang',
    'harga promo', 'diskon besar', 'flash sale',
    'obat kuat', 'obat pembesar', 'obat pelangsing',
    'vape murah', 'rokok elektrik', 'jual senjata',
    'jual airsoft', 'jual narkoba', 'jual ganja',
    'jual sabu', 'jual pil', 'bokep', 'porn', 'xxx',
    'onlyfans', 'livechat dewasa', 'video dewasa',

    // =============================================
    // KATA KASAR — BAHASA INDONESIA
    // =============================================
    'anjing', 'bangsat', 'bajingan', 'brengsek', 'tolol',
    'goblok', 'idiot', 'kampret', 'babi', 'monyet',
    'kontol', 'memek', 'ngentot', 'pepek', 'jancok',
    'asu', 'asw', 'anjg', 'anjir', 'anying',
    'bgst', 'bngst', 'bjngn', 'gblk', 'tlol',
    'tai', 'taik', 'tae', 'setan', 'iblis',
    'sundal', 'sundel', 'lacur', 'pelacur', 'lonte',
    'pecun', 'perek', 'jablay', 'bitch', 'bastard',
    'fuck', 'shit', 'damn', 'asshole', 'dick',
    'cok', 'cocot', 'bacot', 'bacod', 'ngecod',
    'pantek', 'pukimak', 'kimak', 'lancau', 'celaka',
    'keparat', 'bedebah', 'terkutuk', 'sialan',
    'bodoh', 'dungu', 'oon', 'bego', 'geblek',
    'somplak', 'ndeso', 'udik', 'norak', 'kambing',
    'tai kucing', 'mampus', 'matilah',

    // =============================================
    // KATA KASAR — BAHASA JAWA & SUNDA
    // =============================================
    'jancuk', 'jancok', 'dancuk', 'dancok', 'cuk',
    'asu', 'asuuu', 'diamput', 'diancuk', 'mbahmu',
    'cangkemu', 'matamu', 'raimu', 'ndasmu',
    'gendeng', 'kenthir', 'edan', 'budeg',
    'sia', 'maneh', 'anjink', 'belegug', 'kehed',
    'jurigan', 'jurig', 'goreng',

    // =============================================
    // UJARAN KEBENCIAN & SARA
    // =============================================
    'kafir', 'murtad', 'sesat', 'halal dibunuh',
    'cina bangsat', 'pribumi', 'non-pri', 'aseng',
    'antek asing', 'pengkhianat bangsa', 'komunis',
    'pki', 'kadrun', 'cebong', 'kampung',
];

// Pola URL mencurigakan (regex)
$config['profanity_url_patterns'] = [
    '/bit\.ly/i',
    '/tinyurl\.com/i',
    '/wa\.me\/\d+/i',
    '/t\.me\//i',
];

<?php
// File: config.php

define('DATA_DIR', __DIR__ . '/popo/'); // Direktori tempat file JSON akan disimpan
define('USERS_FILE', DATA_DIR . 'users.json');
define('INVESTMENTS_FILE', DATA_DIR . 'investments.json'); // File ini akan mencatat riwayat investasi
define('LOANS_FILE', DATA_DIR . 'loans.json');
define('SWAPS_FILE', DATA_DIR . 'swaps.json');
define('TOPUPS_FILE', DATA_DIR . 'topups.json');
define('WITHDRAWALS_FILE', DATA_DIR . 'withdrawals.json');
define('AIRDROPS_FILE', DATA_DIR . 'airdrops.json'); // File untuk mencatat klaim airdrop

// Pastikan direktori popo/ ada dan writable
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

// Fungsi helper untuk membaca data dari JSON
function readJsonFile($filePath) {
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return [];
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    return $data ?: [];
}

// Fungsi helper untuk menulis data ke JSON
function writeJsonFile($filePath, $data) {
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

// Fungsi untuk format mata uang Rupiah
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 2, ',', '.');
}

// Fungsi untuk format token CFX
function formatCfx($amount) {
    return number_format($amount, 5, '.', '') . ' CFX'; // 5 desimal
}

?>

<?php

$jsonPath = __DIR__ . '/wilayah.json';
$binPath = __DIR__ . '/src/wilayah.bin';

if (!file_exists($jsonPath) || !file_exists($binPath)) {
    die("Error: File source tidak lengkap.\n");
}

$json = json_decode(file_get_contents($jsonPath), true);
$binHandle = fopen($binPath, 'rb');
$rowSize = 50;

echo "Memulai validasi data...\n";
$errorCount = 0;
$totalProcessed = 0;

function getNormalizedPostal($rawPostal) {
    // 1. Pecah jika ada rentang (misal: 12345 - 67890)
    $parts = explode('-', $rawPostal);
    $postal = trim($parts[0]);
    
    // 2. Jika setelah di-trim hasilnya bukan angka, anggap kosong
    if (!is_numeric($postal)) {
        return '     '; // 5 spasi
    }
    
    // 3. Ambil 5 digit pertama, pad dengan spasi
    return str_pad(substr($postal, 0, 5), 5, ' ', STR_PAD_RIGHT);
}

// Kita akan buat fungsi bantu untuk mengekstrak data dari JSON sesuai logic converter
function getJsonFlattened($json) {
    $data = [];
    foreach ($json['provinsi'] as $k => $v) $data[$k . '----'] = ['type' => 'P', 'postal' => '     ', 'name' => $v];
    foreach ($json['kabkot'] as $k => $v) $data[$k . '--'] = ['type' => 'K', 'postal' => '     ', 'name' => $v];
    // Di dalam loop foreach kecamatan pada fungsi getJsonFlattened
    foreach ($json['kecamatan'] as $k => $v) {
        $parts = explode(' -- ', $v);
        $name = trim($parts[0]);
        $rawPostal = isset($parts[1]) ? $parts[1] : '';
        
        $data[$k] = [
            'type' => 'C', 
            'postal' => trim(getNormalizedPostal($rawPostal)), // Trim untuk perbandingan
            'name' => $name
        ];
    }
    ksort($data);
    return $data;
}

$expectedData = getJsonFlattened($json);

// Baca file biner baris demi baris
while (!feof($binHandle)) {
    $row = fread($binHandle, $rowSize);
    if (strlen($row) < $rowSize) break;

    $code = substr($row, 0, 6);
    $type = substr($row, 6, 1);
    $postal = trim(substr($row, 7, 5));
    $name = trim(substr($row, 12, 38));

    if (!isset($expectedData[$code])) {
        echo "Error: Kode $code ditemukan di biner tapi tidak ada di JSON!\n";
        $errorCount++;
        continue;
    }

    $ex = $expectedData[$code];
    
    // Validasi per kolom
    if ($ex['type'] !== $type || $ex['name'] !== $name || trim($ex['postal']) !== $postal) {
        echo "Data tidak cocok pada kode $code:\n";
        echo "  Biner: Type=$type, Postal=$postal, Name=$name\n";
        echo "  JSON : Type={$ex['type']}, Postal={$ex['postal']}, Name={$ex['name']}\n";
        $errorCount++;
    }

    $totalProcessed++;
    unset($expectedData[$code]); // Hapus agar nanti bisa cek sisa yang tidak terproses
}

fclose($binHandle);

if (count($expectedData) > 0) {
    echo "Error: Ada " . count($expectedData) . " data di JSON yang tidak masuk ke Biner!\n";
    $errorCount += count($expectedData);
}

if ($errorCount === 0) {
    echo "Validasi Sukses! $totalProcessed record cocok dengan JSON.\n";
} else {
    echo "Validasi Gagal dengan $errorCount error.\n";
}
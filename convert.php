<?php

$jsonPath = __DIR__ . '/wilayah.json';
$outputPath = __DIR__ . '/src/wilayah.bin';

if (!file_exists($jsonPath)) {
    die("Error: File wilayah.json tidak ditemukan.\n");
}

$json = json_decode(file_get_contents($jsonPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Format JSON tidak valid!\n");
}

$rawData = [];

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

// 1. Ambil Provinsi
if (isset($json['provinsi'])) {
    foreach ($json['provinsi'] as $key => $value) {
        $rawData[$key . '----'] = ['type' => 'P', 'postal' => '     ', 'name' => $value];
    }
}

// 2. Ambil Kabkot
if (isset($json['kabkot'])) {
    foreach ($json['kabkot'] as $key => $value) {
        $rawData[$key . '--'] = ['type' => 'K', 'postal' => '     ', 'name' => $value];
    }
}

// 3. Ambil Kecamatan
if (isset($json['kecamatan'])) {
    foreach ($json['kecamatan'] as $key => $value) {
        $parts = explode(' -- ', $value);
        $name = trim($parts[0]);
        
        // AMBIL HANYA KODE POS PERTAMA (jika ada rentang, ambil yang kiri)
        $raw = isset($parts[1]) ? $parts[1] : '';
        $postalEncoded = getNormalizedPostal($raw); // Panggil fungsi di atas
        $rawData[$key] = ['type' => 'C', 'postal' => $postalEncoded, 'name' => $name];
    }
}

// WAJIB: Urutkan key secara alfabetis agar Binary Search bisa bekerja
ksort($rawData);

$fileHandle = fopen($outputPath, 'wb');
if (!$fileHandle) {
    die("Error: Gagal membuat file wilayah.bin\n");
}

foreach ($rawData as $code => $info) {
    // Potong atau panjangkan nama wilayah agar tepat 38 byte
    $nameEncoded = substr(str_pad($info['name'], 38, ' ', STR_PAD_RIGHT), 0, 38);
    $postalEncoded = substr(str_pad($info['postal'], 5, ' ', STR_PAD_RIGHT), 0, 5);
    
    // Total 6 + 1 + 5 + 38 = 50 Byte per baris
    $row = $code . $info['type'] . $postalEncoded . $nameEncoded;
    fwrite($fileHandle, $row);
}

fclose($fileHandle);
echo "Conversion complete! Binary file written to: $outputPath (" . (count($rawData) * 50) . " bytes)\n";
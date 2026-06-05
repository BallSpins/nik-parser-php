# NIK Parser: Industrial-Grade Low-Memory Binary Search

Parser NIK KTP Indonesia dengan performa tinggi dan konsumsi memori ekstrem minimal (< 4 MB). Dirancang untuk skalabilitas maksimal, parser ini tidak memuat dataset ke RAM, melainkan menggunakan teknik **Binary Search langsung pada berkas biner**. 

Telah tervalidasi stabil untuk pemrosesan hingga 1 juta data tanpa adanya degradasi performa memori.

## Mengapa Pendekatan Ini?

Kebanyakan parser NIK di Node.js menggunakan pendekatan *Memory-Resident Hash Map* (meload seluruh JSON ke RAM), yang menyebabkan konsumsi memori membengkak seiring bertambahnya dataset. `NikParser` menggunakan **Disk-Backed Binary Stream**:

* **Constant Memory Footprint**: Penggunaan RAM tetap stabil (~4MB) berapapun ukuran dataset (100 ribu atau 100 juta data).
* **Persistent I/O**: Menggunakan *Persistent File Handle* untuk menghindari *overhead* sistem.
* **Zero Dependency**: Berjalan murni dengan PHP standar tanpa *library* luar.

---

### API Reference

#### 1. NikParser (Parsing Data)

Metode ini digunakan untuk membaca dan mengekstrak informasi dari NIK yang sudah ada.

| Metode | Deskripsi |
| --- | --- |
| `isValid()` | Validasi integritas NIK secara keseluruhan. |
| `province()` | Mendapatkan nama provinsi berdasarkan NIK. |
| `kabupatenKota()` | Mendapatkan nama kabupaten/kota. |
| `kecamatan()` | Mendapatkan nama kecamatan. |
| `birthDate()` | Mendapatkan objek `DateTime` tanggal lahir. |
| `kelamin()` | Deteksi gender (`pria` / `wanita`). |
| `getDetails()` | Mengembalikan seluruh data NIK dalam format *associative array*. |

#### 2. NikGenerator (Pembuatan Data)

Metode statis untuk membuat NIK baru sesuai dengan format standar.

| Metode | Deskripsi |
| --- | --- |
| `generate($kecamatanId, $birthDate, $gender, $randomCode)` | Membuat NIK 16 digit. Menangani otomatis *offset* 40 untuk NIK wanita. |

---

### Cara Penggunaan Parser
```php
use Ballspins\NikParser\NikParser;

$parser = new NikParser('3578201503990001');
$data = $parser->getDetails();

print_r($data);
```

### Contoh Penggunaan Generator

```php
use Ballspins\NikParser\NikGenerator;

// Generate NIK Pria
$nik = NikGenerator::generate('357820', new DateTime('1999-03-15'), 'pria');

// Generate NIK Wanita (Otomatis +40 pada tanggal lahir)
$nikWanita = NikGenerator::generate('357820', new DateTime('2001-08-12'), 'wanita');

```

---

## Perbandingan Benchmark (1 Juta Iterasi)

Pengujian dilakukan untuk membandingkan efisiensi antara *Memory-Resident* (Node.js) vs *Disk-Backed Binary Stream* (PHP).

| Metrik | Node.js v22.17 (JSON-based) | PHP 8.3.20 (NikParser Optimized) |
| :--- | :--- | :--- |
| **Execution Time (Avg)** | ~0.22s | **~4.3s** |
| **Peak Memory Usage** | ~5.8 MB | **~4.0 MB** |
| **Memory Complexity** | O(N) - Naik seiring data | **O(1) - Konstan** |

> **Analisis Arsitektur:** Perbedaan kecepatan eksekusi disebabkan oleh perbedaan fundamental arsitektur: Node.js memuat dataset ke RAM (Memory-Resident), sedangkan NikParser membaca langsung dari disk (Disk-Resident). Pendekatan Disk-Resident dipilih dengan sengaja untuk menjamin batas konsumsi memori yang konstan dan mencegah risiko Out of Memory pada dataset masif.

> **Konteks Real-World:** Benchmark 1 juta iterasi di atas adalah stress test ekstrem. Dalam skenario aplikasi web nyata yang umumnya memproses 1-5 NIK per request, latensi I/O sebesar ~4 mikrodetik per pencarian tidak akan terasa oleh pengguna akhir (user experience tetap terasa instan), namun stabilitas penggunaan RAM yang rendah memberikan keuntungan besar pada skalabilitas server.

Menambahkan spesifikasi lingkungan pengujian (benchmark environment) adalah langkah yang sangat **valid dan profesional**. Dalam dunia *engineering*, hasil benchmark tanpa konteks spesifikasi perangkat sering dianggap "kosong" karena variabel *hardware* sangat memengaruhi hasil (terutama pada *disk I/O*).

Berikut adalah cara menambahkan informasi tersebut ke dalam README agar hasil benchmark kamu kredibel:

## Lingkungan Benchmark (Benchmark Environment)

Benchmark dijalankan di lingkungan lokal untuk memastikan konsistensi hasil. Perlu diingat bahwa performa pada `NikParser` sangat bergantung pada kecepatan *disk I/O* (SSD vs HDD) karena arsitekturnya yang berbasis *Disk-Resident*.

| Komponen | Spesifikasi |
| --- | --- |
| **OS** | Windows 11 Home Single Language (Build 26100) |
| **CPU** | AMD Ryzen 3 7320U (4 Cores, 8 Threads) |
| **RAM** | 8GB (6.8 Installed) LPDDR5 5500 MT/s |
| **Storage** | NVMe SSD Gen 4 |
| **PHP Version** | 8.3.20 (CLI) |
| **Node.js Version** | 22.17.0 |

> **Catatan Hardware:** Benchmark ini menggunakan NVMe SSD. Jika dijalankan pada HDD konvensional, latensi I/O untuk PHP akan meningkat secara signifikan dibandingkan hasil di atas. Namun, efisiensi memori (4MB) akan tetap konstan pada perangkat apa pun.


## Cara Verifikasi Performa

Kamu bisa memverifikasi hasil benchmark di lingkungan lokal menggunakan skrip berikut (`benchmark.php`):

```php
require __DIR__ . '/vendor/autoload.php';
use Ballspins\NikParser\NikParser;

$iterations = 1000000;
$parser = new NikParser('3578201503990001');

$start = microtime(true);
for($i = 0; $i < $iterations; $i++) { 
    $parser->kecamatan();
}
$end = microtime(true);

echo "Execution Time: " . round($end - $start, 4) . " seconds\n";
echo "Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";

```

## Spesifikasi Teknis: Fixed-Width Binary

Setiap baris data dalam `wilayah.bin` memiliki panjang statis **50 byte**, memungkinkan akses instan ke posisi byte spesifik menggunakan `fseek()`.

1. **Kode Wilayah (6 byte)**: Indeks pencarian ($O(\log N)$).
2. **Tipe (1 byte)**: Penanda wilayah.
3. **Kodepos (5 byte)**: *Padded string*.
4. **Nama Wilayah (38 byte)**: *Padded string*.

---

### Catatan Penting

* **Stabilitas I/O**: Parser menggunakan *Static Property* untuk menjaga *file pointer*, mencegah kebocoran *handle* pada lingkungan *long-running process* seperti Swoole atau RoadRunner.
* **Skalabilitas**: Cocok untuk *batch processing* jutaan data yang tidak memungkinkan untuk dimuat ke RAM secara keseluruhan.

---

#### Utility Scripts

Proyek ini menyediakan perintah shortcut melalui Composer untuk pemeliharaan data:
* `composer convert`: Mengonversi `wilayah.json` menjadi `src/wilayah.bin`.
* `composer validate`: Memastikan integritas `src/wilayah.bin` terhadap sumber data.
* `composer benchmark`: Menjalankan pengujian performa sistem.

## Referensi

Dataset wilayah dan referensi logika parsing dalam proyek ini diadaptasi dari implementasi [mul14/nik_parser](https://github.com/mul14/nik_parser.ts). Penggunaan dataset tersebut ditujukan untuk mempermudah pemetaan kode wilayah NIK KTP Indonesia.
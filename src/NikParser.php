<?php

namespace Ballspins\NikParser;

use DateTime;
use Exception;

class NikParser
{
    private string $nik;
    private const ROW_SIZE = 50; // Jarak per baris data statis (50 byte)

    private static $fileHandle = null;
    private static $fileSize = null; // Cache ukuran file
    private static array $cache = []; // Cache hasil pencarian
    private static $binaryData = null; // Buffer seluruh file

    public function __construct(string $nik)
    {
        $this->nik = preg_replace('/\D/', '', $nik);
    }

    public function isValid(): bool
    {
        return strlen($this->nik) === 16
            && !empty($this->province())
            && !empty($this->kabupatenKota())
            && !empty($this->kecamatan())
            && !empty($this->birthDate());
    }

    public function provinceId(): string
    {
        return substr($this->nik, 0, 2);
    }

    public function province(): ?string
    {
        $data = $this->binarySearch($this->provinceId() . '----');
        return $data ? $data['name'] : null;
    }

    public function kabupatenKotaId(): string
    {
        return substr($this->nik, 0, 4);
    }

    public function kabupatenKota(): ?string
    {
        $data = $this->binarySearch($this->kabupatenKotaId() . '--');
        return $data ? $data['name'] : null;
    }

    public function kecamatanId(): string
    {
        return substr($this->nik, 0, 6);
    }

    public function kecamatan(): ?string
    {
        $data = $this->binarySearch($this->kecamatanId());
        return $data ? $data['name'] : null;
    }

    public function kodepos(): ?string
    {
        $data = $this->binarySearch($this->kecamatanId());
        return $data ? $data['postal_code'] : null;
    }

    private function binarySearch(string $searchCode): ?array
    {
        // 1. Cek cache hasil (Memoization)
        if (isset(self::$cache[$searchCode])) return self::$cache[$searchCode];

        // 2. Inisialisasi handle sekali saja
        if (self::$fileHandle === null) {
            $filePath = __DIR__ . '/wilayah.bin';
            if (!file_exists($filePath)) return null;
            self::$fileHandle = fopen($filePath, 'rb');
            self::$fileSize = filesize($filePath); // Simpan ukuran di static
        }

        $low = 0;
        $high = (self::$fileSize / self::ROW_SIZE) - 1;
        $found = null;
        
        while ($low <= $high) {
            $mid = (int)(($low + $high) / 2);
            
            fseek(self::$fileHandle, $mid * self::ROW_SIZE);
            $row = fread(self::$fileHandle, self::ROW_SIZE);

            if (strlen($row) < self::ROW_SIZE) break;

            $currentCode = substr($row, 0, 6);

            if ($currentCode === $searchCode) {
                $found = [
                    'type'        => substr($row, 6, 1),
                    'postal_code' => trim(substr($row, 7, 5)),
                    'name'        => trim(substr($row, 12, 38)),
                ];
                break;
            }

            if ($currentCode < $searchCode) $low = $mid + 1;
            else $high = $mid - 1;
        }

        // 3. Simpan ke cache sebelum return
        return self::$cache[$searchCode] = $found;
    }

    public function kelamin(): ?string
    {
        if (strlen($this->nik) !== 16) {
            return null;
        }
        $day = (int)substr($this->nik, 6, 2);
        return $day > 40 ? 'wanita' : 'pria';
    }

    public function birthDate(): ?DateTime
    {
        if (strlen($this->nik) !== 16) {
            return null;
        }

        try {
            $day = (int)substr($this->nik, 6, 2);
            $month = (int)substr($this->nik, 8, 2);
            $year = (int)substr($this->nik, 10, 2);

            if ($day > 40) {
                $day -= 40;
            }

            if ($month < 1 || $month > 12) {
                return null;
            }

            $currentYearTwoDigits = (int)date('y');
            if ($year <= $currentYearTwoDigits) {
                $fullYear = 2000 + $year;
            } else {
                $fullYear = 1900 + $year;
            }

            if (!checkdate($month, $day, $fullYear)) {
                return null;
            }

            return new DateTime(sprintf('%04d-%02d-%02d', $fullYear, $month, $day));
        } catch (Exception $e) {
            return null;
        }
    }

    public function uniqueCode(): string
    {
        return substr($this->nik, 12, 4);
    }

    public function getDetails(): array
    {
        return [
            'nik' => $this->nik,
            'is_valid' => $this->isValid(),
            'province_id' => $this->provinceId(),
            'province' => $this->province(),
            'kabupaten_kota_id' => $this->kabupatenKotaId(),
            'kabupaten_kota' => $this->kabupatenKota(),
            'kecamatan_id' => $this->kecamatanId(),
            'kecamatan' => $this->kecamatan(),
            'postal_code' => $this->kodepos(),
            'gender' => $this->kelamin(),
            'birth_date' => $this->birthDate()?->format('Y-m-d'),
            'unique_code' => $this->uniqueCode(),
        ];
    }
}
<?php

namespace Ballspins\NikParser;

use DateTime;
use Exception;

class NikGenerator
{
    /**
     * @param string $kecamatanId Kode kecamatan (6 digit)
     * @param DateTime $birthDate Tanggal lahir
     * @param string $gender 'pria' atau 'wanita'
     * @param string $randomCode 4 digit kode unik (default: random)
     */
    public static function generate(
        string $kecamatanId, 
        DateTime $birthDate, 
        string $gender = 'pria', 
        string $randomCode = null
    ): string {
        // 1. Validasi Panjang Kode Kecamatan
        if (strlen($kecamatanId) !== 6) {
            throw new Exception("Kode kecamatan harus 6 digit.");
        }

        // 2. Format Tanggal
        $day = (int)$birthDate->format('d');
        $month = (int)$birthDate->format('m');
        $year = (int)$birthDate->format('y');

        // 3. Penyesuaian NIK Wanita (Offset 40)
        if (strtolower($gender) === 'wanita') {
            $day += 40;
        }

        // 4. Random Code (Jika tidak disediakan)
        if ($randomCode === null) {
            $randomCode = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        // 5. Gabungkan NIK (6 + 2 + 2 + 2 + 4)
        return $kecamatanId . 
               sprintf('%02d', $day) . 
               sprintf('%02d', $month) . 
               sprintf('%02d', $year) . 
               $randomCode;
    }
}
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Ballspins\NikParser\NikParser;

class NikParserTest extends TestCase
{
    public function test_it_can_parse_valid_male_nik()
    {
        // Contoh NIK Pria Surabaya (Kecamatan Sawahan: 35.78.20), Lahir 15 Maret 1999
        $nikPria = '3578201503990001'; 
        
        $parser = new NikParser($nikPria);

        $this->assertTrue($parser->isValid());
        $this->assertEquals('pria', $parser->kelamin());
        $this->assertEquals('1999-03-15', $parser->birthDate()?->format('Y-m-d'));
        $this->assertEquals('35', $parser->provinceId());
        $this->assertEquals('3578', $parser->kabupatenKotaId());
        $this->assertEquals('357820', $parser->kecamatanId());
    }

    public function test_it_can_parse_valid_female_nik()
    {
        // Contoh NIK Wanita Surabaya, Lahir 12 Agustus 2001
        // Tanggal 12 + 40 = 52
        $nikWanita = '3578205208010002'; 

        $parser = new NikParser($nikWanita);

        $this->assertTrue($parser->isValid());
        $this->assertEquals('wanita', $parser->kelamin());
        $this->assertEquals('2001-08-12', $parser->birthDate()?->format('Y-m-d'));
    }

    public function test_it_returns_false_for_invalid_or_fake_nik()
    {
        // NIK asal-asalan / kurang dari 16 digit
        $nikPalsu = '12345';
        $parser = new NikParser($nikPalsu);
        $this->assertFalse($parser->isValid());

        // NIK dengan tanggal tidak masuk akal (Misal digit tanggal ditulis 99)
        $nikTanggalNgawur = '3578209903990001';
        $parser2 = new NikParser($nikTanggalNgawur);
        $this->assertNull($parser2->birthDate());
        $this->assertFalse($parser2->isValid());
    }
}
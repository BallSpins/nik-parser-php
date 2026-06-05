<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Ballspins\NikParser\NikGenerator;
use DateTime;
use Exception;

class NikGeneratorTest extends TestCase
{
    public function test_it_can_generate_a_valid_male_nik()
    {
        $nik = NikGenerator::generate('357820', new DateTime('1999-03-15'), 'pria', '0001');
        $this->assertEquals('3578201503990001', $nik);
    }

    public function test_it_can_generate_a_valid_female_nik_with_offset()
    {
        // 12 + 40 = 52
        $nik = NikGenerator::generate('357820', new DateTime('2001-08-12'), 'wanita', '0002');
        $this->assertEquals('3578205208010002', $nik);
    }

    public function test_it_throws_exception_for_invalid_kecamatan_length()
    {
        $this->expectException(Exception::class);
        NikGenerator::generate('123', new DateTime('1999-03-15'));
    }
}
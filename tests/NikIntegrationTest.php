<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Ballspins\NikParser\NikParser;
use Ballspins\NikParser\NikGenerator;
use DateTime;

class NikIntegrationTest extends TestCase
{
    public function test_it_can_generate_and_then_successfully_parse_the_nik()
    {
        // Setup
        $kecamatan = '357820';
        $birthDate = new DateTime('1999-03-15');
        $gender = 'pria';
        $random = '9999';

        // 1. Generate NIK
        $generatedNik = NikGenerator::generate($kecamatan, $birthDate, $gender, $random);

        // 2. Parse NIK
        $parser = new NikParser($generatedNik);
        $details = $parser->getDetails();

        // 3. Assertion
        $this->assertTrue($details['is_valid']);
        $this->assertEquals($kecamatan, $details['kecamatan_id']);
        $this->assertEquals('1999-03-15', $details['birth_date']);
        $this->assertEquals($gender, $details['gender']);
        $this->assertEquals($random, $details['unique_code']);
    }
}
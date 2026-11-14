<?php

namespace Tests\Unit;

use App\Helpers\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahHelperTest extends TestCase
{
    public function test_formats_zero_correctly(): void
    {
        $this->assertEquals('Rp 0', Rupiah::short(0));
        $this->assertEquals('Rp 0', Rupiah::short(null));
    }

    public function test_formats_thousands_correctly(): void
    {
        $this->assertEquals('Rp 500 RB', Rupiah::short(500000));
        $this->assertEquals('Rp 150 RB', Rupiah::short(150000));
    }

    public function test_formats_millions_as_jt(): void
    {
        $this->assertEquals('Rp 1 JT', Rupiah::short(1000000));
        $this->assertEquals('Rp 18,4 JT', Rupiah::short(18400000));
        $this->assertEquals('Rp 90,4 JT', Rupiah::short(90400000));
    }

    public function test_formats_billions_as_m(): void
    {
        $this->assertEquals('Rp 1 M', Rupiah::short(1000000000));
        $this->assertEquals('Rp 2,5 M', Rupiah::short(2500000000));
    }
}


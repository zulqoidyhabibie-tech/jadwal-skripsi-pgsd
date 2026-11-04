<?php

namespace Tests\Unit;

use App\Models\Syarat;
use PHPUnit\Framework\TestCase;

class SyaratTest extends TestCase
{
    public function test_syarat_angka_menerima_koma_desimal(): void
    {
        $ipk = new Syarat(['jenis' => 'angka', 'nilai_minimal' => 2.75]);
        $this->assertTrue($ipk->terpenuhiOleh('2,75'));
        $this->assertTrue($ipk->terpenuhiOleh('3.41'));
        $this->assertFalse($ipk->terpenuhiOleh('2,74'));
        $this->assertFalse($ipk->terpenuhiOleh('tiga'));
        $this->assertFalse($ipk->terpenuhiOleh(''));
    }

    public function test_syarat_link_harus_http(): void
    {
        $link = new Syarat(['jenis' => 'link']);
        $this->assertTrue($link->terpenuhiOleh('https://drive.google.com/file/d/abc'));
        $this->assertFalse($link->terpenuhiOleh('drive.google.com/file/d/abc'));
        $this->assertFalse($link->terpenuhiOleh('javascript:alert(1)'));
    }

    public function test_syarat_centang(): void
    {
        $c = new Syarat(['jenis' => 'centang']);
        $this->assertTrue($c->terpenuhiOleh('1'));
        $this->assertFalse($c->terpenuhiOleh(null));
    }
}

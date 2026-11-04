<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GantiPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_bisa_mengganti_password_sendiri(): void
    {
        $user = User::create(['name' => 'Admin', 'username' => 'admin2', 'email' => 'a@x.id', 'role' => 'admin', 'is_active' => true, 'password' => 'gantipassword123']);

        $this->actingAs($user)->get('/password')->assertOk()->assertSee('Ganti password');

        $this->actingAs($user)->put('/password', [
            'password_lama' => 'gantipassword123',
            'password' => 'rahasiaBaru99',
            'password_confirmation' => 'rahasiaBaru99',
        ])->assertRedirect('/beranda')->assertSessionHas('ok');

        $this->assertTrue(Hash::check('rahasiaBaru99', $user->fresh()->password));
    }

    public function test_password_lama_salah_ditolak(): void
    {
        $user = User::create(['name' => 'Mhs', 'username' => '2021000001', 'email' => 'm@x.id', 'role' => 'mahasiswa', 'is_active' => true, 'password' => 'passwordlama1']);

        $this->actingAs($user)->from('/password')->put('/password', [
            'password_lama' => 'salah12345',
            'password' => 'rahasiaBaru99',
            'password_confirmation' => 'rahasiaBaru99',
        ])->assertRedirect('/password')->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check('passwordlama1', $user->fresh()->password));
    }
}

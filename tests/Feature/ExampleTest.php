<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Rute "/" harus mengarahkan pengguna: belum login → halaman login,
 * sudah login → dashboard.
 */
class RootRedirectTest extends TestCase
{
    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}

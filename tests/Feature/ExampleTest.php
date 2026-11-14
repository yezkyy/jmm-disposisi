<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Tamu diarahkan ke halaman login saat membuka root.
     */
    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * Halaman login dapat diakses dengan sukses.
     */
    public function test_login_page_returns_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('JMM Disposisi');
        $response->assertSee('PT Jaya Mukti Mandiri');
    }
}

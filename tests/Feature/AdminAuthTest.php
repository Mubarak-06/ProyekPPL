<?php

namespace Tests\Feature;

use App\Http\Middleware\PastikanAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\PelangganTestCase;

class AdminAuthTest extends PelangganTestCase
{
    public function test_semua_route_admin_menolak_tamu_dan_pelanggan(): void
    {
        $routes = collect(app('router')->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'));
        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(PastikanAdmin::class, $route->gatherMiddleware());
            $path = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $this->call($route->methods()[0], $path)->assertRedirect('/login');
        }

        $this->actingAs(User::factory()->create());
        foreach ($routes as $route) {
            $path = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $this->call($route->methods()[0], $path)->assertForbidden();
        }
        $this->assertDatabaseCount('produk', 0);
        $this->assertDatabaseCount('pesanan', 0);
    }

    public function test_login_admin_menuju_dashboard_dan_guest_middleware_mengenali_admin(): void
    {
        $password = Str::random(32);
        $admin = $this->akunAdmin();
        $admin->password = $password;
        $admin->save();
        $this->withSession(['url.intended' => route('pelanggan.profil')])
            ->post('/login', ['email' => $admin->email, 'password' => $password])
            ->assertRedirect('/admin/dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/dashboard')->assertOk()->assertSee('tombol-keluar');
        $this->get('/login')->assertRedirect('/admin/dashboard');
    }

    public function test_login_pelanggan_tidak_dialihkan_ke_admin_dan_tujuan_checkout_tetap_berfungsi(): void
    {
        $password = Str::random(32);
        $user = User::factory()->create(['password' => $password]);
        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post('/login', ['email' => $user->email, 'password' => $password])->assertRedirect('/akun');
        $this->get('/login')->assertRedirect('/akun');
        $this->post('/logout')->assertRedirect('/');
        $this->withSession(['url.intended' => route('pelanggan.checkout')])
            ->post('/login', ['email' => $user->email, 'password' => $password])->assertRedirect('/checkout');
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_registrasi_dan_profil_tidak_dapat_memberikan_hak_admin(): void
    {
        $password = Str::random(32);
        $this->post('/daftar', [
            'name' => 'Pelanggan', 'email' => 'pelanggan-baru@example.test',
            'password' => $password, 'password_confirmation' => $password, 'is_admin' => true,
        ])->assertRedirect('/akun');
        $user = User::where('email', 'pelanggan-baru@example.test')->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->put('/akun', ['name' => $user->name, 'email' => $user->email, 'is_admin' => true])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_admin);
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_password_salah_tidak_memberi_sesi_admin(): void
    {
        $admin = $this->akunAdmin();
        $this->post('/login', ['email' => $admin->email, 'password' => Str::random(32)])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_logout_admin_memerlukan_csrf_dan_token_valid_tidak_menghasilkan_419(): void
    {
        // Aktifkan pemeriksaan CSRF asli yang biasanya dilewati oleh lingkungan testing.
        $this->app['env'] = 'local';
        $token = Str::random(40);
        $this->actingAs($this->akunAdmin())->withSession(['_token' => $token, 'penanda' => 'hapus-saat-logout']);
        $this->post('/logout')->assertStatus(419);
        $this->assertAuthenticated();
        $this->get('/admin/dashboard')->assertOk()->assertSee('name="_token"', false);
        $this->post('/logout', ['_token' => $token])->assertRedirect('/login')->assertSessionMissing('penanda');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_perintah_admin_menghash_password_dan_menolak_email_yang_sudah_ada(): void
    {
        $password = Str::random(32);
        $this->artisan('admin:buat', ['email' => 'admin-uji@example.test'])
            ->expectsQuestion('Password admin (minimal 12 karakter)', $password)
            ->expectsQuestion('Ulangi password admin', $password)
            ->assertSuccessful();
        $admin = User::where('email', 'admin-uji@example.test')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertSame('Administrator CV Karunia Abadi', $admin->name);
        $this->assertTrue(Hash::check($password, $admin->getRawOriginal('password')));
        $this->artisan('admin:buat', ['email' => $admin->email])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_perintah_admin_tidak_menerima_password_lemah_atau_noninteraktif(): void
    {
        $this->artisan('admin:buat', ['email' => 'admin-uji@example.test'])
            ->expectsQuestion('Password admin (minimal 12 karakter)', 'pendek')
            ->expectsQuestion('Ulangi password admin', 'pendek')
            ->assertFailed();
        $this->artisan('admin:buat', ['email' => 'admin-uji@example.test', '--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}

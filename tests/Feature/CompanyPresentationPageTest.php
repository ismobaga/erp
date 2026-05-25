<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPresentationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_presentation_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('Demander une démo');
        $response->assertSeeText('DMS');
        $response->assertSeeText('Gestion des pharmacies');
    }

    public function test_dms_presentation_page_is_available(): void
    {
        $response = $this->get('/dms-presentation');

        $response->assertOk();
        $response->assertSeeText('Fonctionnalités de DMS');
        $response->assertSeeText('Prêt à transformer votre pharmacie ?');
    }

    public function test_company_presentation_contact_form_accepts_a_valid_request(): void
    {
        $response = $this->post('/contact-request', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'intent' => 'Implémentation ERP',
            'message' => 'Nous souhaitons structurer nos opérations.',
        ]);

        $response->assertRedirect('/#contact');
        $response->assertSessionHas('status');
    }

    public function test_core_public_company_pages_are_available(): void
    {
        $this->get('/about')->assertOk()->assertSeeText('CROMMIX MALI S.A.');
        $this->get('/services')->assertOk()->assertSeeText('Nos services');
        $this->get('/solutions')->assertOk()->assertSeeText('Solutions & produits');
        $this->get('/contact')->assertOk()->assertSeeText('Contact');
    }

    public function test_admin_and_dashboard_routes_stay_protected_for_guests(): void
    {
        $adminResponse = $this->get('/admin');
        $adminResponse->assertStatus(302);
        $this->assertStringContainsString('/admin/login', (string) $adminResponse->headers->get('Location'));

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(302);
        $this->assertStringContainsString('/admin/login', (string) $dashboardResponse->headers->get('Location'));
    }
}

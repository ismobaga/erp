<?php

namespace Tests\Feature;

use Tests\TestCase;

class CompanyPresentationPageTest extends TestCase
{
    public function test_company_presentation_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('Demander une démo');
        $response->assertSeeText('Catalogue DMS');
        $response->assertSeeText('Ouvrir la présentation DMS');
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
}

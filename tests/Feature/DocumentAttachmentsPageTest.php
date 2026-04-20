<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentAttachments;
use App\Models\Attachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentAttachmentsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_finance_user_can_access_the_documents_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        Attachment::create([
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'file_name' => 'Facture_Q1_2026.pdf',
            'file_path' => 'attachments/test/facture-q1-2026.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/documents');

        $response->assertOk();
        $response->assertSee('Gestion des Documents');
        $response->assertSee('Facture_Q1_2026.pdf');
    }

    public function test_user_can_upload_a_document_attachment(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $file = UploadedFile::fake()->create('rapport-annuel.pdf', 256, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentAttachments::class)
            ->set('upload', $file)
            ->set('documentCategory', 'Factures')
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $attachment = Attachment::query()->where('file_name', 'rapport-annuel.pdf')->first();

        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime_type);
        Storage::disk('local')->assertExists($attachment->file_path);
    }
}

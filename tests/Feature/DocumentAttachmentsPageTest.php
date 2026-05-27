<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentAttachments;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\User;
use App\Services\SecureFileUploadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentAttachmentsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app('currentCompany')->update([
            'advanced_options' => ['documents' => true],
        ]);
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

        // Use a real PDF magic bytes header so finfo_file() detects the correct MIME type.
        $file = UploadedFile::fake()->createWithContent('rapport-annuel.pdf', "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n");

        Livewire::actingAs($user)
            ->test(DocumentAttachments::class)
            ->set('upload', $file)
            ->set('documentCategory', 'Factures')
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $attachment = Attachment::query()->where('file_name', 'rapport-annuel.pdf')->first();

        $this->assertNotNull($attachment);
        $this->assertNotNull($attachment->company_id);
        $this->assertStringStartsWith((string) config('erp.documents.directory', 'attachments').'/', $attachment->file_path);
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_document_download_requires_a_valid_signature(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $attachment = Attachment::create([
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'file_name' => 'secure-ledger.pdf',
            'file_path' => 'attachments/2026/04/secure-ledger.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('local')->put($attachment->file_path, 'secured-content');

        $this->actingAs($user)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute('attachments.download', now()->addMinutes(5), ['attachment' => $attachment]);

        $this->actingAs($user)
            ->get($signedUrl)
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'document_downloaded',
            'subject_type' => Attachment::class,
            'subject_id' => $attachment->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_upload_is_blocked_when_secure_storage_quota_is_exceeded(): void
    {
        Storage::fake('local');
        config()->set('erp.documents.quota_mb', 1);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $company = app('currentCompany');

        Attachment::create([
            'company_id' => $company->id,
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'file_name' => 'existing-archive.pdf',
            'file_path' => 'attachments/2026/04/existing-archive.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1048576,
            'uploaded_by' => $user->id,
        ]);

        // Use real PDF magic bytes so validation passes until quota check.
        $file = UploadedFile::fake()->createWithContent('overflow.pdf', "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n");

        Livewire::actingAs($user)
            ->test(DocumentAttachments::class)
            ->set('upload', $file)
            ->set('documentCategory', 'Archives')
            ->call('uploadDocument')
            ->assertHasErrors(['upload']);
    }

    public function test_file_with_wrong_magic_bytes_is_rejected(): void
    {
        // An EXE disguised as a PDF — magic bytes are MZ not %PDF
        $file = UploadedFile::fake()->createWithContent('malware.pdf', "\x4D\x5A\x90\x00");
        $this->expectException(ValidationException::class);
        app(SecureFileUploadService::class)->storeFile($file, User::class, 1, 1, 1);
    }

    public function test_upload_is_rejected_without_company_context(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        // Remove the company context binding
        app()->offsetUnset('currentCompany');

        $file = UploadedFile::fake()->createWithContent('no-company-doc.pdf', "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n");

        Livewire::actingAs($user)
            ->test(DocumentAttachments::class)
            ->set('upload', $file)
            ->set('documentCategory', 'Factures')
            ->call('uploadDocument')
            ->assertNotFound();

        // Restore company context for subsequent tests.
        $this->setUpCompany();
    }

    public function test_quota_is_scoped_per_company(): void
    {
        Storage::fake('local');
        config()->set('erp.documents.quota_mb', 1);

        $company1 = app('currentCompany');
        $company2 = Company::create(['name' => 'Other Company', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        // Company 2's attachment should NOT count against company 1's quota
        Attachment::withoutCompanyScope()->create([
            'company_id' => $company2->id,
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'file_name' => 'other-company.pdf',
            'file_path' => 'attachments/2026/04/other-company.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1048576, // 1 MB — would exceed quota if counted
            'uploaded_by' => $user->id,
        ]);

        // Use real PDF magic bytes
        $file = UploadedFile::fake()->createWithContent('company1-doc.pdf', "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n");

        // Company 1 has not used any quota; upload should succeed
        Livewire::actingAs($user)
            ->test(DocumentAttachments::class)
            ->set('upload', $file)
            ->set('documentCategory', 'Factures')
            ->call('uploadDocument')
            ->assertHasNoErrors();
    }

    public function test_attachment_download_rejects_path_traversal(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $company = app('currentCompany');

        $attachment = Attachment::create([
            'company_id' => $company->id,
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'file_name' => 'traversal.pdf',
            'file_path' => '../../etc/passwd',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $user->id,
        ]);

        $signedUrl = URL::temporarySignedRoute('attachments.download', now()->addMinutes(5), ['attachment' => $attachment]);

        $this->actingAs($user)
            ->get($signedUrl)
            ->assertForbidden();
    }
}

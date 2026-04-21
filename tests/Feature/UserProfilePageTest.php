<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_active_user_can_access_profile_page(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'phone' => '+22370000001',
        ]);
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get('/admin/profile');

        $response->assertOk();
        $response->assertSee($user->email);
        $response->assertSee('+22370000001');
    }
}

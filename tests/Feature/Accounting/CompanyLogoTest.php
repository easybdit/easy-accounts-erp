<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_a_logo(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png');

        $this->post(route('accounting.settings.logo.update'), ['logo' => $file])
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_settings_permission_cannot_upload_a_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);
        $file = UploadedFile::fake()->image('logo.png');

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), ['logo' => $file])
            ->assertForbidden();

        $this->assertNull(AccountingSettings::current()->logo_path);
    }

    public function test_an_accountant_can_upload_a_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);
        $file = UploadedFile::fake()->image('logo.png');

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), ['logo' => $file])
            ->assertRedirect();

        $settings = AccountingSettings::current()->fresh();
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_uploading_a_new_logo_replaces_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), [
            'logo' => UploadedFile::fake()->image('first.png'),
        ]);
        $firstPath = AccountingSettings::current()->fresh()->logo_path;

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), [
            'logo' => UploadedFile::fake()->image('second.png'),
        ]);
        $secondPath = AccountingSettings::current()->fresh()->logo_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), [
            'logo' => UploadedFile::fake()->create('logo.pdf', 100),
        ])->assertSessionHasErrors('logo');
    }

    public function test_an_accountant_can_remove_the_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        $this->actingAs($user)->post(route('accounting.settings.logo.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $path = AccountingSettings::current()->fresh()->logo_path;

        $this->actingAs($user)->delete(route('accounting.settings.logo.destroy'))
            ->assertRedirect();

        $this->assertNull(AccountingSettings::current()->fresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }
}

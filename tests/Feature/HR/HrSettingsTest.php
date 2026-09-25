<?php

namespace Tests\Feature\HR;

use App\Models\HR\HrSettings;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_seeded_on_first_access(): void
    {
        $settings = HrSettings::current();

        $this->assertSame(['A' => 1000, 'B' => 700, 'C' => 500], $settings->special_working_day_grade_rates);
        $this->assertSame(3, $settings->late_warning_threshold);
        $this->assertFalse($settings->multi_step_leave_approval_enabled);
    }

    public function test_an_administrator_can_update_hr_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('hr.settings.update'), [
            'special_working_day_grade_rates' => ['A' => 1500, 'B' => 900, 'C' => 600],
            'late_deduction_ratio' => 5,
            'late_warning_threshold' => 4,
            'multi_step_leave_approval_enabled' => true,
        ])->assertRedirect(route('hr.settings.edit'));

        $settings = HrSettings::current();
        $this->assertSame(1500.0, (float) $settings->special_working_day_grade_rates['A']);
        $this->assertSame(5, $settings->late_deduction_ratio);
        $this->assertTrue($settings->multi_step_leave_approval_enabled);
    }

    public function test_setting_a_late_deduction_ratio_overrides_the_package_default(): void
    {
        $this->assertSame(3, config('attendance.salary.late_deduction_ratio'));

        HrSettings::current()->update(['late_deduction_ratio' => 7]);

        // The override applies at the next boot, not mid-request — call the
        // provider's private override method directly (via reflection)
        // rather than its public boot(), so this test doesn't also
        // re-trigger Vite::prefetch()/the accounting-settings overrides.
        $provider = new AppServiceProvider(app());
        $method = new \ReflectionMethod($provider, 'applyHrSettingsOverrides');
        $method->invoke($provider);

        $this->assertSame(7, config('attendance.salary.late_deduction_ratio'));
    }

    public function test_a_role_without_employees_manage_cannot_change_settings(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->put(route('hr.settings.update'), [
            'special_working_day_grade_rates' => ['A' => 1, 'B' => 1, 'C' => 1],
            'late_warning_threshold' => 3,
        ])->assertForbidden();
    }
}

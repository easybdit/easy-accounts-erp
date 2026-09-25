<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\AttendanceDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_add_a_pull_mode_device(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.attendance-devices.store'), [
            'name' => 'Main Office Entrance',
            'ip' => '192.168.1.201',
            'port' => 4370,
            'status' => 'active',
        ])->assertRedirect(route('hr.attendance-devices.index'));

        $this->assertDatabaseHas('easyattendance_devices', ['name' => 'Main Office Entrance', 'ip' => '192.168.1.201']);
        $this->assertSame('pull', AttendanceDevice::firstWhere('name', 'Main Office Entrance')->connection_mode);
    }

    public function test_a_device_needs_either_an_ip_or_a_serial_number(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.attendance-devices.store'), [
            'name' => 'Unconfigured Device',
            'status' => 'active',
        ])->assertSessionHasErrors(['ip', 'serial_number']);
    }

    public function test_testing_a_device_with_an_unreachable_ip_reports_failure_without_erroring(): void
    {
        $admin = User::factory()->create();
        // Loopback with nothing listening: a fast "connection refused"
        // rather than a slow timeout, so this test stays quick.
        $device = AttendanceDevice::create([
            'name' => 'Unreachable', 'ip' => '127.0.0.1', 'port' => 9, 'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('hr.attendance-devices.test', $device))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_a_role_without_employees_manage_cannot_add_a_device(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->post(route('hr.attendance-devices.store'), [
            'name' => 'Blocked', 'ip' => '192.168.1.1', 'status' => 'active',
        ])->assertForbidden();
    }
}

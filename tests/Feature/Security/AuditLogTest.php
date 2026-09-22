<?php

namespace Tests\Feature\Security;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_account_records_an_activity(): void
    {
        $account = Account::factory()->create(['name' => 'Petty Cash']);

        $activity = Activity::where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('created', $activity->event);
    }

    public function test_updating_an_account_records_a_dirty_only_activity(): void
    {
        $account = Account::factory()->create(['name' => 'Petty Cash']);

        $account->update(['name' => 'Office Petty Cash']);

        $activity = Activity::where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('name', $activity->attribute_changes['attributes'] ?? []);
    }

    public function test_audit_log_page_lists_recorded_activity(): void
    {
        // Creating the acting user itself logs a User "created" activity
        // (User also uses LogsActivity), so the page must show both entries.
        $user = User::factory()->create();
        Account::factory()->create(['name' => 'Petty Cash']);

        $this->actingAs($user)->get(route('security.audit-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Security/AuditLog/Index')
                ->has('activities.data', 2)
            );
    }

    public function test_audit_log_page_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->get(route('security.audit-log.index'))->assertForbidden();
    }
}

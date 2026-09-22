<?php

namespace Tests\Feature\Security;

use App\Models\Accounting\Account;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Log retention policy (Section 90 Phase 11): the audit trail otherwise
 * grows unbounded. Uses spatie/activitylog's built-in "activitylog:clean"
 * command (config('activitylog.clean_after_days')), scheduled daily in
 * routes/console.php.
 */
class AuditLogRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cleanup_command_is_scheduled_daily(): void
    {
        $events = app(Schedule::class)->events();

        $scheduled = collect($events)->first(fn ($event) => str_contains($event->command ?? '', 'activitylog:clean'));

        $this->assertNotNull($scheduled, 'Expected activitylog:clean to be scheduled.');
        $this->assertSame('0 0 * * *', $scheduled->expression);
    }

    public function test_the_clean_command_removes_activities_older_than_the_retention_window(): void
    {
        $account = Account::factory()->create(['name' => 'Petty Cash']);
        Activity::where('subject_id', $account->id)->update(['created_at' => now()->subDays(400)]);

        $this->artisan('activitylog:clean', ['--days' => 365, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('activity_log', ['subject_id' => $account->id]);
    }
}

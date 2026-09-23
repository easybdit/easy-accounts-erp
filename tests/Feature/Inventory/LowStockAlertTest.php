<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\SendLowStockAlert;
use App\Mail\LowStockAlertMail;
use App\Models\Inventory\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_does_nothing_when_nothing_is_low_on_stock(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->syncRoles(['Inventory']);
        Product::factory()->inventoryTracked()->create(['low_stock_threshold' => null]);

        $count = app(SendLowStockAlert::class)->handle();

        $this->assertSame(0, $count);
        Mail::assertNothingSent();
    }

    public function test_action_emails_every_user_who_can_manage_inventory(): void
    {
        Mail::fake();
        $manager = User::factory()->create(['email' => 'manager@example.com']);
        $manager->syncRoles(['Inventory']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->syncRoles(['Administrator']);
        $sales = User::factory()->create(['email' => 'sales@example.com']);
        $sales->syncRoles(['Sales']);

        $product = Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);

        $count = app(SendLowStockAlert::class)->handle();

        $this->assertSame(1, $count);
        Mail::assertSent(LowStockAlertMail::class, function (LowStockAlertMail $mail) use ($product) {
            return $mail->hasTo('manager@example.com')
                && $mail->hasTo('admin@example.com')
                && ! $mail->hasTo('sales@example.com')
                && $mail->products->contains('id', $product->id);
        });
    }

    public function test_action_does_nothing_when_there_are_no_recipients(): void
    {
        Mail::fake();
        Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);

        $count = app(SendLowStockAlert::class)->handle();

        $this->assertSame(0, $count);
        Mail::assertNothingSent();
    }

    public function test_a_service_product_is_never_flagged_low_stock_regardless_of_threshold(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->syncRoles(['Inventory']);
        Product::factory()->create(['type' => 'service', 'low_stock_threshold' => 5]);

        $count = app(SendLowStockAlert::class)->handle();

        $this->assertSame(0, $count);
    }

    public function test_the_mail_body_renders_without_error(): void
    {
        $product = Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);

        $html = (new LowStockAlertMail(collect([$product])))->render();

        $this->assertStringContainsString($product->sku, $html);
    }

    public function test_the_scheduled_command_runs_successfully(): void
    {
        Mail::fake();

        $this->artisan('inventory:send-low-stock-alerts')->assertSuccessful();
    }

    public function test_guest_cannot_trigger_a_manual_alert(): void
    {
        $this->post(route('inventory.low-stock-alert.store'))->assertRedirect(route('login'));
    }

    public function test_a_viewer_cannot_trigger_a_manual_alert(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->post(route('inventory.low-stock-alert.store'))->assertForbidden();
    }

    public function test_a_manager_can_manually_trigger_an_alert(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->syncRoles(['Inventory']);
        Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);

        $this->actingAs($user)->post(route('inventory.low-stock-alert.store'))->assertRedirect();

        Mail::assertSent(LowStockAlertMail::class);
    }
}

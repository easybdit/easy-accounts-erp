<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_correct_kpis(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();

        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $ar = Account::factory()->create(['type' => 'asset', 'is_bank_account' => false]);
        $ap = Account::factory()->create(['type' => 'liability']);
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $customer = Customer::factory()->create(['opening_balance' => 0]);
        $vendor = Vendor::factory()->create(['opening_balance' => 0]);

        // Cash sale: bank +500, income +500.
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => $today,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 500],
            ],
        ])->assertRedirect();

        // Cash expense: bank -200, expense +200.
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => $today,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 200],
            ],
        ])->assertRedirect();

        // Sale on account, tagged to the customer: AR +150, income +150.
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => $today,
            'lines' => [
                ['account_id' => $ar->id, 'customer_id' => $customer->id, 'debit' => 150, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 150],
            ],
        ])->assertRedirect();

        // Bill on account, tagged to the vendor: AP +80, expense +80.
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => $today,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 80, 'credit' => 0],
                ['account_id' => $ap->id, 'vendor_id' => $vendor->id, 'debit' => 0, 'credit' => 80],
            ],
        ])->assertRedirect();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('kpis.bankBalance', '300.0000')
                ->where('kpis.monthIncome', '650.0000')
                ->where('kpis.monthExpense', '280.0000')
                ->where('kpis.monthProfit', '370.0000')
                ->where('kpis.arOutstanding', '150.0000')
                ->where('kpis.apOutstanding', '80.0000')
                ->has('trend', 6)
                ->where('trend.5.month', now()->format('M Y'))
                ->where('trend.5.income', '650.0000')
                ->where('trend.5.expense', '280.0000')
                ->where('trend.0.income', '0.0000')
            );
    }

    public function test_transactions_from_a_prior_month_are_excluded_from_this_months_income_and_expense(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 900, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 900],
            ],
        ])->assertRedirect();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.monthIncome', '0.0000')
                // The bank balance itself is a point-in-time total, so the prior month's deposit still counts.
                ->where('kpis.bankBalance', '900.0000')
                // Prior month is the second-to-last of the 6 trend buckets (index 4); it should carry the 900, not the current month.
                ->where('trend.4.income', '900.0000')
                ->where('trend.5.income', '0.0000')
            );
    }
}

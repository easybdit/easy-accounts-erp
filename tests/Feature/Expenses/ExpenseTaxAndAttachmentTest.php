<?php

namespace Tests\Feature\Expenses;

use App\Models\Accounting\Account;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTaxAndAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $category = ExpenseCategory::factory()->create();
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $paymentAccount = Account::factory()->create(['type' => 'asset']);

        return array_merge([
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'payee' => 'City Power Co',
            'expense_date' => '2026-01-10',
            'amount' => 100,
        ], $overrides);
    }

    public function test_a_taxed_expense_debits_the_tax_account_and_pays_amount_plus_tax(): void
    {
        $user = User::factory()->create();
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload([
            'tax_rate_id' => $taxRate->id,
        ]))->assertRedirect();

        $expense = Expense::first();
        $this->assertSame('15.0000', (string) $expense->tax_amount);
        $this->assertSame('115.0000', $expense->totalPaid());

        $journal = $expense->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('115.0000', $journal->totalDebit());

        $taxLine = $journal->entries->firstWhere('account_id', $taxLiability->id);
        $this->assertSame('15.0000', $taxLine->debit);

        $paymentLine = $journal->entries->firstWhere('account_id', $expense->payment_account_id);
        $this->assertSame('115.0000', $paymentLine->credit);
    }

    public function test_an_expense_without_tax_behaves_exactly_as_before(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload())->assertRedirect();

        $expense = Expense::first();
        $this->assertSame('0.0000', (string) $expense->tax_amount);
        $this->assertSame('100.0000', $expense->totalPaid());
    }

    public function test_receipts_can_be_uploaded_downloaded_and_deleted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('expenses.entries.store'), [
            ...$this->payload(),
            'attachments' => [$file],
        ])->assertRedirect();

        $expense = Expense::first();
        $this->assertDatabaseCount('expense_attachments', 1);
        $attachment = $expense->attachments->first();
        $this->assertSame('receipt.pdf', $attachment->original_filename);
        Storage::disk('local')->assertExists($attachment->stored_path);

        $this->actingAs($user)->get(route('expenses.entries.attachments.download', [$expense->id, $attachment->id]))
            ->assertOk();

        $this->actingAs($user)->delete(route('expenses.entries.attachments.destroy', [$expense->id, $attachment->id]))
            ->assertRedirect();

        $this->assertDatabaseCount('expense_attachments', 0);
        Storage::disk('local')->assertMissing($attachment->stored_path);
    }

    public function test_attachments_are_capped_at_five_files(): void
    {
        $user = User::factory()->create();
        $files = array_map(fn ($i) => UploadedFile::fake()->create("receipt-{$i}.pdf", 10, 'application/pdf'), range(1, 6));

        $response = $this->actingAs($user)->post(route('expenses.entries.store'), [
            ...$this->payload(),
            'attachments' => $files,
        ]);

        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('expenses', 0);
    }
}

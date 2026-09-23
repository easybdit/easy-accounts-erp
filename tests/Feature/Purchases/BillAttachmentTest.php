<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function draftBill(): Bill
    {
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        return Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'status' => 'draft',
        ]);
    }

    public function test_a_document_can_be_uploaded_downloaded_and_deleted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $bill = $this->draftBill();
        $file = UploadedFile::fake()->create('vendor-invoice.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('purchases.bills.attachments.store', $bill), [
            'attachments' => [$file],
        ])->assertRedirect();

        $this->assertDatabaseCount('bill_attachments', 1);
        $attachment = $bill->attachments()->first();
        $this->assertSame('vendor-invoice.pdf', $attachment->original_filename);
        Storage::disk('local')->assertExists($attachment->stored_path);

        $this->actingAs($user)->get(route('purchases.bills.attachments.download', [$bill->id, $attachment->id]))
            ->assertOk();

        $this->actingAs($user)->delete(route('purchases.bills.attachments.destroy', [$bill->id, $attachment->id]))
            ->assertRedirect();

        $this->assertDatabaseCount('bill_attachments', 0);
        Storage::disk('local')->assertMissing($attachment->stored_path);
    }

    public function test_a_document_can_be_added_to_an_already_posted_bill(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $bill = $this->draftBill();
        $bill->update(['status' => 'posted']);
        $file = UploadedFile::fake()->create('vendor-invoice.pdf', 50, 'application/pdf');

        $this->actingAs($user)->post(route('purchases.bills.attachments.store', $bill), [
            'attachments' => [$file],
        ])->assertRedirect();

        $this->assertDatabaseCount('bill_attachments', 1);
    }

    public function test_attachments_are_capped_at_five_files(): void
    {
        $user = User::factory()->create();
        $bill = $this->draftBill();
        $files = array_map(fn ($i) => UploadedFile::fake()->create("doc-{$i}.pdf", 10, 'application/pdf'), range(1, 6));

        $response = $this->actingAs($user)->post(route('purchases.bills.attachments.store', $bill), [
            'attachments' => $files,
        ]);

        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('bill_attachments', 0);
    }

    public function test_an_attachment_belonging_to_another_bill_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $bill = $this->draftBill();
        $otherBill = $this->draftBill();
        $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
        $this->actingAs($user)->post(route('purchases.bills.attachments.store', $bill), ['attachments' => [$file]]);
        $attachment = $bill->attachments()->first();

        $this->actingAs($user)->get(route('purchases.bills.attachments.download', [$otherBill->id, $attachment->id]))
            ->assertNotFound();
    }

    public function test_guest_cannot_upload_an_attachment(): void
    {
        $bill = $this->draftBill();

        $this->post(route('purchases.bills.attachments.store', $bill), [])->assertRedirect(route('login'));
    }
}

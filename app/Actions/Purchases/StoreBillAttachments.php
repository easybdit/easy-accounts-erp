<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores uploaded supporting documents (the vendor's own invoice copy,
 * delivery notes, etc.) against a bill on the private "local" disk —
 * mirrors App\Actions\Expenses\StoreExpenseAttachments exactly. Unlike an
 * Expense, a Bill has a real lifecycle (draft → posted → paid), so
 * attachments can be added at any point in that lifecycle, not just at
 * creation.
 */
class StoreBillAttachments
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(Bill $bill, array $files, ?int $userId): void
    {
        foreach ($files as $file) {
            $path = $file->store("bills/{$bill->id}", 'local');

            $bill->attachments()->create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $userId,
            ]);
        }
    }

    public function delete(Bill $bill, int $attachmentId): void
    {
        $attachment = $bill->attachments()->findOrFail($attachmentId);

        Storage::disk('local')->delete($attachment->stored_path);
        $attachment->delete();
    }
}

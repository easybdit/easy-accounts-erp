<?php

namespace App\Actions\Expenses;

use App\Models\Expenses\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores uploaded receipt/invoice files against an already-recorded expense
 * on the private "local" disk (Section 90 Phase 6) — financial documents
 * are never put on the public disk, and are only ever served back through
 * an authenticated, permission-gated download route.
 */
class StoreExpenseAttachments
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(Expense $expense, array $files, ?int $userId): void
    {
        foreach ($files as $file) {
            $path = $file->store("expenses/{$expense->id}", 'local');

            $expense->attachments()->create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $userId,
            ]);
        }
    }

    public function delete(Expense $expense, int $attachmentId): void
    {
        $attachment = $expense->attachments()->findOrFail($attachmentId);

        Storage::disk('local')->delete($attachment->stored_path);
        $attachment->delete();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_is_stored_privately_and_can_be_downloaded_then_deleted(): void
    {
        Storage::fake('local');
        $owner = User::factory()->staff()->create();
        $expense = Expense::factory()->create(['created_by' => $owner->id]);

        $id = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/attachments", [
            'file' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertCreated()->json('data.id');

        $path = $this->app['db']->table('attachments')->where('id', $id)->value('storage_path');
        Storage::disk('local')->assertExists($path);
        $this->get("/api/expenses/{$expense->id}/attachments/{$id}")->assertOk();
        $this->deleteJson("/api/expenses/{$expense->id}/attachments/{$id}")->assertOk();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_unrelated_staff_cannot_upload_or_delete_receipts(): void
    {
        Storage::fake('local');
        $expense = Expense::factory()->create();
        $this->actingAs(User::factory()->staff()->create())
            ->postJson("/api/expenses/{$expense->id}/attachments", [
                'file' => UploadedFile::fake()->image('receipt.jpg'),
            ])->assertForbidden();
    }

    public function test_unsupported_receipt_type_is_rejected(): void
    {
        Storage::fake('local');
        $owner = User::factory()->staff()->create();
        $expense = Expense::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/attachments", [
            'file' => UploadedFile::fake()->create('script.php', 2, 'application/x-php'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }
}

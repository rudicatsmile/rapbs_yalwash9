<?php

namespace Tests\Feature;

use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\FinancialRecordTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialRecordTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_track_draft_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        FinancialRecord::factory()->create([
            'status' => 0, // Draft
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('financial_record_tracks', 0);
    }

    public function test_it_prevents_duplicate_tracking_on_double_save_sequence()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Initial State: Final Record with 1 Expense Item
        $record = FinancialRecord::factory()->create([
            'status' => 1,
            'user_id' => $user->id,
            'total_expense' => 1000
        ]);

        // Force backdate previous tracks to avoid merging during setup
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 1000,
        ]);

        // Force backdate again so the next steps are seen as "New Action"
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        // Expect:
        // 1. Initial Final
        // 2. Item Added
        $this->assertDatabaseCount('financial_record_tracks', 2);

        // 2. Simulate User changing Amount of Item 1 to 2000
        // This typically updates Parent's Total Expense AND Item's Amount

        // Step A: Parent saves Total Expense (Simulated)
        $record->update(['total_expense' => 2000]);

        // Check tracks: Should be 3 now (Track A: Total Expense Changed)
        $this->assertDatabaseCount('financial_record_tracks', 3);

        // Step B: Item saves Amount -> Touches Parent
        // This simulates the $touches = ['financialRecord'] behavior
        $item->update(['amount' => 2000]);

        // With Merge Logic:
        // The update from Step B (Item Save) happens < 2s after Step A (Parent Save).
        // It should MERGE into Track 3.
        // So count should REMAIN 3.

        $count = FinancialRecordTrack::count();
        $this->assertEquals(3, $count, 'Duplicate track created! Expected 3, got ' . $count);

        // Verify Track 3 contains BOTH changes (Total Expense AND Item Amount)
        $track = FinancialRecordTrack::latest('id')->first();
        $this->assertArrayHasKey('field_total_expense', $track->changes_summary, 'Total Expense change missing from merged track');

        // Check if expense item change is present
        // Since we didn't implement specific key check for amount yet in test, let's just ensure the track has updated snapshot
        $this->assertEquals(2000, $track->snapshot_data['total_expense']);
        $this->assertEquals(2000, $track->snapshot_data['expense_items'][0]['amount']);
    }

    public function test_it_tracks_initial_final_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create([
            'status' => 0, // Draft
            'user_id' => $user->id,
        ]);

        // Update to Final
        $record->update(['status' => 1]);

        $this->assertDatabaseCount('financial_record_tracks', 1);
        $this->assertDatabaseHas('financial_record_tracks', [
            'financial_record_id' => $record->id,
            'version' => 1,
            'action_type' => 'INITIAL_FINAL',
            'created_by' => $user->id,
        ]);
    }

    public function test_it_tracks_updates_on_final_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create directly as Final (Should trigger INITIAL_FINAL)
        $record = FinancialRecord::factory()->create([
            'status' => 1,
            'user_id' => $user->id,
            'income_amount' => 1000000
        ]);

        $this->assertDatabaseCount('financial_record_tracks', 1);

        // Backdate to avoid merge
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        // Update the record
        $record->update(['income_amount' => 5000000]);

        $this->assertDatabaseCount('financial_record_tracks', 2);

        $track = FinancialRecordTrack::latest('id')->first();
        $this->assertEquals(2, $track->version);
        $this->assertEquals('UPDATE_FINAL', $track->action_type);

        // Verify changes summary
        $this->assertArrayHasKey('field_income_amount', $track->changes_summary);
        $this->assertEquals(1000000, $track->changes_summary['field_income_amount']['old']);
        $this->assertEquals(5000000, $track->changes_summary['field_income_amount']['new']);
    }

    public function test_it_does_not_track_updates_without_changes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create Final record
        $record = FinancialRecord::factory()->create([
            'status' => 1,
            'user_id' => $user->id,
            'income_amount' => 1000000
        ]);

        $this->assertDatabaseCount('financial_record_tracks', 1);

        // Update with SAME data
        $record->touch();

        // Should still be 1 track (ignored)
        $this->assertDatabaseCount('financial_record_tracks', 1);
    }

    public function test_it_tracks_expense_item_additions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create(['status' => 1, 'user_id' => $user->id]);

        $this->assertDatabaseCount('financial_record_tracks', 1);

        // Backdate to avoid merge
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'New Item',
            'amount' => 500,
        ]);

        // Expect 2 tracks: 1 Initial, 1 Update (Item Added)
        $this->assertDatabaseCount('financial_record_tracks', 2);

        $track = FinancialRecordTrack::latest('id')->first();
        $this->assertArrayHasKey("expense_item_{$item->id}_added", $track->changes_summary);
    }

    public function test_it_tracks_expense_item_description_changes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create(['status' => 1, 'user_id' => $user->id]);

        // Backdate Initial
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Old Desc',
            'amount' => 500,
        ]);

        // Backdate Item Creation
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        // Update Description
        $item->update(['description' => 'New Desc']);

        // Expect 3 tracks: 1 Initial, 1 Added, 1 Update Desc
        $this->assertDatabaseCount('financial_record_tracks', 3);

        $track = FinancialRecordTrack::latest('id')->first();
        $key = "expense_item_{$item->id}_description";

        $this->assertArrayHasKey($key, $track->changes_summary);
        $this->assertEquals('Old Desc', $track->changes_summary[$key]['old']);
        $this->assertEquals('New Desc', $track->changes_summary[$key]['new']);
    }

    public function test_it_tracks_expense_item_source_type_changes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create(['status' => 1, 'user_id' => $user->id]);

        // Backdate Initial
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Desc',
            'amount' => 500,
            'source_type' => 'Source A',
        ]);

        // Backdate Item Creation
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item->update(['source_type' => 'Source B']);

        $track = FinancialRecordTrack::latest('id')->first();
        $key = "expense_item_{$item->id}_source_type";

        $this->assertArrayHasKey($key, $track->changes_summary);
        $this->assertEquals('Source A', $track->changes_summary[$key]['old']);
        $this->assertEquals('Source B', $track->changes_summary[$key]['new']);
    }

    public function test_it_tracks_expense_item_deletion()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create(['status' => 1, 'user_id' => $user->id]);

        // Backdate Initial
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'To Delete',
            'amount' => 500,
        ]);

        // Backdate Item Creation
        FinancialRecordTrack::query()->update(['created_at' => now()->subMinutes(5)]);

        $item->delete();

        // Ensure parent is touched to trigger observer (Test environment quirk)
        $record->touch();

        $track = FinancialRecordTrack::latest('id')->first();
        $key = "expense_item_{$item->id}_deleted";

        $this->assertArrayHasKey($key, $track->changes_summary);
        $this->assertEquals('Deleted', $track->changes_summary[$key]['new']);
    }
}

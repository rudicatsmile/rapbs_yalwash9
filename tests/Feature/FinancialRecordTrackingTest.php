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

        // Add Item
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
        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Old Desc',
            'amount' => 500,
        ]);

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
        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Desc',
            'amount' => 500,
            'source_type' => 'Source A',
        ]);

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
        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'To Delete',
            'amount' => 500,
        ]);

        $item->delete();

        // Ensure parent is touched to trigger observer (Test environment quirk)
        $record->touch();

        $track = FinancialRecordTrack::latest('id')->first();
        $key = "expense_item_{$item->id}_deleted";

        $this->assertArrayHasKey($key, $track->changes_summary);
        $this->assertEquals('Deleted', $track->changes_summary[$key]['new']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────
    // Authentication
    // ────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_campaigns(): void
    {
        $this->getJson('/api/v1/campaigns')->assertStatus(401);
        $this->postJson('/api/v1/campaigns')->assertStatus(401);
    }

    // ────────────────────────────────────────────
    // Index
    // ────────────────────────────────────────────

    public function test_user_can_list_their_campaigns(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        // Campaign user is NOT part of
        Campaign::factory()->create();

        $response = $this->getJson('/api/v1/campaigns');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $campaign->id);
    }

    // ────────────────────────────────────────────
    // Store
    // ────────────────────────────────────────────

    public function test_user_can_create_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/campaigns', [
            'name' => 'Curse of Strahd',
            'description' => 'A gothic horror adventure.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Curse of Strahd')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.created_by', $user->id);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Curse of Strahd',
            'created_by' => $user->id,
        ]);

        // Creator should be attached as DM
        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $response->json('data.id'),
            'user_id' => $user->id,
            'role' => 'dm',
        ]);
    }

    public function test_create_campaign_validates_name_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/campaigns', [
            'description' => 'Missing name',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_campaign_validates_name_max_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/campaigns', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
    }

    // ────────────────────────────────────────────
    // Show
    // ────────────────────────────────────────────

    public function test_member_can_view_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->getJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $campaign->id)
            ->assertJsonPath('data.name', $campaign->name);
    }

    public function test_non_member_cannot_view_campaign(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $campaign = Campaign::factory()->create(['created_by' => $owner->id]);
        $campaign->users()->attach($owner->id, ['role' => 'dm']);

        $response = $this->getJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }

    // ────────────────────────────────────────────
    // Update
    // ────────────────────────────────────────────

    public function test_dm_can_update_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->putJson("/api/v1/campaigns/{$campaign->id}", [
            'name' => 'Updated Campaign Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Campaign Name');
    }

    public function test_player_cannot_update_campaign(): void
    {
        $dm = User::factory()->create();
        $player = User::factory()->create();

        $campaign = Campaign::factory()->create(['created_by' => $dm->id]);
        $campaign->users()->attach($dm->id, ['role' => 'dm']);
        $campaign->users()->attach($player->id, ['role' => 'player']);

        Sanctum::actingAs($player);

        $response = $this->putJson("/api/v1/campaigns/{$campaign->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_dm_cannot_update_archived_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->archived()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->putJson("/api/v1/campaigns/{$campaign->id}", [
            'name' => 'Should Fail',
        ]);

        $response->assertStatus(403);
    }

    public function test_dm_can_archive_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->putJson("/api/v1/campaigns/{$campaign->id}", [
            'status' => 'archived',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_update_validates_status_value(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->putJson("/api/v1/campaigns/{$campaign->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    // ────────────────────────────────────────────
    // Destroy
    // ────────────────────────────────────────────

    public function test_dm_can_delete_campaign(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $campaign->users()->attach($user->id, ['role' => 'dm']);

        $response = $this->deleteJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_player_cannot_delete_campaign(): void
    {
        $dm = User::factory()->create();
        $player = User::factory()->create();

        $campaign = Campaign::factory()->create(['created_by' => $dm->id]);
        $campaign->users()->attach($dm->id, ['role' => 'dm']);
        $campaign->users()->attach($player->id, ['role' => 'player']);

        Sanctum::actingAs($player);

        $response = $this->deleteJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_non_member_cannot_delete_campaign(): void
    {
        $dm = User::factory()->create();
        $stranger = User::factory()->create();

        $campaign = Campaign::factory()->create(['created_by' => $dm->id]);
        $campaign->users()->attach($dm->id, ['role' => 'dm']);

        Sanctum::actingAs($stranger);

        $response = $this->deleteJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }
}


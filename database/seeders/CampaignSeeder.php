<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $dm = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $players = User::factory(4)->create();

        // Create 3 active campaigns
        Campaign::factory(3)->create(['created_by' => $dm->id])->each(function (Campaign $campaign) use ($dm, $players) {
            // Attach creator as DM
            $campaign->users()->attach($dm->id, ['role' => 'dm']);

            // Attach 2-3 random players
            $selectedPlayers = $players->random(rand(2, 3));
            foreach ($selectedPlayers as $player) {
                $campaign->users()->attach($player->id, ['role' => 'player']);
            }
        });

        // Create 1 archived campaign
        Campaign::factory()->archived()->create(['created_by' => $dm->id])->each(function (Campaign $campaign) use ($dm, $players) {
            $campaign->users()->attach($dm->id, ['role' => 'dm']);
            $campaign->users()->attach($players->first()->id, ['role' => 'player']);
        });
    }
}


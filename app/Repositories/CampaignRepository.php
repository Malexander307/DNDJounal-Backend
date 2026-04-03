<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CampaignRepository
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->campaigns()
            ->with('creator')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): Campaign
    {
        return Campaign::with(['creator', 'users'])->findOrFail($id);
    }

    public function create(array $data, User $creator): Campaign
    {
        return DB::transaction(function () use ($data, $creator) {
            $campaign = Campaign::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'created_by' => $creator->id,
            ]);

            $campaign->users()->attach($creator->id, ['role' => 'dm']);

            return $campaign->load(['creator', 'users']);
        });
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);

        return $campaign->fresh(['creator', 'users']);
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }
}


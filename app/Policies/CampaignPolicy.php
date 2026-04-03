<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Any authenticated user can list campaigns.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only campaign members can view a campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->isMember($user);
    }

    /**
     * Any authenticated user can create a campaign.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the DM can update, and the campaign must not be archived.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->isDungeonMaster($user) && !$campaign->isArchived();
    }

    /**
     * Only the DM can delete a campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->isDungeonMaster($user);
    }
}


<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Repositories\CampaignRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignService
{
    public function __construct(
        private readonly CampaignRepository $repository
    ) {}

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listForUser($user, $perPage);
    }

    public function show(int $id): Campaign
    {
        return $this->repository->findById($id);
    }

    public function store(array $data, User $creator): Campaign
    {
        return $this->repository->create($data, $creator);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        return $this->repository->update($campaign, $data);
    }

    public function destroy(Campaign $campaign): void
    {
        $this->repository->delete($campaign);
    }
}


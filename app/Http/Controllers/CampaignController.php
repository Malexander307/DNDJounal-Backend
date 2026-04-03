<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = $this->campaignService->list(
            auth()->user(),
            (int) request('per_page', 15)
        );

        return $this->paginated(
            $campaigns,
            'Campaigns retrieved successfully.'
        );
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = $this->campaignService->store(
            $request->validated(),
            $request->user()
        );

        return $this->created(
            new CampaignResource($campaign),
            'Campaign created successfully.'
        );
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign = $this->campaignService->show($campaign->id);

        return $this->success(
            new CampaignResource($campaign),
            'Campaign retrieved successfully.'
        );
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign = $this->campaignService->update(
            $campaign,
            $request->validated()
        );

        return $this->success(
            new CampaignResource($campaign),
            'Campaign updated successfully.'
        );
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $this->campaignService->destroy($campaign);

        return $this->success(
            null,
            'Campaign deleted successfully.'
        );
    }
}

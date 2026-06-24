<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationMembershipSummaryResource;
use App\Models\OrganizationMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationsController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $memberships = OrganizationMembership::query()
            ->with(['organization', 'defaultWorkspace'])
            ->where('user_id', $request->user()->id)
            ->where('status', MembershipStatus::Active)
            ->whereNull('deleted_at')
            ->whereHas('organization', function ($query) {
                $query->where('status', OrganizationStatus::Active)
                    ->whereNull('deleted_at');
            })
            ->get()
            ->sortBy(fn (OrganizationMembership $membership) => $membership->organization->name)
            ->values()
            ->map(fn (OrganizationMembership $membership) => [
                'organization' => $membership->organization,
                'membership' => $membership,
            ]);

        return OrganizationMembershipSummaryResource::collection($memberships);
    }
}

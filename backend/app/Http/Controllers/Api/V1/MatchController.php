<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Services\DiscoverableProfiles;
use App\Services\DiscoveryProfilePresenter;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request, DiscoverableProfiles $discoverable, DiscoveryProfilePresenter $presenter)
    {
        $filters = $request->validate([
            'gender' => ['sometimes', 'in:male,female,other'],
            'country' => ['sometimes', 'string', 'max:80'],
            'state' => ['sometimes', 'string', 'max:80'],
            'city' => ['sometimes', 'string', 'max:80'],
            'religion' => ['sometimes', 'string', 'max:80'],
            'denomination' => ['sometimes', 'string', 'max:100'],
            'community' => ['sometimes', 'string', 'max:100'],
            'sub_community' => ['sometimes', 'string', 'max:120'],
            'ethnicity' => ['sometimes', 'string', 'max:120'],
            'mother_tongue' => ['sometimes', 'string', 'max:80'],
            'marital_status' => ['sometimes', 'string', 'max:40'],
            'education' => ['sometimes', 'string', 'max:180'],
            'occupation' => ['sometimes', 'string', 'max:180'],
            'min_age' => ['sometimes', 'integer', 'between:18,100'],
            'max_age' => ['sometimes', 'integer', 'between:18,100', 'gte:min_age'],
            'min_height_cm' => ['sometimes', 'integer', 'between:100,250'],
            'max_height_cm' => ['sometimes', 'integer', 'between:100,250', 'gte:min_height_cm'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $viewer = $request->user()->loadMissing('partnerPreference');
        $query = $discoverable->query($viewer)
            ->with(['user:id,name', 'photos' => fn ($photos) => $photos->where('is_primary', true)->where('moderation_status', 'approved')->where('visibility', 'members')])
            ->with(['favouritedBy' => fn ($favourites) => $favourites->where('user_id', $viewer->id)]);

        foreach (['gender', 'country', 'state', 'city', 'religion', 'denomination', 'community', 'sub_community', 'ethnicity', 'mother_tongue', 'marital_status'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['education', 'occupation'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, 'like', '%'.$filters[$field].'%');
            }
        }
        if (isset($filters['min_age'])) {
            $query->whereDate('date_of_birth', '<=', now()->subYears($filters['min_age'])->toDateString());
        }
        if (isset($filters['max_age'])) {
            $query->whereDate('date_of_birth', '>=', now()->subYears($filters['max_age'] + 1)->addDay()->toDateString());
        }
        if (isset($filters['min_height_cm'])) {
            $query->where('height_cm', '>=', $filters['min_height_cm']);
        }
        if (isset($filters['max_height_cm'])) {
            $query->where('height_cm', '<=', $filters['max_height_cm']);
        }

        $matches = $query->orderByDesc('verification_status')
            ->orderByDesc('last_active_at')
            ->paginate($filters['per_page'] ?? 24)
            ->through(fn (Profile $profile) => $presenter->payload($profile, $viewer));

        return $this->success($matches);
    }

    public function show(Request $request, Profile $profile, DiscoverableProfiles $discoverable, DiscoveryProfilePresenter $presenter)
    {
        $viewer = $request->user()->loadMissing('partnerPreference');
        $profile = $discoverable->find($viewer, $profile->id)->load([
            'user:id,name',
            'photos' => fn ($photos) => $photos->where('moderation_status', 'approved')->whereIn('visibility', ['members', 'private']),
            'favouritedBy' => fn ($favourites) => $favourites->where('user_id', $viewer->id),
        ]);

        $viewedToday = ProfileView::query()
            ->where('viewer_id', $viewer->id)
            ->where('viewed_user_id', $profile->user_id)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->exists();
        if (! $viewedToday) {
            ProfileView::create(['viewer_id' => $viewer->id, 'viewed_user_id' => $profile->user_id, 'viewed_at' => now()]);
        }

        return $this->success($presenter->payload($profile, $viewer, true));
    }
}

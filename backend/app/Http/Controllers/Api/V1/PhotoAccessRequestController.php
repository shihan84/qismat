<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\PhotoAccessRequest;
use App\Models\User;
use App\Services\ActivityTracker;
use App\Services\DiscoverableProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PhotoAccessRequestController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $data = $request->validate(['direction' => ['sometimes', Rule::in(['sent', 'received'])]]);
        $received = ($data['direction'] ?? 'received') === 'received';
        $query = PhotoAccessRequest::query()
            ->where($received ? 'owner_id' : 'requester_id', $request->user()->id)
            ->with($received ? 'requester.profile:id,user_id,profile_code,display_name' : 'owner.profile:id,user_id,profile_code,display_name')
            ->latest();

        return $this->success($query->paginate(30));
    }

    public function store(Request $request, DiscoverableProfiles $discoverable, ActivityTracker $tracker)
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'not_in:'.$request->user()->id]]);
        $owner = User::query()->where('role', 'member')->findOrFail($data['user_id']);
        $discoverable->query($request->user())->where('user_id', $owner->id)->firstOrFail();
        abort_unless($owner->profilePhotos()->where('moderation_status', 'approved')->where('visibility', 'private')->exists(), 409, 'This member has no private photos available to request.');

        $photoRequest = PhotoAccessRequest::firstOrNew(['requester_id' => $request->user()->id, 'owner_id' => $owner->id]);
        abort_if($photoRequest->exists && in_array($photoRequest->status, ['pending', 'approved'], true), 409, 'A photo access request is already active.');
        $photoRequest->fill(['status' => 'pending', 'responded_at' => null])->save();
        $tracker->record($request, 'photo_access.requested', 'user', $owner->id);

        return $this->success($photoRequest, 'Private photo access requested.', 201);
    }

    public function respond(Request $request, PhotoAccessRequest $photoAccessRequest, ActivityTracker $tracker)
    {
        abort_unless($photoAccessRequest->owner_id === $request->user()->id, 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'declined'])]]);
        abort_unless($photoAccessRequest->status === 'pending', 409, 'Only pending requests can be answered.');
        $photoAccessRequest->update(['status' => $data['decision'], 'responded_at' => now()]);
        $tracker->record($request, 'photo_access.'.$data['decision'], 'user', $photoAccessRequest->requester_id);

        return $this->success($photoAccessRequest, 'Photo access request updated.');
    }

    public function destroy(Request $request, PhotoAccessRequest $photoAccessRequest, ActivityTracker $tracker)
    {
        abort_unless(in_array($request->user()->id, [$photoAccessRequest->requester_id, $photoAccessRequest->owner_id], true), 404);
        DB::transaction(function () use ($photoAccessRequest) {
            PhotoAccessRequest::query()->whereKey($photoAccessRequest->id)->lockForUpdate()->firstOrFail()
                ->update(['status' => 'revoked', 'responded_at' => now()]);
        });
        $tracker->record($request, 'photo_access.revoked', 'photo_access_request', $photoAccessRequest->id);

        return $this->success(null, 'Private photo access removed.');
    }
}

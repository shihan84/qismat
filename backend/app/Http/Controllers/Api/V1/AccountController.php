<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\ActivityTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    use RespondsWithJson;

    private const PREFERENCE_FIELDS = [
        'email_new_interest', 'email_interest_accepted', 'email_new_message',
        'email_moderation_updates', 'email_product_updates', 'push_new_interest',
        'push_interest_accepted', 'push_new_message', 'push_moderation_updates',
    ];

    public function notificationPreferences(Request $request)
    {
        $preferences = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);

        return $this->success($preferences->fresh());
    }

    public function updateNotificationPreferences(Request $request, ActivityTracker $tracker)
    {
        $rules = array_fill_keys(self::PREFERENCE_FIELDS, ['sometimes', 'boolean']);
        $data = $request->validate($rules);
        $preferences = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);
        $preferences->update($data);
        $tracker->record($request, 'account.notifications_updated');

        return $this->success($preferences->fresh(), 'Notification preferences saved.');
    }

    public function logoutAll(Request $request, ActivityTracker $tracker)
    {
        $tracker->record($request, 'account.sessions_revoked');
        $request->user()->tokens()->delete();

        return $this->success(null, 'Signed out on every device.');
    }

    public function destroy(Request $request, ActivityTracker $tracker)
    {
        $request->validate(['confirmation' => ['required', 'in:DELETE']]);
        $user = $request->user();
        $photos = $user->profilePhotos()->get(['disk', 'path']);

        $tracker->record($request, 'account.deleted');

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->profilePhotos()->delete();
            $user->partnerPreference()?->delete();
            DB::table('favourites')->where('user_id', $user->id)->orWhere('favourite_user_id', $user->id)->delete();
            DB::table('profile_views')->where('viewer_id', $user->id)->orWhere('viewed_user_id', $user->id)->delete();
            DB::table('blocks')->where('blocker_id', $user->id)->orWhere('blocked_user_id', $user->id)->delete();
            DB::table('photo_access_requests')->where('requester_id', $user->id)->orWhere('owner_id', $user->id)->delete();
            DB::table('interests')->where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->delete();
            DB::table('conversations')->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id)->delete();
            $user->profile()?->delete();
            $user->notificationPreference()?->delete();
            $user->forceFill([
                'name' => 'Deleted member',
                'email' => "deleted-{$user->id}@deleted.invalid",
                'phone' => null,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'status' => 'deleted',
                'password' => str()->random(64),
                'remember_token' => null,
            ])->save();
        });

        foreach ($photos as $photo) {
            Storage::disk($photo->disk)->delete($photo->path);
        }

        return $this->success(null, 'Your Qismat account and member profile have been deleted.');
    }
}

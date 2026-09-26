<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Interest;
use App\Models\PhotoAccessRequest;
use App\Models\ProfilePhoto;
use App\Models\User;

class ProfilePhotoAccess
{
    public function __construct(private readonly DiscoverableProfiles $discoverable) {}

    public function canView(User $viewer, ProfilePhoto $photo): bool
    {
        if ($viewer->id === $photo->user_id || ($viewer->role === 'admin' && $viewer->status === 'active')) {
            return true;
        }

        if ($viewer->status !== 'active' || ! $viewer->hasVerifiedEmail() || $photo->moderation_status !== 'approved') {
            return false;
        }

        $owner = $photo->user;
        $profile = $owner?->profile;
        if (! $owner || $owner->status !== 'active' || ! $owner->hasVerifiedEmail() || $profile?->moderation_status !== 'approved') {
            return false;
        }

        $blocked = Block::query()->where(function ($query) use ($viewer, $owner) {
            $query->where('blocker_id', $viewer->id)->where('blocked_user_id', $owner->id);
        })->orWhere(function ($query) use ($viewer, $owner) {
            $query->where('blocker_id', $owner->id)->where('blocked_user_id', $viewer->id);
        })->exists();
        if ($blocked) {
            return false;
        }

        if ($photo->visibility === 'members') {
            return $this->discoverable->query($viewer)->where('user_id', $owner->id)->exists();
        }

        if ($photo->visibility === 'matches') {
            return Interest::query()
                ->where('status', 'accepted')
                ->where(function ($query) use ($viewer, $owner) {
                    $query->where(fn ($pair) => $pair->where('sender_id', $viewer->id)->where('receiver_id', $owner->id))
                        ->orWhere(fn ($pair) => $pair->where('sender_id', $owner->id)->where('receiver_id', $viewer->id));
                })
                ->exists();
        }

        if ($photo->visibility === 'private') {
            return PhotoAccessRequest::query()
                ->where('requester_id', $viewer->id)
                ->where('owner_id', $owner->id)
                ->where('status', 'approved')
                ->exists();
        }

        return false;
    }
}

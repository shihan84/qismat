<?php

namespace Tests\Feature\Api;

use App\Models\Interest;
use App\Models\PhotoAccessRequest;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('profile_photos');
    }

    public function test_member_can_upload_list_and_privately_read_a_processed_photo(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $response = $this->post('/api/v1/profile/photos', [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 800, 900)->size(500),
            'visibility' => 'private',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.moderation_status', 'pending')
            ->assertJsonPath('data.width', 800)
            ->assertJsonPath('data.height', 900)
            ->assertJsonMissingPath('data.path')
            ->assertJsonMissingPath('data.disk');

        $photo = ProfilePhoto::query()->firstOrFail();
        Storage::disk('profile_photos')->assertExists($photo->path);
        $this->assertStringStartsWith("users/{$member->id}/", $photo->path);

        $this->getJson('/api/v1/profile/photos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $photo->id);

        $this->get("/api/v1/profile/photos/{$photo->id}/content")
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');
    }

    public function test_photo_upload_enforces_file_and_dimension_limits(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/v1/profile/photos', [
            'photo' => UploadedFile::fake()->image('small.jpg', 200, 200),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->post('/api/v1/profile/photos', [
            'photo' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_member_can_reorder_choose_a_primary_photo_and_delete_it_safely(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $first = $this->upload('first.jpg');
        $second = $this->upload('second.jpg');

        $this->patchJson("/api/v1/profile/photos/{$second->id}", [
            'is_primary' => true,
            'visibility' => 'matches',
        ])->assertOk()
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.visibility', 'matches');

        $this->assertDatabaseHas('profile_photos', ['id' => $first->id, 'is_primary' => false]);

        $this->putJson('/api/v1/profile/photos/order', [
            'photo_ids' => [$second->id, $first->id],
        ])->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);

        $secondPath = $second->path;
        $this->deleteJson("/api/v1/profile/photos/{$second->id}")->assertOk();
        Storage::disk('profile_photos')->assertMissing($secondPath);
        $this->assertDatabaseHas('profile_photos', ['id' => $first->id, 'is_primary' => true]);
    }

    public function test_member_cannot_exceed_the_photo_limit_or_change_another_members_photo(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        foreach (range(1, 6) as $index) {
            $this->upload("photo-{$index}.jpg");
        }

        $this->post('/api/v1/profile/photos', [
            'photo' => UploadedFile::fake()->image('seventh.jpg', 800, 900),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A profile can contain up to six photos.');

        $other = User::factory()->create();
        $otherPhoto = $this->storedPhoto($other);
        $this->patchJson("/api/v1/profile/photos/{$otherPhoto->id}", ['visibility' => 'private'])
            ->assertNotFound();
        $this->deleteJson("/api/v1/profile/photos/{$otherPhoto->id}")
            ->assertNotFound();
    }

    public function test_photo_access_respects_moderation_visibility_and_accepted_interests(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $owner->profile()->create([
            'profile_code' => 'QSM000000001',
            'display_name' => 'Photo Owner',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'A complete member biography.',
            'visibility' => 'members',
            'moderation_status' => 'approved',
            'discovery_opt_in' => true,
        ]);
        $photo = $this->storedPhoto($owner, ['moderation_status' => 'pending']);

        Sanctum::actingAs($viewer);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertNotFound();

        $photo->update(['moderation_status' => 'approved']);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertOk();

        $photo->update(['visibility' => 'private']);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertNotFound();

        $photo->update(['visibility' => 'matches']);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertNotFound();
        Interest::create([
            'sender_id' => $viewer->id,
            'receiver_id' => $owner->id,
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertOk();

        $photo->update(['moderation_status' => 'rejected']);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertNotFound();

        Sanctum::actingAs($owner);
        $this->get("/api/v1/profile/photos/{$photo->id}/content")->assertOk();
    }

    public function test_admin_can_review_photos_and_the_member_receives_feedback(): void
    {
        $member = User::factory()->create();
        $member->profile()->create(['profile_code' => 'QSM000000002', 'display_name' => 'Member']);
        $photo = $this->storedPhoto($member);
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/photos')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $photo->id)
            ->assertJsonPath('data.data.0.user.profile.display_name', 'Member');

        $this->postJson("/api/v1/admin/photos/{$photo->id}/review", [
            'decision' => 'rejected',
            'reason' => 'Please upload a clear, recent photo of yourself.',
        ])->assertOk()
            ->assertJsonPath('data.moderation_status', 'rejected')
            ->assertJsonPath('data.moderation_feedback', 'Please upload a clear, recent photo of yourself.');

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'profile_photo.rejected',
            'target_type' => 'profile_photo',
            'target_id' => $photo->id,
        ]);

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/profile/photos')
            ->assertJsonPath('data.0.moderation_feedback', 'Please upload a clear, recent photo of yourself.');
    }

    public function test_private_photo_access_requires_an_approved_member_request(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $owner->profile()->create([
            'profile_code' => 'QSMPRIVATE01',
            'display_name' => 'Private Photo Member',
            'date_of_birth' => now()->subYears(28)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'A complete member biography.',
            'visibility' => 'members',
            'moderation_status' => 'approved',
            'discovery_opt_in' => true,
        ]);
        $this->storedPhoto($owner, ['moderation_status' => 'approved']);
        Storage::disk('profile_photos')->put("users/{$owner->id}/private.jpg", 'private-photo');
        $privatePhoto = ProfilePhoto::create([
            'user_id' => $owner->id,
            'disk' => 'profile_photos',
            'path' => "users/{$owner->id}/private.jpg",
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 900,
            'size_bytes' => 13,
            'is_primary' => false,
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($viewer);
        $this->get("/api/v1/profile/photos/{$privatePhoto->id}/content")->assertNotFound();
        $this->postJson('/api/v1/photo-access-requests', ['user_id' => $owner->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
        $requestId = PhotoAccessRequest::query()->value('id');

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/photo-access-requests?direction=received')
            ->assertOk()
            ->assertJsonPath('data.data.0.requester_id', $viewer->id);
        $this->postJson("/api/v1/photo-access-requests/{$requestId}/respond", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($viewer);
        $this->get("/api/v1/profile/photos/{$privatePhoto->id}/content")->assertOk();
        $this->deleteJson("/api/v1/photo-access-requests/{$requestId}")->assertOk();
        $this->get("/api/v1/profile/photos/{$privatePhoto->id}/content")->assertNotFound();
    }

    private function upload(string $name): ProfilePhoto
    {
        $this->post('/api/v1/profile/photos', [
            'photo' => UploadedFile::fake()->image($name, 800, 900),
        ], ['Accept' => 'application/json'])->assertCreated();

        return ProfilePhoto::query()->latest('id')->firstOrFail();
    }

    private function storedPhoto(User $owner, array $attributes = []): ProfilePhoto
    {
        $path = "users/{$owner->id}/stored.jpg";
        Storage::disk('profile_photos')->put($path, 'photo-bytes');

        return ProfilePhoto::create($attributes + [
            'user_id' => $owner->id,
            'disk' => 'profile_photos',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 900,
            'size_bytes' => 11,
            'is_primary' => true,
            'visibility' => 'members',
            'moderation_status' => 'pending',
            'sort_order' => 0,
        ]);
    }
}

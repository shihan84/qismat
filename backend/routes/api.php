<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AdminAuditController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminDiscoveryController;
use App\Http\Controllers\Api\V1\AdminMemberController;
use App\Http\Controllers\Api\V1\AdminPhotoModerationController;
use App\Http\Controllers\Api\V1\AdminProfileModerationController;
use App\Http\Controllers\Api\V1\AdminReportController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\FavouriteController;
use App\Http\Controllers\Api\V1\FirebaseAuthController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\PartnerPreferenceController;
use App\Http\Controllers\Api\V1\PhotoAccessRequestController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProfileOnboardingController;
use App\Http\Controllers\Api\V1\ProfilePhotoController;
use App\Http\Controllers\Api\V1\SafetyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'data' => ['service' => 'qismat-api', 'version' => 'v1'],
        'message' => null,
    ]));

    Route::prefix('auth')->group(function () {
        Route::post('/register', [FirebaseAuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('/login', [FirebaseAuthController::class, 'login'])->middleware('throttle:20,1');
        Route::post('/password-reset', [FirebaseAuthController::class, 'passwordReset'])->middleware('throttle:10,1');
        Route::post('/firebase', [FirebaseAuthController::class, 'exchange'])
            ->middleware(['firebase.auth', 'throttle:20,1']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout-all', [AccountController::class, 'logoutAll']);

        Route::prefix('admin')->middleware(['verified', 'admin'])->group(function () {
            Route::get('/dashboard', AdminDashboardController::class);
            Route::get('/members', [AdminMemberController::class, 'index']);
            Route::patch('/members/{user}', [AdminMemberController::class, 'update']);
            Route::get('/audit-logs', AdminAuditController::class);
            Route::get('/discovery', AdminDiscoveryController::class);
            Route::get('/profiles', [AdminProfileModerationController::class, 'index']);
            Route::post('/profiles/{profile}/review', [AdminProfileModerationController::class, 'review']);
            Route::get('/photos', [AdminPhotoModerationController::class, 'index']);
            Route::post('/photos/{photo}/review', [AdminPhotoModerationController::class, 'review']);
            Route::get('/reports', [AdminReportController::class, 'index']);
            Route::post('/reports/{report}/resolve', [AdminReportController::class, 'resolve']);
        });

        Route::middleware('verified')->group(function () {
            Route::get('/account/notification-preferences', [AccountController::class, 'notificationPreferences']);
            Route::put('/account/notification-preferences', [AccountController::class, 'updateNotificationPreferences']);
            Route::delete('/account', [AccountController::class, 'destroy'])->middleware('throttle:3,1');
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::get('/profile/onboarding-status', [ProfileOnboardingController::class, 'status']);
            Route::post('/profile/submit', [ProfileOnboardingController::class, 'submit']);
            Route::put('/profile/discovery', [ProfileOnboardingController::class, 'discovery']);
            Route::get('/profile/partner-preferences', [PartnerPreferenceController::class, 'show']);
            Route::put('/profile/partner-preferences', [PartnerPreferenceController::class, 'update']);
            Route::get('/profile/photos', [ProfilePhotoController::class, 'index']);
            Route::post('/profile/photos', [ProfilePhotoController::class, 'store'])->middleware('throttle:10,1');
            Route::put('/profile/photos/order', [ProfilePhotoController::class, 'reorder']);
            Route::patch('/profile/photos/{photo}', [ProfilePhotoController::class, 'update']);
            Route::delete('/profile/photos/{photo}', [ProfilePhotoController::class, 'destroy']);
            Route::get('/profile/photos/{photo}/content', [ProfilePhotoController::class, 'content'])
                ->name('profile.photos.content');

            Route::get('/matches', [MatchController::class, 'index']);
            Route::get('/matches/{profile}', [MatchController::class, 'show']);
            Route::get('/favourites', [FavouriteController::class, 'index']);
            Route::post('/favourites', [FavouriteController::class, 'store']);
            Route::delete('/favourites/{profile}', [FavouriteController::class, 'destroy']);

            Route::get('/interests', [InterestController::class, 'index']);
            Route::post('/interests', [InterestController::class, 'store']);
            Route::post('/interests/{interest}/respond', [InterestController::class, 'respond']);
            Route::delete('/interests/{interest}', [InterestController::class, 'destroy']);

            Route::get('/blocks', [SafetyController::class, 'blocks']);
            Route::post('/blocks', [SafetyController::class, 'block'])->middleware('throttle:20,1');
            Route::delete('/blocks/{user}', [SafetyController::class, 'unblock']);
            Route::post('/reports', [SafetyController::class, 'report'])->middleware('throttle:10,1');

            Route::get('/conversations', [ConversationController::class, 'index']);
            Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
            Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'store'])->middleware('throttle:30,1');
            Route::post('/conversations/{conversation}/read', [ConversationController::class, 'read']);
            Route::delete('/messages/{message}', [ConversationController::class, 'destroy']);
            Route::get('/photo-access-requests', [PhotoAccessRequestController::class, 'index']);
            Route::post('/photo-access-requests', [PhotoAccessRequestController::class, 'store'])->middleware('throttle:10,1');
            Route::post('/photo-access-requests/{photoAccessRequest}/respond', [PhotoAccessRequestController::class, 'respond']);
            Route::delete('/photo-access-requests/{photoAccessRequest}', [PhotoAccessRequestController::class, 'destroy']);
        });
    });
});

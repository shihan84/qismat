# Qismat Project Status

Last updated: 2026-09-26

## Overall

**Phase:** Identity and profile foundation
**Overall progress:** 76%
**Current release:** 0.1.0-dev

| Area | Status | Progress |
|---|---|---:|
| Architecture | In progress | 70% |
| Backend/API | In progress | 95% |
| Website | In progress | 95% |
| Admin dashboard | In progress | 82% |
| Flutter mobile app | In progress | 20% |
| Android CI/CD | In progress | 60% |
| iOS/Xcode Cloud | Planned | 5% |
| cPanel deployment | Staging operational | 82% |
| QA/security | In progress | 25% |

## Completed in current foundation batch

- [x] GitHub repository verified and initialized
- [x] cPanel + SSH production target recorded
- [x] Android build target: GitHub Actions
- [x] iOS build target: Xcode Cloud
- [x] Architecture decision records and roadmap
- [x] Backend API/domain overlay started
- [x] MySQL/MariaDB reference schema v1
- [x] Initial authentication and profile controller/model code
- [x] Activity and admin audit tracking schema
- [x] Flutter application shell
- [x] Public web shell
- [x] Admin dashboard shell
- [x] Android GitHub Actions workflow
- [x] Web/admin GitHub Actions workflow
- [x] Full Laravel 12 application skeleton and Artisan bootstrap
- [x] Laravel Sanctum token authentication bootstrap
- [x] API response contract and CORS configuration
- [x] Backend migrations, factories, plan seed data and API test suite
- [x] Backend GitHub Actions workflow
- [x] Latest Android CI result confirmed green after BUG-001
- [x] Firebase ID-token verification middleware and Sanctum exchange API
- [x] Firebase-verified-email enforcement before user synchronization
- [x] cPanel SSH/database preflight workflow
- [x] Profile onboarding, moderation state and explicit discovery opt-in API
- [x] Shared API gateway at `admin.qismatconnections.com/api/v1`
- [x] Successful cPanel staging deployment with database migrations and HTTPS health verification
- [x] Pre-migration database/member-file backup and first atomic staging release verified
- [x] Web, admin, backend and Android checks passing for the deployed release
- [x] Production web email/password registration, verification, login and recovery
- [x] Laravel Firebase authentication proxy and verified-ID-token exchange
- [x] Apex and www authentication CORS coverage
- [x] Google sign-in website integration; provider activation remains
- [x] Apple sign-in hidden until Apple Developer configuration is available
- [x] Structured family/career profile fields and partner-preference API deployed
- [x] Member web profile, partner-preference and visibility integration deployed
- [x] Private photo API, member photo manager and admin photo moderation deployed
- [x] Private-photo access requests, owner-controlled reveal decisions and member trust indicators implemented
- [x] Discovery filters, safe profile details, favourites and privacy-aware profile views implemented
- [x] Member discovery/saved-profile UI and admin eligibility diagnostics implemented
- [x] Sent/received interest management with accept, decline and cancellation
- [x] Bidirectional blocking that immediately removes discovery, favourites and active interests
- [x] Confidential reports and audited administrator resolution/suspension queue
- [x] Competitor-informed trust-first product strategy recorded
- [x] Mutual-match conversations, paginated messages, unread/read state, deletion, polling and conversation safety controls

## In progress / next

- [x] Firebase project/service-account configuration
- [ ] Complete remaining member web and admin launch workflows
- [x] Member web authentication and profile onboarding integration
- [ ] Enable Google in Firebase Console and run end-to-end Google login QA
- [x] Profile photos and privacy rules
- [x] Partner-preference model and API
- [x] Inclusive faith/community/ethnicity profile fields and 18–100 age enforcement
- [x] Member notification preferences, blocked-member management, global sign-out and account deletion
- [x] Public privacy, terms, safety and account-deletion/support pages
- [x] Admin member search, account suspension/reactivation, verification controls and audit history
- [ ] Recommendation engine
- [x] Search/filter APIs
- [x] Interests, favourites and profile views foundation
- [x] Conversations/messages after mutual acceptance
- [x] Block/report/moderation APIs and first web/admin workflows
- [ ] Connect web/admin/mobile shells to API
- [x] Admin authentication and profile moderation APIs/UI foundation
- [ ] Pin the cPanel SSH host key in GitHub Secrets
- [ ] Add atomic releases and a tested staging rollback procedure
- [ ] Xcode Cloud configuration after web, admin and Android stabilization

## Build identifiers

| Component | Version/build |
|---|---|
| Platform | 0.1.0-dev |
| Backend | Laravel 12 / API foundation 003 |
| Web | shell 001 |
| Admin | shell 001 |
| Android | shell 001 |
| iOS | shell 001 |
| Database schema | through migration `2026_09_26_000500` |

## Major blockers and risks

- cPanel SSH, PHP 8.2, MariaDB client and Git are verified. Server Composer and Node.js are unavailable, so deployable artifacts are built in GitHub Actions.
- MariaDB authentication and database access are verified by the cPanel preflight.
- `CPANEL_API_PATH`, Firebase credentials and the persistent `LARAVEL_APP_KEY` are configured.
- Staging is deployed and healthy through the admin-domain gateway.
- The Flutter application remains an early shell and has not completed Firebase authentication integration.
- Google web sign-in code is deployed, but the Google provider must still be enabled in Firebase Console.
- Apple sign-in is intentionally deferred pending Apple Developer credentials.
- Firebase currently blocks email-template edits for this project. Default verification emails work; branded email delivery is deferred or can later move to Laravel with custom SMTP.
- cPanel deployment uses immutable releases and atomic activation with automatic/manual application rollback. A verified pinned host key, off-host backup copy and restore/rollback rehearsals remain outstanding.
- No production migration is authorized until database/storage backup and rollback procedures are completed.
- Apple Developer and payment-provider credentials remain pending for their later stages.

## Delivery priority

The active product phase completes and stabilizes the full member web and admin launch scope first. Android implementation starts after that gate. Native iOS implementation follows the stabilized Android/shared feature set and uses Xcode Cloud for builds and releases.

## Rule

Update this file whenever a meaningful feature, deployment, release, architecture decision or blocker changes project state.

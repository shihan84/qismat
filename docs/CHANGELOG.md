# Changelog

## 2026-09-26 — Private photo access and trust indicators

- Added member-to-member private-photo access requests with owner approval, decline, cancellation and revocation.
- Enforced approved access at the photo-content boundary, including immediate denial when either member blocks the other.
- Added received and sent photo-request management to the member website.
- Added profile-reviewed and identity-verified trust indicators to discovery details.

## 2026-09-26 — Administrator member operations

- Added role-protected member search by name, email and profile code with account, moderation, verification and report context.
- Added audited member suspension/reactivation that revokes sessions and safely removes suspended profiles from discovery.
- Added reviewed and identity-verification controls with mandatory administrator reasons.
- Added a searchable administrator audit-log view and working admin navigation links.

## 2026-09-26 — Account controls and public policies

- Added member notification preferences for interest, messaging, moderation and product email/push categories.
- Added blocked-member review and unblock controls plus global API-session revocation.
- Added confirmed account deletion that removes profile data and stored photos while retaining a non-login tombstone for abuse prevention.
- Added linked privacy, terms, safety and account-deletion/support pages to the public website.

## Unreleased

- Added optional religion, sect or denomination, community or caste, sub-community or sub-caste, and ethnicity fields across profiles, partner preferences, discovery, and admin moderation.
- Enforced an inclusive member age range of 18 through 100 at profile entry and discovery eligibility, matching the existing preference and search limits.
- Added member web photo management, an administrator photo-review queue and an approved-primary-photo requirement for discovery.
- Added private profile-photo upload, metadata removal, ordering, primary-photo selection, visibility authorization and audited admin moderation APIs.
- Connected the member web profile editor to structured family, career, privacy and partner-preference fields, and enforced private visibility across discovery and interests.
- Added a shared, validated partner-preference API and structured family, career and profile-visibility fields for web and mobile clients.
- Added staged atomic cPanel releases, automatic and operator-selected application rollback, pre-deployment snapshots and daily database/member-file backups.
- Added optional pinned SSH `known_hosts` configuration with an explicit warning while transitional key scanning remains enabled.
- Added the complete production-v1 execution plan, Android release path and measurable launch gates.
- Added a public marriage-purpose and anti-fraud warning, plus required acknowledgments during account registration and profile submission.
- Added Laravel-proxied Firebase email/password authentication and www CORS coverage.
- Added Google sign-in to the member website and connected its Firebase token to the existing Laravel session exchange.
- Deferred and hid Apple sign-in until Apple Developer credentials are available.
- Recorded the future branded authentication-email plan while default Firebase emails remain operational.
- Added Firebase email registration, verification, login and password recovery to the member website.
- Added Firebase admin login with server-enforced active-admin authorization.
- Added admin dashboard counts, a pending-profile moderation queue and audited approve/reject decisions.
- Added the `qismat:admin` console command for controlled administrator provisioning and session revocation.
- Added Firebase client configuration to the staging deployment workflow.
- Added complete member web profile editing, completion tracking, moderation submission and discovery controls.
- Added persistent moderation feedback with reviewer attribution and protected it from discovery responses.

All notable changes to Qismat will be recorded here.

## [Unreleased] - 2026-09-20

### Added
- Profile onboarding, moderation state and explicit discovery opt-in APIs.
- Shared API gateway at `https://admin.qismatconnections.com/api/v1`.
- Client endpoint configuration for web, admin, Android and iOS.
- Automatic cPanel staging deployment for relevant upstream `main` changes.

### Fixed
- Corrected active-member defaults in backend factories.
- Prevented direct interests from bypassing profile discovery eligibility.
- Cleared stale submission timestamps when reviewed profile data changes.
- Validated match age and demographic filters.
- Serialized staging deployments and made the health check validate the expected JSON service response.
- Avoided the invalid `api.qismatconnections.com` TLS certificate.

## [0.1.0-dev] - 2026-09-15

### Added
- Repository initialization
- Project scope and hosting targets
- Foundation branch
- Project status tracker
- Roadmap
- Task tracker
- Architecture document
- Architecture decision log
- Bootable Laravel 12 framework application and Artisan entry point
- Laravel Sanctum personal access token support
- Standard API success/error response baseline and CORS configuration
- Current-user API and inactive-account login enforcement
- Public `QSM` matrimonial profile identifiers generated independently of database IDs
- Reproducible migrations, membership plan seed data and model factory
- API tests for health, registration, login, authentication, profile management and interest rules
- Backend PHP 8.2 GitHub Actions workflow
- Locked web/admin dependency graphs and deterministic `npm ci` builds
- Firebase ID-token verification middleware and Firebase-to-Sanctum exchange endpoint
- Firebase UID mapping and verified-email enforcement for local Qismat accounts
- Non-deploying cPanel server/database preflight workflow
- cPanel deployment workflow with CI-built Composer dependencies, protected Firebase credential upload, managed `.env` merging, migrations and API health verification

### Changed

- Existing auth, profile, match and interest endpoints now use the common API response envelope.
- Duplicate interest attempts return the existing record without duplicating activity history.
- Backend status advanced from a source overlay to a runnable Laravel application.
- Authentication ownership moved from Laravel password/Gmail SMTP flows to Firebase Authentication; Laravel remains the API authorization and business-data authority.
- Encrypted cPanel SSH deployment keys are supported through a separate passphrase secret.

### Planned
- Authentication and verification APIs
- Flutter mobile skeleton
- Web/member portal shell
- Admin dashboard shell
- CI/CD workflows

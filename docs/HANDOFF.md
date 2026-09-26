# Qismat Contributor Handoff

Last updated: 2026-09-26

## Purpose

This document is the starting point for contributors joining Qismat. Read it together with `PRODUCTION_PLAN.md`, `OPERATIONS.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TASKS.md`, `DECISIONS.md` and `DEPLOYMENT_LOG.md` before changing application or deployment code.

## Repository and environments

- Repository: `shihan84/qismat`
- Integration branch: `main`
- Member website: `https://qismatconnections.com`
- Member website alias: `https://www.qismatconnections.com`
- Admin website: `https://admin.qismatconnections.com`
- Shared API: `https://admin.qismatconnections.com/api/v1`
- Health endpoint: `https://admin.qismatconnections.com/api/v1/health`
- Latest verified staging release: `9407116f3b4c0e473913759efa04fdc23180a32c`
- Verified cPanel deployment run: `36246502479`

The Laravel runtime is deployed outside the public admin document root. The admin domain routes only `/api/*` to Laravel; other non-file requests load the React admin application.

## Architecture

- `backend/`: Laravel 12 API, MySQL/MariaDB data, Firebase token verification and Sanctum API sessions.
- `web/`: public matrimonial website and member portal.
- `admin/`: administrator authentication and profile-moderation dashboard.
- `mobile/`: Flutter application shell for Android and iOS.
- `.github/workflows/`: backend, web/admin, Android and cPanel deployment automation.
- `docs/`: product status, plans, decisions, bugs and deployment history.

Firebase owns member identity. Laravel verifies Firebase ID tokens, synchronizes the local user by Firebase UID and issues a Sanctum token. Laravel/MySQL remains the authority for profiles, roles, moderation, discovery and other business data.

Email/password registration, login and password reset are proxied through Laravel. This avoids browser API-key and origin problems while retaining Firebase verification emails. Both apex and `www` member origins are permitted by Laravel CORS.

## Implemented and deployed

- Premium public landing page and responsive member access panel.
- Firebase email/password registration, email verification, login and password recovery.
- Laravel Firebase proxy, server-side ID-token verification and Sanctum exchange.
- Google sign-in website integration and Laravel token exchange.
- Apple sign-in code removed from the visible flow until credentials are available.
- Member profile editing, required-field readiness, moderation submission and discovery controls.
- Public marriage-purpose and anti-fraud warning with registration and profile-submission acknowledgments.
- Persistent moderation feedback returned to the member without leaking into discovery.
- Admin login, active-admin role enforcement, dashboard counts and audited approve/reject moderation.
- Profile CRUD, moderation states, discovery eligibility and interest send/respond API foundation.
- Structured family/career fields and member-scoped partner-preference CRUD with safe age/height ranges.
- Optional faith, denomination, community/caste, sub-community/sub-caste and ethnic-background data shared by profiles, preferences, discovery and moderation, with member ages limited to 18–100.
- Member notification controls, blocked-member management, global session revocation and safeguarded account deletion with private media removal.
- Public privacy, terms, safety and account-deletion/support pages linked from the website footer.
- Administrator member search, audited suspension/reactivation, reviewed/identity-verification controls and searchable audit history.
- Member web editing for structured profile fields, matching preferences and enforced profile visibility.
- Private metadata-stripped profile photos, member photo management, audited admin photo moderation and approved-primary-photo discovery gating.
- Owner-controlled private-photo access requests with approve, decline, cancellation and revocation, plus reviewed and identity-verified discovery indicators.
- Privacy-safe member discovery with full filters, explainable preference matches, profile detail views, daily view recording and favourites.
- Member discovery/saved-profile screens and administrator discovery-eligibility diagnostics.
- Complete sent/received interest management, bidirectional blocking, confidential reports and an audited administrator safety queue.
- Mutual-connection messaging with unread/read state, member deletion, polling, rate limits and report/block actions.
- cPanel database preflight, managed environment deployment, migrations, frontend deployment and API health checks.
- Pre-migration database/member-file snapshots, immutable application releases, atomic activation and automatic/manual application rollback workflows.
- CI builds for backend, web/admin and Android.

## Immediate priorities

1. Enable Google under Firebase Authentication → Sign-in method and complete a live Google login test.
2. Provision the first production administrator from the server console and verify the moderation workflow.
3. Verify the public support mailbox and approve the published privacy, terms, safety and retention language before launch.
4. Add phone verification and profile-completion guidance prompts; private-photo reveal requests and current trust indicators are implemented.
5. Complete notification delivery, observability and production-release hardening; rehearse rollback and backup restore, configure off-host copies and pin SSH host verification.
6. Start Android implementation only after the web and admin launch scope is complete and stable.

## Explicitly deferred

- Apple login: wait for Apple Developer membership, Service ID, Team ID, Key ID and private key.
- Branded authentication email: default Firebase verification/reset emails work, but Firebase currently blocks template edits. Revisit Firebase template access or use Firebase Admin action links with an approved Laravel SMTP provider.
- Native iOS release work and Xcode Cloud: start after shared web, admin and Android behavior stabilizes.
- Payments: wait for an approved provider and credentials.

## Known operational notes

- The Google provider code is deployed, but Google must still be enabled in Firebase Console.
- Default Firebase verification emails may enter Spam and currently use Firebase project branding.
- The first administrator must be promoted from the server console after their Firebase account has signed in once:

  `php artisan qismat:admin admin@example.com`

- Never add a public role-assignment endpoint.
- The obsolete `api.qismatconnections.com` hostname is not used because cPanel presents an unrelated certificate for it.
- cPanel does not provide the required build tools, so Composer and frontend production artifacts are built in GitHub Actions.
- Deployments now use immutable release directories and atomic entry-point switches. Application rollback never reverses database migrations.

## Contributor workflow

1. Fetch the latest `origin/main` before starting.
2. Create a focused branch from `origin/main`; do not commit directly to `main`.
3. Keep one concern per pull request and include verification performed.
4. Run relevant tests and production builds locally before pushing.
5. Open a pull request against `main` and wait for required GitHub Actions checks.
6. Resolve conflicts by preserving newer upstream work; do not overwrite another contributor's changes.
7. Merge only after checks pass and the change is reviewed.
8. Update the appropriate files in `docs/` whenever scope, status, architecture, deployment or blockers change.

Useful local checks:

- Backend: `cd backend && php artisan test`
- Member web: `npm --prefix web ci && npm --prefix web run build`
- Admin: `npm --prefix admin ci && npm --prefix admin run build`
- Flutter: `cd mobile && flutter analyze && flutter test`

## Deployment behavior

Relevant changes merged to `main` automatically run `.github/workflows/deploy-cpanel.yml`. Documentation-only and mobile-only changes do not deploy cPanel. Deployments are serialized and perform:

1. Secret validation.
2. Laravel production dependency build.
3. Member and admin production builds.
4. Database connectivity verification.
5. Managed Laravel environment preparation.
6. Backend and frontend upload.
7. Database migrations.
8. Admin-domain API gateway integration.
9. Exact JSON health verification.

For application rollback, redeploy a known-good Git reference through the manual workflow input. Review database migrations separately and restore from a tested backup when required. Record material deployments and rollbacks in `DEPLOYMENT_LOG.md`.

## Secrets and access boundaries

Required GitHub secrets include:

- `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_PORT`
- `CPANEL_SSH_KEY`, `CPANEL_SSH_KEY_PASSPHRASE`
- `CPANEL_API_PATH`, `CPANEL_WEB_PATH`, `CPANEL_ADMIN_PATH`
- `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`
- `FIREBASE_PROJECT_ID`, `FIREBASE_WEB_API_KEY`
- `FIREBASE_SERVICE_ACCOUNT_JSON`
- `LARAVEL_APP_KEY`
- `CPANEL_SSH_KNOWN_HOSTS` (recommended now; required before production launch)

Contributors may reference secret names but must never request, print, download, copy or commit their values. Do not place production credentials in issues, pull requests, logs, screenshots, handoff documents or chat messages. Changes to secrets, Firebase providers, DNS, administrator roles or production infrastructure require owner coordination.

## Handoff expectations

Every contributor should leave:

- A focused pull request with a clear summary.
- Tests/builds performed and their results.
- Any migration, secret-name or deployment impact.
- Remaining limitations and the next recommended step.
- Updated project documentation when the change affects tracked scope or status.

If work is incomplete, leave the branch in a buildable state and document the exact blocker rather than merging partial production behavior.

# Qismat Task Tracker

Execution order and acceptance criteria are defined in `PRODUCTION_PLAN.md`; this file remains the compact completion checklist.

## In progress
- [x] Add Firebase project/service-account configuration
- [x] Deploy web Firebase Authentication
- [x] Add Google sign-in UI and Firebase-to-Laravel token exchange
- [ ] Enable Google provider in Firebase Console and complete live login QA
- [x] Deploy admin authentication and moderation UI
- [ ] Provision and verify the first production administrator account

## Deferred
- [ ] Apple login after Apple Developer credentials are available
- [ ] Branded verification/password-reset email delivery after Firebase template access is restored or custom SMTP is approved

## Delivery order
- [ ] Complete member web modules
- [ ] Complete admin modules and moderation operations
- [ ] Complete Android modules against the shared API
- [ ] Stabilize shared behavior and complete cross-platform QA
- [ ] Build and release the native iOS client through Xcode Cloud

## Next
- [x] Profile CRUD and onboarding-state APIs
- [x] Private photo upload/storage, metadata removal and authorization rules
- [x] Partner preference model and API
- [x] Search/filter API
- [x] Interest send/respond API foundation
- [x] Shortlist/favourites
- [x] Explainable matching/recommendation foundation
- [x] Mutual-match chat foundation with read state, deletion and safety controls
- [x] Interest inbox, acceptance, decline and cancellation
- [x] Bidirectional block enforcement and confidential member reports
- [x] Admin safety report queue, suspension and audited resolution
- [x] Account privacy controls, notification preferences and public policy/support pages
- [x] Admin moderation foundation
- [x] Admin member search, status/verification operations and audit-log review
- [x] Private-photo access requests, owner decisions and revocation
- [ ] Membership/payment foundation
- [ ] Notifications
- [ ] Finish all member web and admin launch workflows and production QA
- [ ] Android Firebase registration/login/verification/password recovery after the web/admin completion gate

## DevOps
- [x] `.env.example` files without secrets
- [x] GitHub Actions Android build
- [x] Backend/web CI checks
- [x] cPanel deploy workflow with CI-built Laravel dependencies
- [x] cPanel SSH/database preflight workflow
- [x] staging configuration and successful deployment
- [x] Add atomic release, automatic rollback and manual rollback workflow implementation
- [x] Add pre-deployment and daily database/member-file backup automation
- [x] Verify the first atomic staging deployment with a pre-migration backup and public health checks
- [ ] Rehearse operator-selected application rollback on staging
- [ ] Rehearse a backup restore into a separate non-production environment
- [ ] Configure encrypted off-host backup copies and retention
- [ ] Add and verify the `CPANEL_SSH_KNOWN_HOSTS` secret, then remove transitional key scanning
- [ ] production configuration
- [ ] Xcode Cloud setup after web, admin and Android stabilization

## Completed
- [x] Confirm `shihan84/qismat` repository access
- [x] Initialize repository
- [x] Create foundation branch
- [x] Add project status tracking
- [x] Add roadmap
- [x] Finalize initial architecture ADRs
- [x] Create Laravel 12 backend skeleton
- [x] Convert schema v1 to runnable Laravel migrations
- [x] Add Sanctum authentication bootstrap
- [x] Add API response/CORS baseline
- [x] Add backend API tests and CI workflow
- [x] Create Flutter app shell
- [x] Create web/member portal shell
- [x] Create admin dashboard shell
- [x] Confirm Android CI green after BUG-001
- [x] Add Laravel Firebase ID-token verification middleware
- [x] Add Firebase-to-Sanctum token exchange API
- [x] Require Firebase-verified email before local account synchronization
- [x] Route the shared API through `admin.qismatconnections.com/api/v1`
- [x] Deploy and smoke-test cPanel staging
- [x] Implement web registration, verification, login, recovery and Laravel session exchange
- [x] Implement admin login, role enforcement, moderation queue and approve/reject audit trail
- [x] Implement member web profile editing, completion, moderation submission and discovery controls
- [x] Return admin rejection feedback to the member without exposing it in discovery results
- [x] Proxy email/password Firebase operations through Laravel
- [x] Allow both apex and www member origins through Laravel CORS
- [x] Add Google sign-in to the member website
- [x] Hide Apple sign-in until its provider credentials are available
- [x] Deploy validated structured profile fields and partner-preference API
- [x] Connect member web profile, partner preferences and visibility to the shared API
- [x] Add member photo management and audited admin photo moderation

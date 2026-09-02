# Backend / Frontend Separation Plan

## Goal
Separate the Laravel backend from the user interface while preserving current monitoring workflows, authentication, and data.

## Proposed Direction
1. **Define boundaries**
   - Laravel owns API routes, authentication, authorization, validation, database access, and business logic.
   - A standalone frontend owns pages, components, client state, forms, and presentation.

2. **Stabilize the backend contract**
   - Add versioned `/api` endpoints for access, dashboard, tasks, calendar, FAE management, profile updates, and notifications.
   - Standardize JSON responses, validation errors, pagination, and HTTP status codes.
   - Preserve role checks in Laravel middleware; never trust frontend-only permissions.

3. **Extract the frontend**
   - Create a separate frontend app using the approved framework and existing Vite tooling where practical.
   - Rebuild Blade screens as frontend routes/components and move CSS/JavaScript ownership there.
   - Add API client configuration for local development and production.

4. **Migrate incrementally**
   - Implement and test one workflow at a time, starting with access/login and dashboard.
   - Move tasks, calendar, FAE management, and profile features next.
   - Keep temporary compatibility redirects for existing PHP/Blade entry points.

5. **Finalize and verify**
   - Remove unused Blade/legacy PHP paths only after feature parity is confirmed.
   - Add backend API tests and frontend workflow tests.
   - Document environment variables, CORS/CSRF, authentication, builds, and deployment.

## Clarifications Needed
- Should the frontend be React, Vue, or another framework?
- Should backend and frontend live in separate repositories or folders in this repository?
- Should authentication use Laravel session cookies or token-based API authentication?
- Are the legacy root PHP files (`access.php`, `calendar.php`, `fae.php`, `tasks.php`, etc.) still required during migration?
- Will frontend and backend run on different domains in production?

# Project Context
This is a self-hosted D&D virtual tabletop application (Roll20-like).
Backend: Laravel 11, PHP 8.3
API: REST, JSON responses only, no Blade views or SSR
Frontend is a separate Vue 3 app consuming this API

# Architecture & Conventions

## Structure
- Routes defined in routes/api.php, all prefixed with /api/v1
- Controllers are thin — no business logic inside them
- Business logic lives in Service classes in app/Services/
- Data access logic lives in Repository classes in app/Repositories/
- Form validation via dedicated BaseRequest classes in app/Http/Requests/
- API responses via dedicated Resource classes in app/Http/Resources/
- Models only contain relationships, casts, and fillable — nothing else

## Response Format
All responses must follow this structure:
success response:  { "data": {...}, "message": "..." }
error response:    { "message": "...", "errors": {...} }
Always use Laravel API Resources, never return Model instances or arrays directly

## Auth
- Auth via Laravel Sanctum with token-based auth (not cookies, SPA is separate origin)
- Unauthenticated requests return 401
- Unauthorized actions return 403

# Current Feature: Users, Campaigns & Roles

## Domain Rules
- A User can create multiple Campaigns — creator automatically becomes Dungeon Master
- A User can be a member of multiple Campaigns as a Player
- Role (dm/player) is scoped per Campaign, not global
- Only the DM of a Campaign can invite members, remove members, and update campaign details
- A Campaign can be active or archived — archived campaigns are read-only
- DM cannot leave their own campaign, they can only delete it

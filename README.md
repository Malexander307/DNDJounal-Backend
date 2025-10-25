# DNDJournal — Backend

This is the backend part of my project. As a D&D player and a newly started DM I had trouble saving all data about campaigns, and this project was created to help with that.

## Overview
DNDJournal stores and manages campaign data: campaigns, sessions, characters, NPCs, notes, assets, and related metadata. It exposes a RESTful API used by frontend clients or tools to create, read, update and delete campaign-related data.

## Key Features
- Campaign management (create, update, archive).
- Session logs and timelines.
- Character and NPC CRUD.
- Notes and attachments (assets).
- Simple authentication for API access.
- Migrations and tests included.

## Tech stack
- PHP 8.x
- Laravel (backend framework)
- MySQL / MariaDB (or other supported DB)
- Composer for dependencies

## Quick Start

Prerequisites:
- PHP 8.x, Composer, a database (MySQL/Postgres), and Git.

Setup:
1. Clone the repository:
   git clone <repo-url> .
2. Install dependencies:
   composer install
3. Copy environment file and set DB + APP settings:
   cp .env.example .env
   # edit .env accordingly
4. Generate app key:
   php artisan key:generate
5. Run migrations:
   php artisan migrate
6. (Optional) Seed data:
   php artisan db:seed
7. Run local server:
   php artisan serve

Run tests:
php artisan test

### Using Sail (Docker)

This project includes Laravel Sail (Docker) for local development.

- Start the containers (detached):
  ./vendor/bin/sail up -d
- Stop and remove containers:
  ./vendor/bin/sail down
- Rebuild images:
  ./vendor/bin/sail build --no-cache
- Run Artisan or Composer inside the app container:
  ./vendor/bin/sail artisan migrate
  ./vendor/bin/sail composer install
- Run tests:
  ./vendor/bin/sail test
- Open a shell in the app container:
  ./vendor/bin/sail shell

Ports and environment
- The frontend is available at http://localhost:${APP_PORT:-8000} (APP_PORT in .env).
- To avoid host port collisions the compose file reads:
  - FORWARD_DB_PORT (default 3307) → maps to container 3306
  - FORWARD_REDIS_PORT (default 6381) → maps to container 6379
  Edit these in .env if the defaults conflict with other services on your machine.

Useful tip
- If you prefer a shorter command you can create an alias:
  alias sail='./vendor/bin/sail'

## Contributing
Contributions are welcome. Please open issues for features/bugs and submit PRs for changes. Keep changes small and include tests where applicable.

## License
This project is open source. See the LICENSE file for details.

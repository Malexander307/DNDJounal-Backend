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

## Contributing
Contributions are welcome. Please open issues for features/bugs and submit PRs for changes. Keep changes small and include tests where applicable.

## License
This project is open source. See the LICENSE file for details.

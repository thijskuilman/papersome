# <img src="https://github.com/user-attachments/assets/16a1d594-4f46-49f1-a56c-f407e91bf75c" width="40" /> Papersome

<h4>Papersome is a self-hosted application for creating ePub newspapers or magazines from RSS/Atom feeds.</h4>

You can add RSS feeds as sources, bundle them into collections, and read them in a newspaper or magazine-style layout with scheduled syncing to other platforms.

| Build RSS collections... | ... and read noise-free articles anywhere |
|-----------|---------|
| <img width="3000" height="1800" alt="newspaper2" src="https://github.com/user-attachments/assets/69739a82-7668-4203-aed7-26f9b3112513" /> | ![Newspaper Article](https://github.com/user-attachments/assets/d1c87127-909a-4553-a51d-965c6c82bc0d) |
| ![Magazine Frontpage](https://github.com/user-attachments/assets/8448a3e2-597e-4228-ae4c-e7722b48621e) | ![Magazine Article](https://github.com/user-attachments/assets/6df2df19-62d8-4e9e-a3e5-ed6f66198401) |

## Installation
Papersome is still in development. Below are Docker-based instructions for local development and a production-like run.

### Docker Quickstart (local dev)

Requirements: Docker Desktop 4+, Node/npm are bundled in the app container.

1. Build images
   - `docker compose build`
2. Install PHP deps
   - `docker compose run --rm php composer install --no-interaction --prefer-dist`
3. Generate app key (writes to your local `.env`)
   - `docker compose run --rm php php artisan key:generate`
4. Install frontend deps
   - `docker compose run --rm php npm ci`
5. Build assets once (if you won’t run HMR)
   - `docker compose run --rm php npm run build`
6. Start the stack
   - `docker compose up -d`
7. (Optional) Start Vite HMR for faster frontend dev
   - `docker compose up -d node`

Visit: http://localhost:8080

Notes:
- Containers share an env file `.env.docker`. It’s dev-friendly: `APP_ENV=local`, `APP_DEBUG=true`.
- `APP_URL` is set to `http://php:8080` so other containers (like headless Chrome) can reach the app.
- A `chromium` service is provided for Browsershot (remote-debugging port 9222). Config is in `config/browsershot.php`.
- A `queue` worker and a `scheduler` service are included. Migrations and seeds are enabled by default via autorun.
- If you use Unraid or a NAS, set `PUID`/`PGID` environment vars when running compose so files are written with the correct ownership (e.g., `PUID=99 PGID=100`).

### Production-like run (local)

Use the override file to enable caches and opcache:

```
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

This keeps the same services but flips to `APP_ENV=production`, disables seeding by default, enables Laravel caches, and turns on PHP opcache.

### Common commands

- Tail logs: `docker compose logs -f php`
- Run artisan: `docker compose exec php php artisan <command>`
- Run migrations manually: `docker compose exec php php artisan migrate --seed`
- Queue worker (already running): `docker compose logs -f queue`
- Stop everything: `docker compose down`

### Troubleshooting

- If styles/scripts don’t update during development, make sure the `node` service is running for HMR, or re-run `npm run build` in the `php` container.
- If Vite HMR cannot connect, ensure port 5173 is free and published; we run Vite with `--host 0.0.0.0` and map `5173:5173`.
- If Browsershot times out, verify the `chromium` container is healthy and that `APP_URL` points to `http://php:8080` (internal DNS) in `.env.docker`.

## Key Features

- Add and manage RSS/Atom feeds
- Group feeds into collections
- Read collections in newspaper or magazine layouts in ePub format
- Schedule new publications for collections, for example daily or specific days
- Synchronize publications to Booklore. If you use [Kobo Sync]([https://duckduckgo.com](https://booklore.org/docs/integration/kobo )), they’re also synced to your Kobo device for offline reading.
- Finetune the output of RSS feed articles via HTML filters
- Multi-user support

## Screenshots
| ☀️ Light | 🌙 Dark |
|-----------|-----------|
| <img height="200px" alt="shelf-light" src="https://github.com/user-attachments/assets/24575ad7-5f8a-4d8a-b193-399fda47ca54" /> | <img height="200px" alt="shelf-dark" src="https://github.com/user-attachments/assets/e3db5ae8-1edd-47f5-81ce-0d7cdfc28fc7" /> |
| <img height="200px" alt="sources-light" src="https://github.com/user-attachments/assets/3a7e7ae4-3b65-44f3-9ddc-d2bf523c0a6f" /> | <img height="200px" alt="sources-dark" src="https://github.com/user-attachments/assets/e655a2c2-dee9-4c9e-a79f-6f1e405afabd" /> |
| <img height="200px" alt="collections-light" src="https://github.com/user-attachments/assets/9fc5ca1f-936c-48d0-b01b-ca664c34e9e6" /> | <img height="200px" alt="collections-dark" src="https://github.com/user-attachments/assets/c141b2c7-8b1b-491c-8d11-76e3f4bd2a0d" /> |
| <img height="200px" alt="collection-detail-light" src="https://github.com/user-attachments/assets/9057ee9e-8cb6-47e6-ae0b-123a4fc5b847" /> | <img height="200px" alt="collection-detail-dark" src="https://github.com/user-attachments/assets/4d50df70-51cc-4c1e-ba57-272ff0519099" /> |
| <img height="200px" alt="source-layout-settings-light" src="https://github.com/user-attachments/assets/545e89df-d156-4759-921f-6822ae449ad1" /> | <img height="200px" alt="source-layout-settings-dark" src="https://github.com/user-attachments/assets/b2c3e582-6594-4e7c-883c-992108075d98" /> |

## Tech

- PHP 8.5+
- Laravel 12
- Livewire v4
- Flux UI
- Filament v5
- Tailwind CSS v4
- Pest v4
- Laravel Pint
- Rector

## Contributing
Contributions are welcome! If you’d like to propose a change:

- Fork the repo and create a feature branch.
- Add or update tests as needed and ensure they pass locally.
- Run Pint to format code.
- Open a pull request.

If you’re planning a larger change, please open an issue first to discuss the direction.

## Security

If you discover a security vulnerability, please open a private security advisory on GitHub or contact the maintainers
directly. Please do not create a public issue for security reports.

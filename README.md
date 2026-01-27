# readability-rss-proxy

A self-hosted RSS proxy that fetches articles and enriches them using a readability engine. It serves full-text RSS/Atom feeds to your favorite reader, providing a "pseudo-offline" experience.

## Overview

Traditional RSS feeds often only provide a short summary. This proxy:
1.  **Fetches** the original feed.
2.  **Extracts** the full content of each article using a `readability-js-server`.
3.  **Persists** the enriched content in a local database.
4.  **Serves** a new feed with full-text content.

It also includes a built-in web reader and an admin interface for managing feeds.

For a detailed breakdown of the system components, see [AGENTS.md](AGENTS.md).

## Status

**Beta.** It's functional and used daily by the author, but installation can be tricky, and some UX areas (like the reader mode) are still being refined.

## Requirements

### Development
*   **Docker & Docker Compose**
*   **Make**
*   **`libnss3-tools`** (for `certutil`, used by `mkcert` to set up local SSL)

### Production / Manual Install
*   **PHP 8.0+**
*   **PostgreSQL 9.6+** (Recommended. MySQL is possible but requires manual schema updates as migrations are psql-specific)
*   **Redis** (for session storage and caching)
*   **readability-js-server** (The enrichment engine)
*   **Web server** (Nginx/Apache)

## Quick Start (Development)

1.  **Initialize the environment:**
    ```bash
    make init
    ```
    This command will:
    *   Build the PHP container.
    *   Set up local SSL certificates via `mkcert`.
    *   Configure `rss-proxy.local` in your hosts file.
    *   Start all services (PHP-FPM, Nginx, PostgreSQL, Redis, Readability server).
    *   Run migrations and load sample data.

2.  **Access the application:**
    Open [https://rss-proxy.local:7000/](https://rss-proxy.local:7000/)

3.  **Default Users:**

| Role | Username | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@admin.com` | `admin` |
| **Regular** | `non_admin@admin.com` | `non_admin` |

## Maintenance & CLI Commands

The application uses Symfony console commands for background tasks. When using Docker, run these via `docker-compose exec php-fpm bin/console [command]`.

*   **`feed:fetch-all`**: Manually trigger a refresh of all feeds. (Also available via `make sync-feeds`)
*   **`feed:delete-old`**: Clean up old feed items.
*   **`user:create`**: Create a new user.

## Misc Notes

*   **Ad Blockers:** Some ad blockers may mistakenly block feed icons or assets in the admin panel (e.g., uBlock blocking the BBC logo). If icons are missing, try disabling your ad blocker for the proxy domain.
*   **Production Deployment:** A `Dockerfile` and Kubernetes manifests (`infrastructure/kubernetes`) are provided for deployment inspiration. Basically, you need to replicate the stack defined in `docker-compose.yaml`.

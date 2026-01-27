### Project Agents

This document describes the main components (agents) of the `readability-rss-proxy` project and their responsibilities.

#### 1. Feed Processor (`App\Feed\Processor`)
The core background agent responsible for:
- Fetching RSS/Atom feeds from external sources using `feed-io/feed-io`.
- Detecting and updating feed metadata (title, description, icons).
- Iterating through new feed items and sending their links to an external `readability-js-server`.
- Enriching feed items with the full-text content returned by the readability server.
- Persisting enriched items into the database.

#### 2. Feed Proxy (`App\Controller\FeedProxyController`)
The agent that serves the enriched feeds to RSS readers:
- Receives requests for specific feeds via unique hash identifiers.
- Retrieves the enriched feed items from the local database.
- Renders and returns a valid RSS/Atom feed containing the full-text content.

#### 3. Reader Interface (`App\Controller\ReaderController`)
Provides a web-based UI for reading the processed articles:
- Lists available feeds and their enriched items.
- Displays the full content of articles in a clean, readable format.
- Handles pagination and item filtering.

#### 4. Feed Management (`App\Controller\FeedCrudController`)
The administrative agent for managing the system:
- Provides a CRUD interface for adding, editing, or removing RSS feeds.
- Allows triggering manual feed refreshes.
- Manages feed configurations (URL, update frequency, etc.).

#### 5. User & Security (`App\Controller\SecurityController` & `App\Security\AppAuthenticator`)
Handles authentication and authorization:
- Manages user accounts and permissions.
- Ensures only authorized users can access the reader or management interfaces.

#### 6. CLI Commands (`App\Command\*`)
Utility agents for background tasks:
- `FeedFetchAllCommand`: Triggers the `Feed Processor` to update all active feeds.
- `FeedDeleteOldCommand`: Cleans up old feed items to manage database size.
- `UserCreateCommand`: Helper to create new users from the terminal.

# readability-rss-proxy
A self-hosted RSS proxy that will fetch articles and run them through a readability analog

## Status
Good enough for my own use. Definitely not good enough for other people to use - bit hard to install, reader mode is 
dodgy and unfinished, and a number of other UX issues.

## Requirements 
### Development
  * Docker
  * Docker Compose
  * Make
  * `libnss3-tools` (or whichever package contains `certutil` in your system)

Run `make init`. This will spin up the local environment and load up some users with some feeds ready to go. Once 
finished, open [https://rss-proxy.local:7000/](https://rss-proxy.local:7000/). There are two users, both with the same
feeds but one is admin and the other one isn't:

| role  |      username     | password |
|-------|-------------------|----------|
|admin  |admin@admin.com    |admin     |
|regular|non_admin@admin.com|non_admin |

### To actually run it somewhere
  * php 7.3+
  * PostgreSQL. MySQL and compatibles are possible, but additional work is required - at the moment only database migrations for psql are provided (you can bypass this by installing the mysql pdo extension and running `bin/console doctrine:schema:update --force` and adjusting the right environment variables, but this is not as resilient as actual migrations). Won't do unless there's demand.
  * Redis (for storing sessions) + php redis extension
  * Any webserver

Basically, replicate the environment spun up in [docker-compose.yaml](docker-compose.yaml).
  
At the moment there's a full build system based in Make, docker and deployments to kubernetes. I'll be making these
images available publicly via docker hub at some point.

## Misc notes

 * Disable your ad blocker while vising the admin panel. There are no ads or tracking you should 
 worry about, and sometimes they block feed icons (ublock in firefox blocks the bbc logo from 
 their feeds, who knows why).

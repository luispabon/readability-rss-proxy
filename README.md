# readability-rss-proxy
A self-hosted RSS proxy that will fetch articles and run them through a readability analog

## Status
Good enough for my own use. Definitely not good enough for other people to use (missing nav on the
admin panel, password changes, etc).

## Requirements 
### Development
  * Docker
  * Docker Compose
  * Make
  * `libnss3-tools` (or whichever package contains `certutil` in your system)

### To actually run it somewhere
  * php 7.3+
  * PostgreSQL or MySQL (MariaDB, Aurora etc) + the matching pdo extension
  * Redis (for storing sessions) + php redis extension
  * Any webserver
  
### How to deploy
At the moment there's a full build system based in Make, docker and deployments to kubernetes.

At some point I might provide with public images. To be fair, I've structured the app to run 
on something like kubernetes.

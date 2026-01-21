# VAIIcko — Installation (Docker)

This repository contains a small MVC teaching framework and a sample application.
This README replaces the previous generic description with a concise, actionable Docker-based installation guide.

## Prerequisites
- Docker (Desktop) installed and running
- docker-compose (bundled with Docker Desktop on Windows)

## What this Docker setup provides
- Apache + PHP 8.3 (web) serving from the project's `public/` directory
- MariaDB database initialized from the `docker/sql/` folder
- Adminer (web-based database admin) to inspect the database

## Ports (mapped to localhost)
- Web site: http://localhost/ (Apache, port 80)
- Adminer: http://localhost:8080/ (Adminer, port 8080)
- MariaDB: 127.0.0.1:3306 (for local DB clients)

## Quick start (recommended)
1. Create the environment file used by Docker Compose.
   The compose file expects environment variables for the database. Create a file named `.env` inside the `docker/` directory (next to `docker-compose.yml`). Example content:

```text
# filepath: docker/.env
MARIADB_ROOT_PASSWORD=rootpass
MARIADB_DATABASE=vaiicko_db
MARIADB_USER=vaiicko_user
MARIADB_PASSWORD=dtb456
```

- The values above match the defaults used in the application configuration (`App/Configuration.php`). You can change them but if you do, update `App/Configuration.php` accordingly or set matching values in your runtime environment.
- Choose a stronger `MARIADB_ROOT_PASSWORD` for production or shared environments.

2. From the project root, start the services with docker-compose:

```cmd
cd docker
docker-compose up -d --build
```

This will pull the configured images, mount the project into the web container, initialize the database using the SQL files under `docker/sql/`, and start Adminer and the web server.

3. Visit the app in your browser
- Application: http://localhost/
- Adminer:  http://localhost:8080/

### Adminer login example
- System: MySQL
- Server: db
- Username: vaiicko_user
- Password: dtb456
- Database: vaiicko_db

## Notes and troubleshooting
- If ports 80 or 3306 are already in use on your machine, modify the `ports:` mapping in `docker/docker-compose.yml` or stop the conflicting service.
- The compose file maps the project root into the web container. Any PHP changes in the project will be visible immediately inside the container.
- Database initialization: SQL scripts in `docker/sql/` are mounted into the MariaDB image and executed on first container startup. If you need to re-run them, remove the database container and its volume (be careful — you will lose data):

```cmd
cd docker
docker-compose down
REM remove any created data files or named volumes if applicable
docker-compose up -d --build
```

## Stopping the project

```cmd
cd docker
docker-compose down
```

## Useful commands
- View logs:

```cmd
cd docker
docker-compose logs -f web
```

- Execute a shell in the web container:

```cmd
docker-compose exec web bash
```

## Security reminder
- The `.env` file contains credentials. Do not commit it to public repositories. Add it to `.gitignore` if you plan to store it locally.

## Further reading
- See `docker/docker-compose.yml` for service definitions and `docker/sql/` for database initialization scripts.

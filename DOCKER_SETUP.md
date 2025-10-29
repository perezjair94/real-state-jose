# Docker Setup Guide - Real Estate Management System

## Overview

This guide provides step-by-step instructions to run the Real Estate Management System using Docker. Docker provides a complete, isolated development environment with PHP, Apache, MySQL, and phpMyAdmin.

## Prerequisites

- **Docker**: [Install Docker](https://docs.docker.com/get-docker/)
- **Docker Compose**: [Install Docker Compose](https://docs.docker.com/compose/install/)
- **Git**: For cloning the repository
- At least 2GB of available disk space
- Ports 8080, 8081, and 3306 available on your machine

### Verify Installation

```bash
docker --version
docker-compose --version
```

## Quick Start

### 1. Clone or Navigate to the Project

```bash
cd /home/cyb3r0801/projects/real-state-jose
```

### 2. Create Environment File (Optional but Recommended)

```bash
cp .env.example .env
```

Or create `.env` with custom values:

```env
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=real_estate_db
MYSQL_USER=real_estate_user
MYSQL_PASSWORD=real_estate_pass
WEB_PORT=8080
DB_PORT=3306
PHPMYADMIN_PORT=8081
```

### 3. Start the Docker Containers

```bash
docker-compose up -d
```

This command will:
- Build the PHP/Apache image
- Start the web server (Port 8080)
- Start MySQL database (Port 3306)
- Start phpMyAdmin (Port 8081)
- Initialize the database with schema and sample data

**First time startup takes 2-3 minutes** as Docker downloads images and initializes the database.

### 4. Wait for Database Initialization

Check the database status:

```bash
docker-compose ps
```

Wait until the database container shows a healthy status:

```bash
docker-compose logs database
```

Look for: `[System] TIMESTAMP [MySQL] ready for connections`

### 5. Access the Application

Once all containers are running and healthy:

- **Application**: [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin**: [http://localhost:8081](http://localhost:8081)

## Default Credentials

### Application Login

**Admin Account:**
- Username: `admin`
- Password: `admin123`
- Email: `admin@inmobiliaria.com`

**Client Account:**
- Username: `cliente1`
- Password: `cliente123`
- Email: `cliente1@example.com`

### phpMyAdmin Access

- **Host**: `database` (or `localhost` if accessing from outside Docker)
- **Username**: `real_estate_user`
- **Password**: `real_estate_pass`
- **Database**: `real_estate_db`

### MySQL Root Access (Optional)

- **Username**: `root`
- **Password**: `rootpassword`

## Common Docker Commands

### View Running Containers

```bash
docker-compose ps
```

### View Container Logs

```bash
# All containers
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f database
docker-compose logs -f phpmyadmin
```

### Stop Containers

```bash
docker-compose stop
```

### Start Containers (After Stopping)

```bash
docker-compose start
```

### Rebuild Containers

```bash
docker-compose down
docker-compose up -d --build
```

### Remove Everything (Including Data)

```bash
docker-compose down -v
```

⚠️ **WARNING**: This deletes the database volume. Use only if you want to reset everything.

### Access Container Shell

```bash
# PHP/Web container
docker exec -it real-estate-web bash

# Database container
docker exec -it real-estate-db mysql -u root -prootpassword
```

## Development Workflow

### File Synchronization

Changes to files in your local directory are **automatically synced** to the container via the volume mount. No rebuild needed for code changes.

```bash
# Example: Edit a PHP file locally
nano modules/properties/list.php

# Changes are immediately reflected in the running application
# Just refresh http://localhost:8080
```

### Database Changes

### Access MySQL CLI

```bash
docker exec -it real-estate-db mysql -u root -prootpassword real_estate_db
```

### Run SQL Scripts

```bash
docker exec -it real-estate-db mysql -u root -prootpassword real_estate_db < database/migrations/new_migration.sql
```

### phpMyAdmin GUI

Access [http://localhost:8081](http://localhost:8081) for a web-based database management interface.

## Configuration

### Modify Database Credentials

Edit `docker-compose.yml` and update the `database` service environment variables:

```yaml
environment:
  MYSQL_ROOT_PASSWORD: your_password
  MYSQL_USER: your_user
  MYSQL_PASSWORD: your_password
  MYSQL_DATABASE: your_database
```

Then rebuild:

```bash
docker-compose down -v
docker-compose up -d
```

### Modify PHP Configuration

Edit `config/php.ini` to adjust:
- Memory limits
- Execution timeouts
- Upload file size
- Session settings
- Timezone

Changes take effect after container restart:

```bash
docker-compose restart web
```

### Modify Apache Configuration

Edit `config/apache.conf` for:
- Virtual host settings
- Rewrite rules
- Security headers
- Compression settings

Restart Apache:

```bash
docker-compose restart web
```

## Troubleshooting

### Port Already in Use

If you get "port is already in use" error:

```bash
# Option 1: Use different ports in docker-compose.yml
# Change:
#   ports:
#     - "9090:80"  # Use 9090 instead of 8080

# Option 2: Stop the service using the port
sudo lsof -i :8080
sudo kill -9 <PID>
```

### Database Connection Refused

**Error**: "SQLSTATE[HY000] [2002] Connection refused"

**Solution**:
1. Check if database container is running: `docker-compose ps`
2. Wait for database initialization: `docker-compose logs database`
3. Verify environment variables in `docker-compose.yml` match application config
4. Restart containers: `docker-compose restart`

### Cannot Access Application

**Error**: "Connection refused" at http://localhost:8080

**Solutions**:
1. Check container status: `docker-compose ps`
2. View web server logs: `docker-compose logs web`
3. Verify ports: `docker-compose ps | grep real-estate-web`
4. Restart: `docker-compose restart web`

### Database Won't Initialize

**Error**: Tables not found in phpMyAdmin

**Solutions**:
1. Check database logs: `docker-compose logs database`
2. Verify SQL files exist:
   - `database/schema.sql`
   - `database/usuarios_schema.sql`
   - `database/seed.sql`
3. Manually initialize:
   ```bash
   docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/schema.sql
   docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/usuarios_schema.sql
   docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/seed.sql
   ```

### Permission Denied Errors

**Error**: "Permission denied" when accessing files

**Solution**:
```bash
docker exec real-estate-web chown -R www-data:www-data /var/www/html
docker exec real-estate-web chmod -R 755 /var/www/html
```

### Memory Issues

**Error**: "Killed" or out of memory

**Solutions**:
1. Increase Docker memory allocation in Docker Desktop settings
2. Reduce PHP memory in `config/php.ini`: `memory_limit = 128M`
3. Check resource usage: `docker stats`

## Production Considerations

### Security

1. **Change Default Credentials** in `docker-compose.yml`
2. **Use .env File** for sensitive data (don't commit to git)
3. **Enable HTTPS** with SSL certificates
4. **Set DEBUG_MODE = false** in `config/constants.php`
5. **Use Strong Passwords** for database and admin accounts

### Database Backups

```bash
# Backup database
docker exec real-estate-db mysqldump -u root -prootpassword real_estate_db > backup.sql

# Restore from backup
docker exec -i real-estate-db mysql -u root -prootpassword real_estate_db < backup.sql
```

### Scaling

For multiple web containers with load balancing, create a `docker-compose.prod.yml` with Nginx reverse proxy.

## Performance Optimization

### Enable Caching

```bash
# Inside container
docker exec real-estate-web mkdir -p cache logs
docker exec real-estate-web chown www-data:www-data cache logs
```

### Monitor Resources

```bash
docker stats real-estate-web real-estate-db
```

### View Performance Logs

```bash
docker exec real-estate-web tail -f logs/*.log
```

## Advanced Usage

### Custom MySQL Configuration

Create `config/mysql.conf`:

```ini
[mysqld]
max_connections=200
default_storage_engine=InnoDB
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
```

Then mount it in `docker-compose.yml`:

```yaml
volumes:
  - ./config/mysql.conf:/etc/mysql/conf.d/custom.cnf
```

### Enable XDebug for Debugging

```bash
# Add to Dockerfile
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Add to php.ini
xdebug.mode=develop,debug
xdebug.client_host=host.docker.internal
```

### Multiple Environments

```bash
# Development
docker-compose -f docker-compose.yml up -d

# Production (with different compose file)
docker-compose -f docker-compose.prod.yml up -d
```

## Additional Resources

- **Docker Documentation**: https://docs.docker.com/
- **Docker Compose Reference**: https://docs.docker.com/compose/compose-file/
- **MySQL Docker Image**: https://hub.docker.com/_/mysql
- **PHP Docker Image**: https://hub.docker.com/_/php
- **phpMyAdmin**: https://hub.docker.com/r/phpmyadmin/phpmyadmin

## Support

For issues or questions:

1. Check Docker logs: `docker-compose logs`
2. Verify configuration files
3. Review this guide's troubleshooting section
4. Check the main project documentation

## Version Information

- **PHP**: 8.2 with Apache
- **MySQL**: 8.0
- **phpMyAdmin**: Latest
- **Docker Compose**: Version 3.8

---

Last Updated: October 2024

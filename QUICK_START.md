# Quick Start Guide - Docker Setup

## 1️⃣ Prerequisites

Make sure you have Docker installed:
```bash
docker --version
docker-compose --version
```

## 2️⃣ Start the Application (3 steps)

### Option A: Using the Helper Script (Recommended)

```bash
cd /home/cyb3r0801/projects/real-state-jose

# Make it executable (first time only)
chmod +x docker-helpers.sh

# Start everything
./docker-helpers.sh start
```

### Option B: Using Docker Compose Directly

```bash
cd /home/cyb3r0801/projects/real-state-jose

# Start containers
docker-compose up -d

# Wait 30 seconds for services to initialize
sleep 30

# Check status
docker-compose ps
```

## 3️⃣ Access the Application

Once all containers are running:

| Service | URL | Credentials |
|---------|-----|-------------|
| **Application** | http://localhost:8080 | admin / admin123 |
| **phpMyAdmin** | http://localhost:8081 | user: real_estate_user / pass: real_estate_pass |
| **MySQL** | localhost:3306 | user: real_estate_user / pass: real_estate_pass |

## ⚡ Quick Commands

```bash
# View logs
./docker-helpers.sh logs

# Check status
./docker-helpers.sh status

# Access web container shell
./docker-helpers.sh shell-web

# Access MySQL
./docker-helpers.sh shell-db

# Backup database
./docker-helpers.sh backup-db

# Stop containers
./docker-helpers.sh stop

# Restart
./docker-helpers.sh restart
```

## 🐛 Troubleshooting

### "Connection refused" error?

Wait a bit longer (database takes time to initialize):
```bash
docker-compose logs database
```

### "Port already in use"?

Edit `docker-compose.yml` and change ports:
```yaml
web:
  ports:
    - "9090:80"  # Changed from 8080
```

### Database empty?

Manually initialize:
```bash
docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/schema.sql
docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/usuarios_schema.sql
docker exec real-estate-db mysql -u root -prootpassword real_estate_db < database/seed.sql
```

## 📚 More Help

- Full setup guide: `DOCKER_SETUP.md`
- Project documentation: `CLAUDE.md`

## 🛑 Stop Everything

```bash
./docker-helpers.sh stop
```

---

**That's it! Your application is now running with Docker.** 🎉

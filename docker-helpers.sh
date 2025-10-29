#!/bin/bash

# Docker Helper Scripts - Real Estate Management System
# Usage: source docker-helpers.sh or run directly

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Docker commands
docker_start() {
    print_header "Starting Docker Containers"
    docker-compose up -d
    print_success "Containers started"
    print_info "Waiting for services to be ready (30 seconds)..."
    sleep 30
    docker_status
}

docker_stop() {
    print_header "Stopping Docker Containers"
    docker-compose stop
    print_success "Containers stopped"
}

docker_restart() {
    print_header "Restarting Docker Containers"
    docker-compose restart
    print_success "Containers restarted"
}

docker_logs() {
    print_header "Docker Logs"
    docker-compose logs -f "$@"
}

docker_status() {
    print_header "Container Status"
    docker-compose ps
}

docker_build() {
    print_header "Building Docker Image"
    docker-compose build --no-cache
    print_success "Image built successfully"
}

docker_clean() {
    print_header "Cleaning Docker Resources"
    read -p "This will remove all containers and volumes. Are you sure? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose down -v
        print_success "Cleaned up all resources"
    else
        print_info "Cleanup cancelled"
    fi
}

docker_shell_web() {
    print_header "Accessing Web Container Shell"
    docker exec -it real-estate-web bash
}

docker_shell_db() {
    print_header "Accessing MySQL Shell"
    docker exec -it real-estate-db mysql -u root -prootpassword real_estate_db
}

docker_backup_db() {
    print_header "Backing Up Database"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local filename="backup_${timestamp}.sql"
    docker exec real-estate-db mysqldump -u root -prootpassword real_estate_db > "${filename}"
    print_success "Database backed up to ${filename}"
}

docker_restore_db() {
    if [ -z "$1" ]; then
        print_error "Usage: docker_restore_db <backup.sql>"
        return 1
    fi

    if [ ! -f "$1" ]; then
        print_error "Backup file not found: $1"
        return 1
    fi

    print_header "Restoring Database from $1"
    docker exec -i real-estate-db mysql -u root -prootpassword real_estate_db < "$1"
    print_success "Database restored from $1"
}

docker_logs_web() {
    print_header "Web Server Logs"
    docker-compose logs -f web
}

docker_logs_db() {
    print_header "Database Logs"
    docker-compose logs -f database
}

docker_reset() {
    print_header "Resetting Everything"
    print_info "This will remove all containers, volumes, and reset the application"
    read -p "Are you sure? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose down -v
        print_info "Rebuilding containers..."
        docker-compose build --no-cache
        docker_start
        print_success "Application reset complete"
    else
        print_info "Reset cancelled"
    fi
}

docker_stats() {
    print_header "Container Resource Usage"
    docker stats --no-stream real-estate-web real-estate-db real-estate-phpmyadmin
}

# Show help menu
show_help() {
    cat << EOF
${BLUE}Real Estate Management System - Docker Helper${NC}

Usage: ./docker-helpers.sh [command] or source docker-helpers.sh && [command]

Commands:
  start              Start all Docker containers
  stop               Stop all containers
  restart            Restart all containers
  logs [service]     View logs (web, database, phpmyadmin, or all)
  logs-web           View web server logs
  logs-db            View database logs
  status             Show container status
  build              Build Docker image
  shell-web          Access web container bash shell
  shell-db           Access MySQL shell
  backup-db          Create database backup
  restore-db FILE    Restore database from backup
  stats              Show container resource usage
  clean              Remove all containers and volumes (DESTRUCTIVE)
  reset              Full reset of application (DESTRUCTIVE)
  help               Show this help message

Examples:
  ./docker-helpers.sh start
  ./docker-helpers.sh logs
  ./docker-helpers.sh backup-db
  ./docker-helpers.sh restore-db backup_20240101_120000.sql

URLs:
  Application:  http://localhost:8080
  phpMyAdmin:   http://localhost:8081
  MySQL:        localhost:3306

Credentials:
  Admin:    admin / admin123
  Client:   cliente1 / cliente123
  Database: real_estate_user / real_estate_pass

EOF
}

# Main command handler
main() {
    case "${1:-help}" in
        start)
            docker_start
            ;;
        stop)
            docker_stop
            ;;
        restart)
            docker_restart
            ;;
        logs)
            docker_logs "${2:-.}"
            ;;
        logs-web)
            docker_logs_web
            ;;
        logs-db)
            docker_logs_db
            ;;
        status)
            docker_status
            ;;
        build)
            docker_build
            ;;
        shell-web)
            docker_shell_web
            ;;
        shell-db)
            docker_shell_db
            ;;
        backup-db)
            docker_backup_db
            ;;
        restore-db)
            docker_restore_db "$2"
            ;;
        stats)
            docker_stats
            ;;
        clean)
            docker_clean
            ;;
        reset)
            docker_reset
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            show_help
            exit 1
            ;;
    esac
}

# If sourced, don't run main; if executed directly, run it
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi

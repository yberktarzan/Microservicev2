.PHONY: help build run test clean docker-build docker-up docker-down migrate

# Variables
APP_NAME=auth-service
DOCKER_IMAGE=auth-service:latest
MAIN_PATH=./cmd/api/main.go

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build the application
	@echo "Building $(APP_NAME)..."
	@go build -o bin/$(APP_NAME) $(MAIN_PATH)
	@echo "✅ Build complete: bin/$(APP_NAME)"

run: ## Run the application
	@echo "Running $(APP_NAME)..."
	@go run $(MAIN_PATH)

test: ## Run tests
	@echo "Running tests..."
	@go test -v ./...

test-coverage: ## Run tests with coverage
	@echo "Running tests with coverage..."
	@go test -v -coverprofile=coverage.out ./...
	@go tool cover -html=coverage.out -o coverage.html
	@echo "✅ Coverage report: coverage.html"

clean: ## Clean build artifacts
	@echo "Cleaning..."
	@rm -rf bin/
	@rm -f coverage.out coverage.html
	@echo "✅ Clean complete"

deps: ## Download dependencies
	@echo "Downloading dependencies..."
	@go mod download
	@go mod tidy
	@echo "✅ Dependencies downloaded"

docker-build: ## Build Docker image
	@echo "Building Docker image..."
	@docker build -t $(DOCKER_IMAGE) .
	@echo "✅ Docker image built: $(DOCKER_IMAGE)"

docker-up: ## Start Docker Compose services
	@echo "Starting services..."
	@docker-compose up -d
	@echo "✅ Services started"
	@docker-compose ps

docker-down: ## Stop Docker Compose services
	@echo "Stopping services..."
	@docker-compose down
	@echo "✅ Services stopped"

docker-logs: ## View Docker Compose logs
	@docker-compose logs -f auth-service

docker-clean: ## Clean Docker resources
	@echo "Cleaning Docker resources..."
	@docker-compose down -v
	@docker rmi $(DOCKER_IMAGE) 2>/dev/null || true
	@echo "✅ Docker resources cleaned"

migrate-up: ## Run database migrations (up)
	@echo "Running migrations..."
	@go run $(MAIN_PATH)

lint: ## Run linter
	@echo "Running linter..."
	@golangci-lint run ./...

format: ## Format code
	@echo "Formatting code..."
	@go fmt ./...
	@goimports -w .
	@echo "✅ Code formatted"

dev: ## Run in development mode with live reload
	@echo "Starting development mode..."
	@air || go run $(MAIN_PATH)

.DEFAULT_GOAL := help

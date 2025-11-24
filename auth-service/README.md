# 🔐 Auth Service - Enterprise Microservice

Go ile geliştirilmiş, Clean Architecture prensiplerine uygun, production-ready authentication microservice.

## 🏗️ Architecture

Bu proje **Clean Architecture** (Hexagonal Architecture) prensiplerini takip eder:

```
auth-service/
├── cmd/
│   └── api/
│       └── main.go              # Application entry point
├── internal/
│   ├── domain/                  # Domain Layer (Entities & Repository Interfaces)
│   │   ├── user.go
│   │   └── repository.go
│   ├── application/             # Application Layer (Use Cases & DTOs)
│   │   ├── dto/
│   │   │   └── auth_dto.go
│   │   └── usecase/
│   │       └── auth_usecase.go
│   ├── infrastructure/          # Infrastructure Layer (Repository Implementations)
│   │   └── repository/
│   │       ├── user_repository.go
│   │       └── refresh_token_repository.go
│   └── presentation/            # Presentation Layer (HTTP Handlers & Middleware)
│       └── http/
│           ├── handler/
│           │   └── auth_handler.go
│           └── middleware/
│               └── auth_middleware.go
├── pkg/                         # Shared Packages
│   ├── database/
│   │   └── postgres.go
│   └── security/
│       ├── jwt.go
│       └── password.go
├── config/                      # Configuration
│   └── config.go
├── .env.example                 # Environment variables example
├── Dockerfile                   # Docker image
├── docker-compose.yml           # Docker compose for local development
└── go.mod                       # Go modules
```

## ✨ Features

- ✅ **Clean Architecture** - Domain, Application, Infrastructure, Presentation layers
- ✅ **JWT Authentication** - Access & Refresh tokens
- ✅ **Password Security** - bcrypt hashing
- ✅ **PostgreSQL** - GORM ORM
- ✅ **Redis** - Token caching (optional)
- ✅ **Input Validation** - Gin validator
- ✅ **Error Handling** - Centralized error responses
- ✅ **CORS** - Configurable CORS policy
- ✅ **Graceful Shutdown** - Proper resource cleanup
- ✅ **Docker Support** - Multi-stage Dockerfile
- ✅ **Health Checks** - `/health` endpoint
- ✅ **Swagger Docs** - API documentation

## 🚀 Quick Start

### Prerequisites

- Go 1.23+
- PostgreSQL 16+
- Redis 7+ (optional)
- Docker & Docker Compose (optional)

### Local Development

1. **Clone and setup:**

```bash
cd auth-service
cp .env.example .env
# Edit .env with your configuration
```

2. **Install dependencies:**

```bash
go mod download
```

3. **Start PostgreSQL (if not running):**

```bash
docker run --name auth-postgres -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=auth_db -p 5432:5432 -d postgres:16-alpine
```

4. **Run the service:**

```bash
go run cmd/api/main.go
```

Service will start on `http://localhost:5004`

### Docker Compose

```bash
# Start all services (PostgreSQL + Redis + Auth Service)
docker-compose up -d

# View logs
docker-compose logs -f auth-service

# Stop all services
docker-compose down
```

## 📚 API Endpoints

### Public Endpoints

| Method | Endpoint             | Description          |
| ------ | -------------------- | -------------------- |
| POST   | `/api/auth/register` | Register new user    |
| POST   | `/api/auth/login`    | User login           |
| POST   | `/api/auth/refresh`  | Refresh access token |
| GET    | `/health`            | Health check         |

### Protected Endpoints (Requires JWT)

| Method | Endpoint           | Description           |
| ------ | ------------------ | --------------------- |
| POST   | `/api/auth/logout` | User logout           |
| GET    | `/api/auth/me`     | Get current user info |

## 🔧 API Examples

### Register

```bash
curl -X POST http://localhost:5004/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "username": "john_doe",
    "password": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

**Response:**

```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "zYxWvuT...",
  "token_type": "Bearer",
  "expires_in": 900,
  "user": {
    "id": "uuid",
    "email": "john@example.com",
    "username": "john_doe",
    "first_name": "John",
    "last_name": "Doe",
    "is_active": true
  }
}
```

### Login

```bash
curl -X POST http://localhost:5004/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email_or_username": "john@example.com",
    "password": "SecurePass123!"
  }'
```

### Get Current User

```bash
curl -X GET http://localhost:5004/api/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Refresh Token

```bash
curl -X POST http://localhost:5004/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "YOUR_REFRESH_TOKEN"
  }'
```

### Logout

```bash
curl -X POST http://localhost:5004/api/auth/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## ⚙️ Configuration

Environment variables (`.env`):

```bash
# Server
SERVER_PORT=5004
SERVER_HOST=0.0.0.0
GIN_MODE=debug # debug | release

# Database
DB_HOST=localhost
DB_PORT=5432
DB_USER=postgres
DB_PASSWORD=postgres
DB_NAME=auth_db
DB_SSLMODE=disable

# JWT
JWT_SECRET=your-super-secret-key
JWT_ACCESS_TOKEN_EXPIRY=15m
JWT_REFRESH_TOKEN_EXPIRY=7d

# Security
BCRYPT_COST=12
MAX_LOGIN_ATTEMPTS=5

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5000
```

## 🧪 Testing

```bash
# Run all tests
go test ./...

# Run with coverage
go test -cover ./...

# Run specific package
go test ./internal/application/usecase/...
```

## 🏗️ Clean Architecture Layers

### 1. Domain Layer (`internal/domain/`)

- **Entities**: Core business objects (`User`, `RefreshToken`)
- **Repository Interfaces**: Contracts for data access
- **Business Rules**: Domain logic and validation
- **No dependencies** on other layers

### 2. Application Layer (`internal/application/`)

- **Use Cases**: Business logic orchestration (`AuthUseCase`)
- **DTOs**: Data transfer objects
- **Application Services**: Cross-cutting concerns
- **Depends only on**: Domain layer

### 3. Infrastructure Layer (`internal/infrastructure/`)

- **Repository Implementations**: Database access
- **External Services**: Third-party integrations
- **Frameworks**: GORM, Redis, etc.
- **Depends on**: Domain layer interfaces

### 4. Presentation Layer (`internal/presentation/`)

- **HTTP Handlers**: REST API endpoints
- **Middleware**: Authentication, logging, CORS
- **Request/Response**: JSON serialization
- **Depends on**: Application & Domain layers

## 🔐 Security Features

1. **Password Hashing**: bcrypt with configurable cost
2. **JWT Tokens**:
   - Access tokens (short-lived, 15 min)
   - Refresh tokens (long-lived, 7 days)
3. **Token Revocation**: Refresh tokens stored in database
4. **Input Validation**: All requests validated
5. **CORS**: Configurable origin whitelist
6. **Rate Limiting**: (Ready for middleware integration)

## 📊 Database Schema

### Users Table

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR UNIQUE NOT NULL,
    username VARCHAR UNIQUE NOT NULL,
    password_hash VARCHAR NOT NULL,
    first_name VARCHAR,
    last_name VARCHAR,
    is_active BOOLEAN DEFAULT true,
    is_verified BOOLEAN DEFAULT false,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Refresh Tokens Table

```sql
CREATE TABLE refresh_tokens (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id),
    token VARCHAR UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_revoked BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW()
);
```

## 🚀 Production Deployment

### Build for Production

```bash
# Build binary
go build -o auth-service ./cmd/api

# Run
./auth-service
```

### Docker Production Build

```bash
# Build image
docker build -t auth-service:latest .

# Run container
docker run -d \
  --name auth-service \
  -p 5004:5004 \
  --env-file .env \
  auth-service:latest
```

### Environment Variables (Production)

- **Change** `JWT_SECRET` to a strong, random value
- **Set** `GIN_MODE=release`
- **Configure** `CORS_ALLOWED_ORIGINS` with actual frontend URLs
- **Enable** SSL for database (`DB_SSLMODE=require`)
- **Use** secure passwords for database and Redis

## 📈 Performance Tips

1. **Database Indexing**: Email and username columns are indexed
2. **Connection Pooling**: GORM manages connection pool
3. **JWT Caching**: Consider Redis for token blacklist
4. **Graceful Shutdown**: Ensures all connections are closed properly

## 🐛 Troubleshooting

### "Connection refused" to PostgreSQL

```bash
# Check if PostgreSQL is running
docker ps | grep postgres

# Check connection
psql -h localhost -U postgres -d auth_db
```

### "Invalid token" errors

- Check `JWT_SECRET` matches between requests
- Verify token hasn't expired
- Ensure `Authorization: Bearer TOKEN` format

### Port already in use

```bash
# Find process using port 5004
lsof -i :5004

# Kill process
kill -9 <PID>
```

## 📝 License

MIT License

## 👤 Author

Senior Go Microservice Architecture

---

**Status**: ✅ Production Ready  
**Version**: 1.0.0  
**Go Version**: 1.23+

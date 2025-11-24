# 🚀 API Gateway - Reverse Proxy Architecture

Enterprise-grade API Gateway microservice built with .NET 10, YARP, and modern resilience patterns.

## 📋 Overview

Bu gateway, microservice mimarisinde **merkezi giriş noktası** rolünü üstlenir. Tüm client istekleri gateway'den geçer ve ilgili microservice'lere yönlendirilir.

### ✨ Key Features

- **🔀 Reverse Proxy** - YARP ile dinamik routing
- **🛡️ Resilience Patterns** - Circuit Breaker, Retry, Timeout
- **🔐 Authentication** - JWT token validation
- **📊 Rate Limiting** - DDoS koruması ve fair usage
- **📝 Structured Logging** - Serilog ile correlation tracking
- **💚 Health Checks** - Downstream service monitoring
- **📈 Metrics** - Prometheus metrics endpoint
- **🔒 Security Headers** - OWASP best practices
- **🌐 CORS** - Cross-origin yapılandırması

## 🏗️ Architecture

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│         API GATEWAY (Port 5000)         │
│  ┌────────────────────────────────────┐ │
│  │  Middleware Pipeline:              │ │
│  │  • ExceptionHandling               │ │
│  │  • SecurityHeaders                 │ │
│  │  • CorrelationId                   │ │
│  │  • RequestLogging                  │ │
│  │  • Authentication                  │ │
│  │  • RateLimiting                    │ │
│  └────────────────────────────────────┘ │
│                                          │
│  ┌────────────────────────────────────┐ │
│  │  YARP Reverse Proxy                │ │
│  │  • Circuit Breaker                 │ │
│  │  • Retry Policy                    │ │
│  │  • Load Balancing                  │ │
│  └────────────────────────────────────┘ │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴────────┬─────────┬──────────┐
       ▼                ▼         ▼          ▼
┌─────────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│User Service │  │Order Svc │  │Product   │  │Auth Svc  │
│  Port 5001  │  │Port 5002 │  │Svc 5003  │  │Port 5004 │
└─────────────┘  └──────────┘  └──────────┘  └──────────┘
```

## 🚀 Getting Started

### Prerequisites

- .NET 10 SDK
- Docker (optional)

### Installation

1. **Restore packages:**
```bash
cd Gateway
dotnet restore
```

2. **Update configuration:**

`appsettings.json` dosyasında downstream service URL'lerini güncelleyin:

```json
{
  "ReverseProxy": {
    "Clusters": {
      "user-service-cluster": {
        "Destinations": {
          "primary": {
            "Address": "http://localhost:5001"
          }
        }
      }
    }
  }
}
```

3. **Run the gateway:**
```bash
dotnet run
```

Gateway şu adreste çalışacak: `http://localhost:5000`

### 🔍 Verify Installation

```bash
# Health check
curl http://localhost:5000/health

# Gateway info
curl http://localhost:5000/

# Metrics
curl http://localhost:5000/metrics
```

## 📚 API Documentation

Gateway çalıştıktan sonra Swagger UI'a erişebilirsiniz:

- **Swagger UI:** http://localhost:5000/swagger

## 🔧 Configuration

### JWT Authentication

`appsettings.json`:
```json
{
  "JwtConfig": {
    "Secret": "YourSuperSecretKeyThatShouldBeAtLeast32CharactersLong!",
    "Issuer": "GatewayAPI",
    "Audience": "MicroserviceClients",
    "ExpirationMinutes": 60
  }
}
```

### Rate Limiting

Fixed window rate limiter (100 request per 60 seconds):

```json
{
  "RateLimitConfig": {
    "EnableRateLimiting": true,
    "PermitLimit": 100,
    "WindowSeconds": 60,
    "QueueLimit": 10
  }
}
```

### Circuit Breaker

```json
{
  "CircuitBreakerConfig": {
    "FailureThreshold": 5,
    "SuccessThreshold": 2,
    "DurationOfBreak": "00:00:30",
    "TimeoutSeconds": 30,
    "RetryCount": 3
  }
}
```

### CORS Policy

```json
{
  "CorsConfig": {
    "EnableCors": true,
    "AllowedOrigins": ["http://localhost:3000"],
    "AllowedMethods": ["GET", "POST", "PUT", "DELETE"],
    "AllowCredentials": true
  }
}
```

## 🛣️ Routes Configuration

Gateway, path-based routing kullanır:

| Path | Downstream Service | Port | Auth Required |
|------|-------------------|------|---------------|
| `/api/users/**` | User Service | 5001 | ✅ Yes |
| `/api/orders/**` | Order Service | 5002 | ✅ Yes |
| `/api/products/**` | Product Service | 5003 | ❌ No |
| `/api/auth/**` | Auth Service | 5004 | ❌ No |

### Example Requests

```bash
# Login (no auth required)
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Get users (auth required)
curl http://localhost:5000/api/users \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get products (no auth required)
curl http://localhost:5000/api/products
```

## 💚 Health Checks

Gateway, 3 farklı health check endpoint sunar:

### `/health` - Full Health Check
Tüm downstream servislerin sağlık durumunu kontrol eder.

```bash
curl http://localhost:5000/health
```

Response:
```json
{
  "status": "Healthy",
  "totalDuration": "00:00:00.0234567",
  "entries": {
    "self": { "status": "Healthy" },
    "user-service": { "status": "Healthy" },
    "order-service": { "status": "Healthy" }
  }
}
```

### `/health/ready` - Readiness Probe
Kubernetes readiness probe için kullanılır.

### `/health/live` - Liveness Probe
Kubernetes liveness probe için kullanılır.

## 📊 Metrics & Monitoring

### Prometheus Metrics

Gateway, `/metrics` endpoint'inde Prometheus formatında metrikler expose eder:

```bash
curl http://localhost:5000/metrics
```

Metrics include:
- HTTP request duration
- Request count by status code
- Rate limit hits
- Circuit breaker state
- Active connections

### Logging

Structured logging Serilog ile:

```json
{
  "@timestamp": "2025-11-24T10:30:00.000Z",
  "@level": "Information",
  "@message": "Incoming Request: GET /api/users",
  "CorrelationId": "abc-123-def-456",
  "RemoteIP": "192.168.1.100",
  "Duration": 45
}
```

Log dosyaları: `logs/gateway-{Date}.log`

## 🔐 Security Features

### 1. Security Headers
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Content-Security-Policy`

### 2. JWT Authentication
Bearer token validation tüm protected route'larda.

### 3. Rate Limiting
IP-based rate limiting (100 req/min default).

### 4. CORS
Configurable CORS policy.

### 5. HTTPS Redirect
Automatic HTTP to HTTPS redirection.

## 🐳 Docker

### Build Image

```bash
docker build -t gateway-api:latest .
```

### Run Container

```bash
docker run -d \
  --name gateway \
  -p 5000:8080 \
  -e ASPNETCORE_ENVIRONMENT=Production \
  gateway-api:latest
```

### Docker Compose

```yaml
version: '3.8'
services:
  gateway:
    image: gateway-api:latest
    ports:
      - "5000:8080"
    environment:
      - ASPNETCORE_ENVIRONMENT=Production
    depends_on:
      - user-service
      - order-service
```

## 🧪 Testing

### Manual Testing

```bash
# 1. Test health
curl http://localhost:5000/health

# 2. Test rate limiting
for i in {1..150}; do curl http://localhost:5000/api/products; done

# 3. Test authentication
curl -H "Authorization: Bearer invalid_token" \
  http://localhost:5000/api/users

# 4. Test CORS
curl -H "Origin: http://unauthorized-origin.com" \
  http://localhost:5000/api/products
```

## 📖 Project Structure

```
Gateway/
├── Config/                      # Configuration models
│   ├── CircuitBreakerConfig.cs
│   ├── CorsConfig.cs
│   ├── JwtConfig.cs
│   └── RateLimitConfig.cs
├── Extensions/                  # Service extensions
│   ├── ApplicationBuilderExtensions.cs
│   └── ServiceCollectionExtensions.cs
├── HealthChecks/               # Custom health checks
│   └── DownstreamServiceHealthCheck.cs
├── Middleware/                 # Custom middleware
│   ├── CorrelationIdMiddleware.cs
│   ├── ExceptionHandlingMiddleware.cs
│   ├── RequestLoggingMiddleware.cs
│   └── SecurityHeadersMiddleware.cs
├── Program.cs                  # Application entry point
├── appsettings.json           # Configuration
├── Gateway.csproj             # Project file
└── Dockerfile                 # Docker image
```

## 🔄 Adding New Microservice

Yeni bir microservice eklemek için:

1. **appsettings.json'a route ekle:**

```json
{
  "ReverseProxy": {
    "Routes": {
      "payment-service-route": {
        "ClusterId": "payment-service-cluster",
        "Match": {
          "Path": "/api/payments/{**catch-all}"
        }
      }
    },
    "Clusters": {
      "payment-service-cluster": {
        "Destinations": {
          "primary": {
            "Address": "http://localhost:5005"
          }
        },
        "HealthCheck": {
          "Active": {
            "Enabled": true,
            "Interval": "00:00:30",
            "Path": "/health"
          }
        }
      }
    }
  }
}
```

2. **Health check ekle (optional):**

```csharp
builder.Services.AddHealthChecks()
    .AddCheck<DownstreamServiceHealthCheck>("payment-service");
```

3. **Gateway'i restart et.**

## 🚨 Troubleshooting

### Problem: "Connection refused" hatası

**Çözüm:** Downstream service'lerin çalıştığından emin olun:
```bash
curl http://localhost:5001/health
```

### Problem: Rate limit aşımı

**Çözüm:** `RateLimitConfig` ayarlarını artırın veya test için disable edin.

### Problem: JWT validation error

**Çözüm:** 
- Token'ın geçerli olduğundan emin olun
- `JwtConfig.Secret` değerinin downstream service ile aynı olduğunu kontrol edin

## 📊 Performance

Recommended configuration:
- **Max connections:** 1000
- **Timeout:** 30s
- **Rate limit:** 100 req/min per IP
- **Circuit breaker:** 5 failures threshold

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📝 License

This project is licensed under the MIT License.

## 👤 Author

Senior .NET Microservices Architecture

---

**🎯 Gateway Status:** Production Ready ✅

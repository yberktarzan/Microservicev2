# 🚀 Quick Start Guide - API Gateway

## 1️⃣ Gateway'i Çalıştırma

```bash
cd /Users/berktarzan/Desktop/Microservice/Gateway
dotnet run
```

Gateway şu adreste başlayacak: **http://localhost:5000**

## 2️⃣ Gateway'i Test Etme

### Option A: Web Browser
Tarayıcıdan şu adresleri ziyaret edin:

- **Gateway Info**: http://localhost:5000
- **Health Check**: http://localhost:5000/health
- **Swagger UI**: http://localhost:5000/swagger

### Option B: Terminal Test
```bash
# Gateway bilgisi
curl http://localhost:5000/

# Health check
curl http://localhost:5000/health | jq

# Test script ile (tüm testler)
./test-gateway.sh
```

## 3️⃣ Downstream Servisleri Hazırlama

Gateway'in proxy yapabilmesi için downstream servislerin çalışıyor olması gerekir:

### Gerekli Servisler:
- **User Service** - Port 5001
- **Order Service** - Port 5002  
- **Product Service** - Port 5003
- **Auth Service** - Port 5004

### Her servis şu endpoint'i expose etmeli:
- `GET /health` - Health check endpoint

## 4️⃣ Route Test Örnekleri

### Products endpoint (Auth gerektirmez)
```bash
curl http://localhost:5000/api/products
```

### Users endpoint (Auth gerektirir)
```bash
# Önce token al
TOKEN=$(curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  | jq -r '.token')

# Token ile istek yap
curl http://localhost:5000/api/users \
  -H "Authorization: Bearer $TOKEN"
```

## 5️⃣ Configuration Değiştirme

### appsettings.json - Servis URL'lerini Güncelle
```json
{
  "ReverseProxy": {
    "Clusters": {
      "user-service-cluster": {
        "Destinations": {
          "primary": {
            "Address": "http://localhost:5001"  // ← Burası
          }
        }
      }
    }
  }
}
```

### JWT Secret Değiştirme (Production için zorunlu!)
```json
{
  "JwtConfig": {
    "Secret": "YourSuperSecretKeyThatShouldBeAtLeast32CharactersLong!"
  }
}
```

### Rate Limiting Ayarlama
```json
{
  "RateLimitConfig": {
    "EnableRateLimiting": true,
    "PermitLimit": 100,        // Dakikada 100 istek
    "WindowSeconds": 60
  }
}
```

## 6️⃣ Docker ile Çalıştırma

### Build
```bash
docker build -t gateway-api:latest .
```

### Run
```bash
docker run -d \
  --name gateway \
  -p 5000:8080 \
  gateway-api:latest
```

### Docker Compose (Tüm servisler birlikte)
```bash
docker-compose up -d
```

## 7️⃣ Logs Kontrolü

### Terminal logs
Gateway çalışırken terminal'de real-time logları göreceksiniz.

### Log dosyası
```bash
tail -f logs/gateway-$(date +%Y%m%d).log
```

## 8️⃣ Troubleshooting

### Problem: "Connection refused" hatası
**Çözüm**: Downstream servisin çalıştığını kontrol edin
```bash
curl http://localhost:5001/health
```

### Problem: Rate limit aşımı
**Çözüm**: `appsettings.json`'da `PermitLimit` değerini artırın

### Problem: JWT validation error
**Çözüm**: Token'ın geçerli ve doğru secret ile imzalandığından emin olun

## 9️⃣ Production Deployment

### Checklist ✅
- [ ] JWT Secret değiştir (32+ karakter)
- [ ] CORS AllowedOrigins ayarla
- [ ] Rate limiting limitlerini belirle
- [ ] HTTPS sertifikası ekle
- [ ] Log retention policy belirle
- [ ] Downstream service URL'leri ayarla
- [ ] Health check interval'lerini optimize et

### Environment Variables
```bash
export ASPNETCORE_ENVIRONMENT=Production
export ASPNETCORE_URLS=http://+:8080
```

## 🔟 Monitoring

### Prometheus Metrics
```bash
curl http://localhost:5000/metrics
```

### Grafana Dashboard
Import edilebilecek metrics:
- `http_requests_total`
- `http_request_duration_seconds`
- `rate_limit_hits_total`

## 📚 Daha Fazla Bilgi

- **README.md** - Kapsamlı dokümantasyon
- **ARCHITECTURE.md** - Mimari detayları
- **Swagger** - http://localhost:5000/swagger

---

## 🎯 Hızlı Komutlar

```bash
# Build
dotnet build

# Run
dotnet run

# Restore packages
dotnet restore

# Test
./test-gateway.sh

# Docker build
docker build -t gateway-api .

# Docker run
docker run -p 5000:8080 gateway-api

# Logs
tail -f logs/gateway-*.log
```

---

**Ready to go!** 🚀 Gateway başarıyla kuruldu ve çalışmaya hazır.

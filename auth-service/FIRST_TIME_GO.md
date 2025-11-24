# 🎓 Auth Service - Go İle İlk Adım

## 📝 Sorular ve Cevaplar

### 1. ❓ "Go'yu ilk defa kullanacağım, her şeyi yorum satırlarıyla anlat"

✅ **TAMAMLANDI!** Tüm önemli dosyalara detaylı Türkçe yorumlar eklendi:

- ✅ `internal/application/usecase/auth_usecase.go` - İş mantığı (Register, Login, Refresh, Logout)
- ✅ `pkg/security/jwt.go` - JWT token nasıl oluşturulur ve doğrulanır
- ✅ `cmd/api/main.go` - Uygulama nasıl başlar, Dependency Injection nasıl yapılır

📚 **Bonus:** `GO_LEARNING.md` dosyası oluşturuldu!

- 12 bölüm, 400+ satır detaylı Go öğretim materyali
- Her kavram kod örnekleri ile açıklanmış
- Auth Service'teki gerçek kod parçaları kullanılmış

### 2. ❓ "Chi diye bir şey öneriyor ChatGPT, bu konuda ne düşünüyorsun?"

**Benim önerim: GIN'de kal! 🎯**

#### Gin vs Chi Karşılaştırması:

| Özellik                 | Gin (Kullandığımız)        | Chi                    |
| ----------------------- | -------------------------- | ---------------------- |
| **Hız**                 | ⚡ En hızlı (benchmark 1.) | ⚡ Hızlı               |
| **Built-in Validation** | ✅ Otomatik (struct tags)  | ❌ Manuel              |
| **JSON Binding**        | ✅ Otomatik parse          | ❌ Manuel              |
| **Topluluk**            | 🌟 77k stars               | 🌟 18k stars           |
| **Dokümantasyon**       | 📚 Çok zengin              | 📚 İyi                 |
| **Öğrenme Eğrisi**      | 📈 Kolay                   | 📈 Orta                |
| **stdlib Uyumluluğu**   | 🔧 Wrapper                 | ✅ Net/http compatible |

#### Gin'in Avantajları (Senin İçin):

```go
// GIN - Otomatik validation, JSON parsing ✅
type RegisterRequest struct {
    Email    string `json:"email" binding:"required,email"`
    Password string `json:"password" binding:"required,min=8"`
}

func Register(c *gin.Context) {
    var req RegisterRequest
    if err := c.ShouldBindJSON(&req); err != nil {
        c.JSON(400, gin.H{"error": err.Error()})
        return
    }
    // req kullanıma hazır, validation yapılmış!
}

// CHI - Manuel işlem ❌
func Register(w http.ResponseWriter, r *http.Request) {
    var req RegisterRequest

    // 1. Body'yi oku
    body, _ := io.ReadAll(r.Body)

    // 2. JSON parse et
    json.Unmarshal(body, &req)

    // 3. Manuel validation
    if req.Email == "" {
        w.WriteHeader(400)
        json.NewEncoder(w).Encode(map[string]string{"error": "email required"})
        return
    }
    // ... 10 satır daha validation
}
```

#### Sonuç:

🎯 **İlk Go projen için GIN mükemmel seçim:**

- Daha az kod yaz
- Otomatik validation
- Zengin middleware ecosystem
- Daha fazla kaynak ve örnek

🔮 **İleriki projelerde Chi dene:**

- stdlib'e daha yakın
- Daha minimalist
- Microservice'lerde popüler

---

## 🚀 Şimdi Ne Yapmalıyım?

### Adım 1: Go'yu Öğren (2-3 saat)

```bash
# GO_LEARNING.md dosyasını oku
cat GO_LEARNING.md

# Özellikle bu bölümlere odaklan:
# - Pointer'lar (çok önemli!)
# - Error Handling
# - Interface'ler
# - Context
```

### Adım 2: Kodu Çalıştır

```bash
cd auth-service

# Bağımlılıkları indir
go mod tidy

# Docker ile başlat
docker-compose up -d

# Logları izle
docker-compose logs -f auth-service

# veya Makefile ile
make deps        # Dependencies
make docker-up   # Start
make docker-logs # Logs
```

### Adım 3: Test Et

```bash
# Health check
curl http://localhost:5004/health

# Register
curl -X POST http://localhost:5004/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "username": "john_doe",
    "password": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe"
  }'

# Login
curl -X POST http://localhost:5004/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email_or_username": "john@example.com",
    "password": "SecurePass123!"
  }'
```

### Adım 4: Kodu İncele

**Okuma sırası:**

1. `cmd/api/main.go` - Uygulama nasıl başlıyor
2. `internal/domain/` - Entity'ler (User, RefreshToken)
3. `internal/application/usecase/` - İş mantığı
4. `pkg/security/jwt.go` - JWT nasıl çalışıyor
5. `internal/presentation/http/handler/` - HTTP endpoint'ler

---

## 📚 Oluşturulan Dosyalar

### Kod Dosyaları (15+ dosya)

```
auth-service/
├── cmd/api/main.go                          ✅ Detaylı yorumlu
├── internal/
│   ├── domain/
│   │   ├── user.go
│   │   └── repository.go
│   ├── application/
│   │   ├── dto/auth_dto.go
│   │   └── usecase/auth_usecase.go         ✅ Detaylı yorumlu
│   ├── infrastructure/repository/
│   │   ├── user_repository.go
│   │   └── refresh_token_repository.go
│   └── presentation/http/
│       ├── handler/auth_handler.go
│       └── middleware/auth_middleware.go
├── pkg/
│   ├── security/
│   │   ├── jwt.go                          ✅ Detaylı yorumlu
│   │   └── password.go
│   └── database/postgres.go
└── config/config.go
```

### Dokümantasyon

- ✅ `README.md` - API dokümantasyonu, kurulum, örnekler
- ✅ `GO_LEARNING.md` - **YENİ!** Kapsamlı Go öğretim rehberi
- ✅ `Makefile` - Build, test, docker komutları
- ✅ `docker-compose.yml` - PostgreSQL + Redis + Auth Service

---

## 🎯 Go Öğrenme Yol Haritası

### Haftaya Göre Plan:

**Hafta 1: Temel Go**

- ✅ Syntax, types, functions
- ✅ Pointer'lar, struct'lar
- ✅ Error handling
- 📖 Kaynak: `GO_LEARNING.md` bölüm 1-5

**Hafta 2: İleri Go**

- ✅ Interface'ler
- ✅ Goroutine & Channel
- ✅ Context
- 📖 Kaynak: `GO_LEARNING.md` bölüm 6-9

**Hafta 3: Web & Database**

- ✅ Gin framework
- ✅ GORM ORM
- ✅ JWT authentication
- 📖 Kaynak: `GO_LEARNING.md` bölüm 10-12 + Auth Service kodu

**Hafta 4: Projeler**

- 🚀 User Service (port 5001)
- 🚀 Order Service (port 5002)
- 🚀 Product Service (port 5003)

---

## 💡 Pro Tips

### Go Öğrenirken:

1. **IDE Setup**: VS Code + Go extension
2. **Auto Format**: Save yaparken otomatik `go fmt`
3. **Linting**: `golangci-lint` kullan
4. **Documentation**: `godoc` ile docs oku

### Auth Service İncelerken:

1. **Breakpoint koy**: VS Code debugger kullan
2. **Log ekle**: `log.Println()` ile debug
3. **Test yaz**: `*_test.go` dosyaları oluştur
4. **Postman kullan**: API endpoint'leri test et

---

## 🔗 Faydalı Linkler

- 📖 [Official Go Tour](https://go.dev/tour/) - İnteraktif öğrenme
- 📖 [Go by Example](https://gobyexample.com/) - Kod örnekleri
- 📖 [Effective Go](https://go.dev/doc/effective_go) - Best practices
- 📖 [Gin Documentation](https://gin-gonic.com/docs/) - Web framework
- 📖 [GORM Guide](https://gorm.io/docs/) - ORM

---

## ❓ Sorular

**Kodda anlamadığın bir yer mi var?**

- Hangi dosya, hangi satır?
- Ben detaylı açıklayayım!

**Daha fazla örnek mi istiyorsun?**

- Hangi konuda? (pointer, interface, goroutine, vs.)
- Özel örnekler yapabilirim!

**Test yazmak ister misin?**

- Unit test'ler yazalım mı?
- Integration test'ler ekleyelim mi?

---

**Başarılar! Go öğrenmek eğlenceli ve hızlı olacak! 🚀**

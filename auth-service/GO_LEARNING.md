# 🎓 Go Öğrenme Rehberi - Auth Service Üzerinden

Bu dokümanda auth-service projesinde kullanılan tüm Go kavramlarını örneklerle açıklıyorum.

## 📚 İçindekiler

1. [Temel Kavramlar](#temel-kavramlar)
2. [Paket Sistemi](#paket-sistemi)
3. [Veri Tipleri](#veri-tipleri)
4. [Pointer'lar](#pointerlar)
5. [Struct'lar](#structlar)
6. [Interface'ler](#interfaceler)
7. [Error Handling](#error-handling)
8. [Concurrency (Goroutine & Channel)](#concurrency)
9. [Context](#context)
10. [JSON & Struct Tags](#json--struct-tags)
11. [HTTP & Web](#http--web)
12. [Database & GORM](#database--gorm)

---

## 1. Temel Kavramlar

### Package Declaration

```go
package main  // Executable program
package usecase  // Library package
```

- **package main**: Çalıştırılabilir program (executable binary)
- **Diğer package'lar**: Kütüphane (import edilir)

### Import

```go
// Tek import
import "fmt"

// Çoklu import (tercih edilen)
import (
    "context"
    "errors"
    "time"

    "github.com/gin-gonic/gin"  // External package
    "auth-service/config"        // Internal package
)
```

### main() Fonksiyonu

```go
func main() {
    // Program buradan başlar
    fmt.Println("Hello, Go!")
}
```

---

## 2. Paket Sistemi

### Go Module (go.mod)

```go
module auth-service  // Module adı

go 1.23  // Go versiyonu

require (
    github.com/gin-gonic/gin v1.10.0  // Dependency
    github.com/golang-jwt/jwt/v5 v5.2.1
)
```

### Package Organization

```
auth-service/
├── cmd/              # Executable programs (main package)
│   └── api/
│       └── main.go   # package main
├── internal/         # Private packages (sadece bu proje kullanabilir)
│   ├── domain/       # package domain
│   ├── application/  # package usecase, dto
│   └── infrastructure/
├── pkg/              # Public packages (diğer projeler kullanabilir)
│   ├── security/     # package security
│   └── database/     # package database
```

### Exported vs Unexported

```go
// BÜYÜK harfle başlar = EXPORTED (public)
type User struct {
    ID    string  // Exported field
    Email string
}

func NewUser() *User {  // Exported function
    return &User{}
}

// küçük harfle başlar = unexported (private)
type internalConfig struct {  // Sadece bu package'da kullanılır
    secret string
}

func validateToken() error {  // Private function
    return nil
}
```

**Örnek:**

```go
// pkg/security/jwt.go
package security

type JWTService struct {  // EXPORTED - başka package'lar kullanabilir
    secretKey []byte      // unexported - sadece bu package'da erişilebilir
}

// EXPORTED - başka package'lar çağırabilir
func NewJWTService(secret string) *JWTService {
    return &JWTService{
        secretKey: []byte(secret),
    }
}
```

---

## 3. Veri Tipleri

### Temel Tipler

```go
// String
var name string = "John Doe"
email := "john@example.com"  // Short declaration

// Integer
var age int = 25
var count int64 = 1000000

// Float
var price float64 = 99.99

// Boolean
var isActive bool = true
var isVerified bool = false

// Byte (uint8)
var b byte = 'A'
```

### Zero Values (Varsayılan Değerler)

```go
var s string   // "" (empty string)
var i int      // 0
var b bool     // false
var p *User    // nil (pointer)
```

### Type Declaration

```go
// Type alias
type UserID string
type Email string

var id UserID = "123"
var email Email = "test@example.com"

// Cannot mix types
// id = email  // COMPILE ERROR! Farklı tipler
```

---

## 4. Pointer'lar

### Pointer Nedir?

```go
// Value type - değerin kendisi
var x int = 42
var y int = x  // x'in kopyası
y = 100        // x hala 42, y 100

// Pointer type - bellek adresi
var p *int = &x  // & = address-of operator
*p = 100         // * = dereference operator
// Şimdi x = 100 (pointer üzerinden değişti)
```

### Neden Pointer Kullanırız?

```go
// 1. MEMORY EFFICIENCY - Büyük struct'ları kopyalamamak için
type User struct {
    ID        uuid.UUID
    Email     string
    FirstName string
    LastName  string
    // ... 20 tane daha field
}

// ❌ BAD - Her çağrıda 1 KB kopyalanır
func UpdateUser(u User) {
    u.Email = "new@example.com"  // UYARI: Sadece kopya değişir!
}

// ✅ GOOD - Sadece 8 byte pointer kopyalanır
func UpdateUser(u *User) {
    u.Email = "new@example.com"  // Orijinal değişir
}

// 2. MUTABILITY - Orijinal değeri değiştirmek için
func IncrementAge(age *int) {
    *age++  // Orijinal değişkeni artır
}

var myAge int = 25
IncrementAge(&myAge)
fmt.Println(myAge)  // 26
```

### Auth Service'te Pointer Kullanımı

```go
// domain/user.go
type User struct {
    ID           uuid.UUID
    Email        string
    PasswordHash string
}

// Repository interface - pointer döner (efficient)
type UserRepository interface {
    Create(ctx context.Context, user *User) error  // Pointer input
    GetByID(ctx context.Context, id uuid.UUID) (*User, error)  // Pointer output
}

// usecase/auth_usecase.go
func (uc *AuthUseCase) Register(req *dto.RegisterRequest) (*dto.AuthResponse, error) {
    // User oluştur (pointer)
    user := &User{  // & = pointer oluştur
        Email:    req.Email,
        Username: req.Username,
    }

    // Repository'ye pointer gönder
    uc.userRepo.Create(ctx, user)  // user zaten pointer

    return response, nil
}
```

### nil Pointer

```go
var p *User  // p = nil (boş pointer)

// nil check (önemli!)
if p == nil {
    return errors.New("user not found")
}

// nil pointer'a erişim = PANIC!
// p.Email  // RUNTIME ERROR: panic
```

---

## 5. Struct'lar

### Struct Tanımlama

```go
// Struct = Class benzeri (ama inheritance yok)
type User struct {
    ID        uuid.UUID  // Field (attribute)
    Email     string
    Username  string
    IsActive  bool
    CreatedAt time.Time
}
```

### Struct Oluşturma

```go
// 1. Literal syntax (tercih edilen)
user := User{
    ID:       uuid.New(),
    Email:    "john@example.com",
    Username: "john_doe",
    IsActive: true,
}

// 2. Zero value (tüm fieldler default değerde)
var user User
// ID = 00000000-0000-0000-0000-000000000000
// Email = ""
// IsActive = false

// 3. Pointer syntax (memory efficient)
user := &User{  // Pointer döner
    Email: "john@example.com",
}
```

### Method'lar

```go
// Receiver = method'un hangi type'a ait olduğu
// (u User) = value receiver
func (u User) GetFullName() string {
    return u.FirstName + " " + u.LastName
}

// (u *User) = pointer receiver (tercih edilen)
func (u *User) Activate() {
    u.IsActive = true  // Orijinal değişir
}

// Kullanım
user := &User{FirstName: "John", LastName: "Doe"}
fmt.Println(user.GetFullName())  // "John Doe"
user.Activate()  // user.IsActive = true
```

### Embedding (Composition)

```go
// Go'da inheritance yok, embedding var
type Person struct {
    FirstName string
    LastName  string
}

type User struct {
    Person  // Embedding - Person'ın tüm fieldleri User'a gelir
    Email   string
}

user := User{
    Person: Person{
        FirstName: "John",
        LastName:  "Doe",
    },
    Email: "john@example.com",
}

// Direkt erişim (promoted fields)
fmt.Println(user.FirstName)  // "John" (user.Person.FirstName yerine)
```

### Auth Service'te Struct Örnekleri

```go
// domain/user.go
type User struct {
    ID           uuid.UUID `gorm:"type:uuid;primary_key;default:gen_random_uuid()"`
    Email        string    `gorm:"uniqueIndex;not null"`
    Username     string    `gorm:"uniqueIndex;not null"`
    PasswordHash string    `gorm:"not null"`
    IsActive     bool      `gorm:"default:true"`
    CreatedAt    time.Time `gorm:"autoCreateTime"`
    UpdatedAt    time.Time `gorm:"autoUpdateTime"`
}

// Method - User'a ait fonksiyon
func (u *User) IsValidPassword(password string) bool {
    return bcrypt.CompareHashAndPassword([]byte(u.PasswordHash), []byte(password)) == nil
}
```

---

## 6. Interface'ler

### Interface Nedir?

```go
// Interface = Method imzalarının koleksiyonu
// Bir tür interface'i "implement" etmek için tüm methodları uygulamalı

type Writer interface {
    Write(data []byte) (int, error)
}

// Bu struct Writer interface'ini implement eder (otomatik)
type FileWriter struct {
    filename string
}

func (fw *FileWriter) Write(data []byte) (int, error) {
    // File'a yaz
    return len(data), nil
}
```

### Implicit Implementation

```go
// Go'da "implements" keyword'ü yok
// Bir type tüm method'ları uyguladıysa otomatik implement eder

type Animal interface {
    Speak() string
}

type Dog struct{}

// Dog otomatik olarak Animal interface'ini implement eder
func (d Dog) Speak() string {
    return "Woof!"
}

// Kullanım
var animal Animal = Dog{}  // Interface type
fmt.Println(animal.Speak())  // "Woof!"
```

### Neden Interface Kullanırız?

```go
// 1. ABSTRACTION - Implementation detaylarını gizle
// 2. TESTABILITY - Mock'lar oluştur
// 3. FLEXIBILITY - Farklı implementasyonlar kullan

// Interface tanımla (contract)
type UserRepository interface {
    Create(ctx context.Context, user *User) error
    GetByID(ctx context.Context, id uuid.UUID) (*User, error)
}

// PostgreSQL implementasyonu
type PostgresUserRepository struct {
    db *gorm.DB
}

func (r *PostgresUserRepository) Create(ctx context.Context, user *User) error {
    return r.db.Create(user).Error
}

// MongoDB implementasyonu (aynı interface)
type MongoUserRepository struct {
    client *mongo.Client
}

func (r *MongoUserRepository) Create(ctx context.Context, user *User) error {
    // MongoDB'ye kaydet
    return nil
}

// Use Case - Interface kullanır (hangi DB olduğunu bilmez)
type AuthUseCase struct {
    userRepo UserRepository  // Interface type (not struct)
}

// Dependency Injection - İstediğimiz implementasyonu veriyoruz
func main() {
    // Production - PostgreSQL kullan
    repo := &PostgresUserRepository{db: db}

    // Test - Mock kullan
    // repo := &MockUserRepository{}

    useCase := &AuthUseCase{userRepo: repo}
}
```

### Empty Interface

```go
// interface{} veya any = Her tür (Java'daki Object gibi)
func PrintAnything(v interface{}) {
    fmt.Println(v)
}

PrintAnything("string")
PrintAnything(42)
PrintAnything(User{})

// Type assertion - interface'den concrete type'a dönüşüm
var i interface{} = "hello"
s := i.(string)  // Type assertion
fmt.Println(s)   // "hello"

// Safe type assertion
s, ok := i.(string)
if ok {
    fmt.Println("It's a string:", s)
}
```

### Auth Service'te Interface Kullanımı

```go
// domain/repository.go - Interface tanımı (contract)
package domain

type UserRepository interface {
    Create(ctx context.Context, user *User) error
    GetByID(ctx context.Context, id uuid.UUID) (*User, error)
    GetByEmail(ctx context.Context, email string) (*User, error)
    ExistsByEmail(ctx context.Context, email string) (bool, error)
}

// infrastructure/repository/user_repository.go - Implementation
package repository

type UserRepository struct {
    db *gorm.DB  // Concrete implementation (GORM)
}

// Interface'i implement eder (implicit)
func (r *UserRepository) Create(ctx context.Context, user *domain.User) error {
    return r.db.WithContext(ctx).Create(user).Error
}

// application/usecase/auth_usecase.go - Interface kullanır
type AuthUseCase struct {
    userRepo domain.UserRepository  // Interface type (DI)
}

// cmd/api/main.go - Dependency Injection
func main() {
    db := database.NewPostgresDB()

    // Concrete implementasyon oluştur
    userRepo := repository.NewUserRepository(db)

    // Interface olarak inject et
    authUseCase := usecase.NewAuthUseCase(userRepo)
}
```

**Bu pattern'in avantajları:**

1. **Testability**: Mock repository inject edebiliriz
2. **Flexibility**: PostgreSQL'den MongoDB'ye geçiş kolay
3. **Clean Architecture**: Business logic infrastructure'dan bağımsız

---

## 7. Error Handling

### Error Type

```go
// error = Go'nun built-in interface'i
type error interface {
    Error() string
}

// errors.New() - yeni error oluştur
err := errors.New("something went wrong")

// fmt.Errorf() - formatted error
err := fmt.Errorf("user %s not found", username)
```

### Error Handling Pattern

```go
// Go'da try-catch yok, error dönülür
func GetUser(id string) (*User, error) {
    user, err := db.GetUser(id)
    if err != nil {
        // Hata varsa hemen kontrol et ve handle et
        return nil, err
    }

    // Success
    return user, nil
}

// Multiple return values
func Divide(a, b float64) (float64, error) {
    if b == 0 {
        return 0, errors.New("division by zero")
    }
    return a / b, nil
}

// Kullanım
result, err := Divide(10, 2)
if err != nil {
    log.Fatal(err)  // Programı durdur
}
fmt.Println(result)  // 5
```

### Custom Errors

```go
// Custom error type
type ValidationError struct {
    Field   string
    Message string
}

func (e *ValidationError) Error() string {
    return fmt.Sprintf("%s: %s", e.Field, e.Message)
}

// Kullanım
if email == "" {
    return &ValidationError{
        Field:   "email",
        Message: "email is required",
    }
}
```

### Error Variables

```go
// Paket seviyesinde error değişkenleri
var (
    ErrUserNotFound      = errors.New("user not found")
    ErrInvalidCredentials = errors.New("invalid credentials")
    ErrUnauthorized      = errors.New("unauthorized")
)

// Kullanım
func GetUser(id string) (*User, error) {
    user := findUser(id)
    if user == nil {
        return nil, ErrUserNotFound  // Predefined error
    }
    return user, nil
}

// Error karşılaştırma
user, err := GetUser("123")
if err == ErrUserNotFound {
    // Handle not found error
}
```

### Error Wrapping (Go 1.13+)

```go
// Error'u wrap et (context ekle)
if err != nil {
    return fmt.Errorf("failed to create user: %w", err)
}

// Wrapped error'u unwrap et
originalErr := errors.Unwrap(wrappedErr)

// Error chain'de belirli bir error var mı?
if errors.Is(err, ErrUserNotFound) {
    // Handle not found
}

// Error'un belirli bir type olup olmadığını kontrol et
var validationErr *ValidationError
if errors.As(err, &validationErr) {
    fmt.Println(validationErr.Field)
}
```

### Auth Service'te Error Handling

```go
// usecase/auth_usecase.go
var (
    ErrInvalidCredentials = errors.New("invalid credentials")
    ErrUserAlreadyExists  = errors.New("user already exists")
    ErrUserNotFound       = errors.New("user not found")
)

func (uc *AuthUseCase) Login(req *dto.LoginRequest) (*dto.AuthResponse, error) {
    // Repository'den user al
    user, err := uc.userRepo.GetByEmail(ctx, req.Email)
    if err != nil {
        // Database error
        return nil, fmt.Errorf("database error: %w", err)
    }
    if user == nil {
        // Custom error
        return nil, ErrUserNotFound
    }

    // Password check
    if !uc.passwordService.ComparePassword(user.PasswordHash, req.Password) {
        return nil, ErrInvalidCredentials
    }

    // Success
    return response, nil
}

// handler/auth_handler.go
func (h *AuthHandler) Login(c *gin.Context) {
    response, err := h.useCase.Login(ctx, req)
    if err != nil {
        // Error'a göre HTTP status code belirle
        switch err {
        case usecase.ErrInvalidCredentials:
            c.JSON(401, gin.H{"error": "Invalid credentials"})
        case usecase.ErrUserNotFound:
            c.JSON(404, gin.H{"error": "User not found"})
        default:
            c.JSON(500, gin.H{"error": "Internal server error"})
        }
        return
    }

    c.JSON(200, response)
}
```

---

## 8. Concurrency (Goroutine & Channel)

### Goroutine

```go
// Goroutine = Lightweight thread (OS thread'inden 100x hafif)
func main() {
    // Normal fonksiyon çağrısı (blocking)
    doWork()

    // Goroutine (non-blocking, parallel çalışır)
    go doWork()

    // Anonymous function goroutine
    go func() {
        fmt.Println("Running in goroutine")
    }()

    // Main goroutine biterse diğerleri de durur
    time.Sleep(1 * time.Second)  // Goroutine'lerin bitmesini bekle
}

func doWork() {
    fmt.Println("Doing work...")
}
```

### Channel

```go
// Channel = Goroutine'ler arası iletişim (pipe)
ch := make(chan string)  // String channel

// Goroutine'de channel'a yaz
go func() {
    ch <- "Hello"  // Send (blocking)
}()

// Main'de channel'dan oku
msg := <-ch  // Receive (blocking)
fmt.Println(msg)  // "Hello"
```

### Buffered Channel

```go
// Unbuffered channel (buffer = 0)
ch := make(chan int)  // Sender blocker until receiver reads

// Buffered channel
ch := make(chan int, 3)  // 3 değer alabilir
ch <- 1  // Non-blocking (buffer'da yer var)
ch <- 2
ch <- 3
ch <- 4  // BLOCKING (buffer dolu)

// Read
v1 := <-ch  // 1
v2 := <-ch  // 2
```

### Select Statement

```go
// Select = Birden fazla channel'ı dinle (switch gibi)
ch1 := make(chan string)
ch2 := make(chan string)

go func() {
    time.Sleep(1 * time.Second)
    ch1 <- "from ch1"
}()

go func() {
    time.Sleep(2 * time.Second)
    ch2 <- "from ch2"
}()

// Hangisi önce gelirse onu al
select {
case msg1 := <-ch1:
    fmt.Println(msg1)  // 1 saniye sonra
case msg2 := <-ch2:
    fmt.Println(msg2)  // 2 saniye sonra
case <-time.After(500 * time.Millisecond):
    fmt.Println("timeout")  // Timeout
}
```

### Auth Service'te Goroutine Kullanımı

```go
// cmd/api/main.go
func main() {
    srv := &http.Server{
        Addr:    ":5004",
        Handler: router,
    }

    // Server'i goroutine'de başlat (non-blocking)
    go func() {
        log.Println("Starting server...")
        if err := srv.ListenAndServe(); err != nil {
            log.Fatal(err)
        }
    }()

    // Main goroutine signal bekler
    quit := make(chan os.Signal, 1)
    signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
    <-quit  // Block until signal

    log.Println("Shutting down...")
    srv.Shutdown(context.Background())
}
```

**Neden goroutine kullanıyoruz?**

- Server blocking olsaydı signal'leri yakalayamazdık
- Graceful shutdown için signal handling gerekli
- Main goroutine signal beklerken, server goroutine request'leri handle eder

---

## 9. Context

### Context Nedir?

```go
// Context = Request-scoped values, cancellation signals, deadlines

// Background context (root)
ctx := context.Background()

// With timeout
ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
defer cancel()  // Mutlaka cancel çağır (memory leak önler)

// With cancel
ctx, cancel := context.WithCancel(context.Background())
defer cancel()

// With value (request-scoped data)
ctx := context.WithValue(context.Background(), "userID", "123")
userID := ctx.Value("userID").(string)
```

### Neden Context Kullanırız?

```go
// 1. TIMEOUT - Uzun süren işlemleri iptal et
func GetUser(ctx context.Context, id string) (*User, error) {
    // Database query
    select {
    case user := <-dbQuery(id):
        return user, nil
    case <-ctx.Done():  // Timeout veya cancel
        return nil, ctx.Err()  // context.DeadlineExceeded
    }
}

// 2. CANCELLATION - İşlemi manuel iptal et
ctx, cancel := context.WithCancel(context.Background())
go func() {
    time.Sleep(5 * time.Second)
    cancel()  // 5 saniye sonra işlemi iptal et
}()

// 3. REQUEST-SCOPED VALUES - User ID, trace ID vs.
func Middleware(next http.Handler) http.Handler {
    return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
        userID := extractUserID(r)

        // Context'e user ID ekle
        ctx := context.WithValue(r.Context(), "userID", userID)

        // Yeni context'li request oluştur
        next.ServeHTTP(w, r.WithContext(ctx))
    })
}

func Handler(w http.ResponseWriter, r *http.Request) {
    // Context'ten user ID al
    userID := r.Context().Value("userID").(string)
}
```

### Auth Service'te Context Kullanımı

```go
// usecase/auth_usecase.go - Her fonksiyon context alır
func (uc *AuthUseCase) Register(ctx context.Context, req *dto.RegisterRequest) (*dto.AuthResponse, error) {
    // Repository'ye context gönder
    exists, err := uc.userRepo.ExistsByEmail(ctx, req.Email)
    if err != nil {
        return nil, err
    }

    // Context timeout kontrolü
    select {
    case <-ctx.Done():
        return nil, ctx.Err()  // Request cancelled
    default:
        // Continue
    }
}

// repository/user_repository.go - GORM'a context gönder
func (r *UserRepository) Create(ctx context.Context, user *domain.User) error {
    // GORM WithContext - timeout/cancel destekler
    return r.db.WithContext(ctx).Create(user).Error
}

// handler/auth_handler.go - Request context'ini use case'e gönder
func (h *AuthHandler) Register(c *gin.Context) {
    // Gin context'ten Go context al
    ctx := c.Request.Context()

    // Use case'e context gönder
    response, err := h.useCase.Register(ctx, req)
    if err != nil {
        c.JSON(500, gin.H{"error": err.Error()})
        return
    }

    c.JSON(200, response)
}

// middleware/auth_middleware.go - User ID'yi context'e ekle
func AuthMiddleware(jwtService *security.JWTService) gin.HandlerFunc {
    return func(c *gin.Context) {
        token := extractToken(c)
        userID, err := jwtService.ExtractUserID(token)
        if err != nil {
            c.AbortWithStatusJSON(401, gin.H{"error": "Unauthorized"})
            return
        }

        // User ID'yi context'e ekle
        ctx := context.WithValue(c.Request.Context(), "userID", userID)
        c.Request = c.Request.WithContext(ctx)

        c.Next()
    }
}
```

**Context Best Practices:**

1. Her fonksiyonun ilk parametresi `ctx context.Context` olmalı
2. Context'i struct field olarak saklama (sadece fonksiyon parametresi)
3. `defer cancel()` kullanarak memory leak önle
4. Background context sadece main/test'lerde kullan

---

## 10. JSON & Struct Tags

### Struct Tags

```go
// Struct tag = Field'lere metadata eklemek için
type User struct {
    ID        string    `json:"id" gorm:"primaryKey"`
    Email     string    `json:"email" gorm:"uniqueIndex;not null" validate:"required,email"`
    FirstName string    `json:"first_name,omitempty" gorm:"size:100"`
    Password  string    `json:"-" gorm:"column:password_hash"`  // - = JSON'da gösterme
    CreatedAt time.Time `json:"created_at" gorm:"autoCreateTime"`
}
```

### JSON Encoding/Decoding

```go
// Struct -> JSON (Marshal)
user := User{ID: "123", Email: "john@example.com"}
jsonData, err := json.Marshal(user)
// {"id":"123","email":"john@example.com"}

// JSON -> Struct (Unmarshal)
jsonStr := `{"id":"123","email":"john@example.com"}`
var user User
err := json.Unmarshal([]byte(jsonStr), &user)
```

### Gin'de JSON Binding

```go
// handler/auth_handler.go
func (h *AuthHandler) Register(c *gin.Context) {
    var req dto.RegisterRequest

    // JSON body'yi struct'a bind et (otomatik validation)
    if err := c.ShouldBindJSON(&req); err != nil {
        c.JSON(400, gin.H{"error": err.Error()})
        return
    }

    // req kullanılabilir
    response, err := h.useCase.Register(c.Request.Context(), &req)
}

// dto/auth_dto.go
type RegisterRequest struct {
    Email     string `json:"email" binding:"required,email"`
    Username  string `json:"username" binding:"required,min=3,max=50"`
    Password  string `json:"password" binding:"required,min=8"`
    FirstName string `json:"first_name" binding:"required"`
    LastName  string `json:"last_name"`
}
```

### GORM Tags

```go
type User struct {
    ID           uuid.UUID `gorm:"type:uuid;primary_key;default:gen_random_uuid()"`
    Email        string    `gorm:"uniqueIndex;not null;size:255"`
    Username     string    `gorm:"uniqueIndex;not null;size:50"`
    PasswordHash string    `gorm:"column:password_hash;not null"`  // Custom column name
    IsActive     bool      `gorm:"default:true;not null"`
    CreatedAt    time.Time `gorm:"autoCreateTime"`  // Auto set on create
    UpdatedAt    time.Time `gorm:"autoUpdateTime"`  // Auto update on save
}
```

**Common Tags:**

- `json`: JSON serialization
- `gorm`: GORM ORM
- `binding`: Gin validation
- `validate`: go-playground/validator
- `yaml`: YAML serialization
- `xml`: XML serialization

---

## 11. HTTP & Web

### Gin Framework

```go
// Router oluştur
router := gin.Default()  // Logger + Recovery middleware

// Routes
router.GET("/health", func(c *gin.Context) {
    c.JSON(200, gin.H{"status": "ok"})
})

router.POST("/users", func(c *gin.Context) {
    var user User
    if err := c.ShouldBindJSON(&user); err != nil {
        c.JSON(400, gin.H{"error": err.Error()})
        return
    }

    c.JSON(201, user)
})

// Server başlat
router.Run(":5004")
```

### Gin Context

```go
func Handler(c *gin.Context) {
    // Request
    c.Request.Method      // HTTP method
    c.Request.URL         // URL
    c.Request.Context()   // Go context

    // Path parameters
    id := c.Param("id")  // /users/:id

    // Query parameters
    page := c.Query("page")         // /users?page=1
    limit := c.DefaultQuery("limit", "10")

    // Headers
    token := c.GetHeader("Authorization")

    // JSON body
    var req RegisterRequest
    c.ShouldBindJSON(&req)

    // Response
    c.JSON(200, gin.H{"message": "success"})
    c.JSON(400, gin.H{"error": "bad request"})
    c.String(200, "Hello")
    c.Data(200, "application/json", []byte(`{"key":"value"}`))

    // Abort
    c.AbortWithStatusJSON(401, gin.H{"error": "Unauthorized"})
}
```

### Middleware

```go
// Global middleware
router.Use(LoggerMiddleware())
router.Use(gin.Recovery())

// Group middleware
admin := router.Group("/admin")
admin.Use(AuthMiddleware())
{
    admin.GET("/users", ListUsers)
}

// Middleware fonksiyonu
func AuthMiddleware() gin.HandlerFunc {
    return func(c *gin.Context) {
        token := c.GetHeader("Authorization")
        if token == "" {
            c.AbortWithStatusJSON(401, gin.H{"error": "Unauthorized"})
            return
        }

        // Validate token...

        c.Next()  // Sonraki handler'a geç
    }
}
```

### Route Groups

```go
router := gin.Default()

// API v1
v1 := router.Group("/api/v1")
{
    v1.GET("/users", ListUsers)
    v1.POST("/users", CreateUser)

    // Nested group
    auth := v1.Group("/auth")
    {
        auth.POST("/register", Register)
        auth.POST("/login", Login)
    }
}

// API v2
v2 := router.Group("/api/v2")
{
    v2.GET("/users", ListUsersV2)
}
```

---

## 12. Database & GORM

### GORM Connection

```go
// pkg/database/postgres.go
func NewPostgresDB(cfg *config.DatabaseConfig) (*gorm.DB, error) {
    dsn := fmt.Sprintf(
        "host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
        cfg.Host, cfg.Port, cfg.User, cfg.Password, cfg.Name, cfg.SSLMode,
    )

    db, err := gorm.Open(postgres.Open(dsn), &gorm.Config{
        Logger: logger.Default.LogMode(logger.Info),
    })
    if err != nil {
        return nil, err
    }

    // Auto Migrate
    db.AutoMigrate(&domain.User{}, &domain.RefreshToken{})

    return db, nil
}
```

### CRUD Operations

```go
// Create
user := &User{Email: "john@example.com"}
db.Create(user)  // user.ID otomatik set edilir

// Read
var user User
db.First(&user, "id = ?", userID)              // SELECT * FROM users WHERE id = ? LIMIT 1
db.Where("email = ?", email).First(&user)      // SELECT * FROM users WHERE email = ? LIMIT 1
db.Find(&users)                                 // SELECT * FROM users

// Update
db.Model(&user).Update("email", "new@example.com")
db.Model(&user).Updates(User{Email: "new@example.com", IsActive: false})

// Delete
db.Delete(&user, userID)  // Soft delete (if DeletedAt field exists)
db.Unscoped().Delete(&user, userID)  // Hard delete
```

### Query Methods

```go
// Where
db.Where("email = ?", email).First(&user)
db.Where("is_active = ? AND created_at > ?", true, time.Now().AddDate(0, 0, -7)).Find(&users)

// Or
db.Where("email = ?", email).Or("username = ?", username).First(&user)

// Not
db.Not("is_active = ?", true).Find(&users)

// Order
db.Order("created_at DESC").Find(&users)

// Limit & Offset
db.Limit(10).Offset(20).Find(&users)

// Count
var count int64
db.Model(&User{}).Where("is_active = ?", true).Count(&count)

// Pluck (tek column al)
var emails []string
db.Model(&User{}).Pluck("email", &emails)

// Exists
exists := db.Where("email = ?", email).First(&User{}).RowsAffected > 0
```

### Transactions

```go
// Transaction
err := db.Transaction(func(tx *gorm.DB) error {
    // 1. User oluştur
    if err := tx.Create(&user).Error; err != nil {
        return err  // Rollback
    }

    // 2. Refresh token oluştur
    if err := tx.Create(&refreshToken).Error; err != nil {
        return err  // Rollback
    }

    // Commit
    return nil
})
```

### Auth Service'te GORM Kullanımı

```go
// repository/user_repository.go
type UserRepository struct {
    db *gorm.DB
}

func (r *UserRepository) Create(ctx context.Context, user *domain.User) error {
    return r.db.WithContext(ctx).Create(user).Error
}

func (r *UserRepository) GetByID(ctx context.Context, id uuid.UUID) (*domain.User, error) {
    var user domain.User
    err := r.db.WithContext(ctx).Where("id = ?", id).First(&user).Error
    if err != nil {
        if errors.Is(err, gorm.ErrRecordNotFound) {
            return nil, nil  // User bulunamadı
        }
        return nil, err  // Database error
    }
    return &user, nil
}

func (r *UserRepository) ExistsByEmail(ctx context.Context, email string) (bool, error) {
    var count int64
    err := r.db.WithContext(ctx).Model(&domain.User{}).Where("email = ?", email).Count(&count).Error
    return count > 0, err
}

func (r *UserRepository) UpdateLastLogin(ctx context.Context, userID uuid.UUID) error {
    return r.db.WithContext(ctx).Model(&domain.User{}).
        Where("id = ?", userID).
        Update("last_login_at", time.Now()).Error
}
```

---

## 🎯 Özet: Önemli Go Kavramları

### 1. **Pointers** (`*` ve `&`)

- `&user` = user'ın adresini al
- `*User` = User pointer tipi
- Memory efficiency için büyük struct'larda kullan

### 2. **Error Handling**

- Her fonksiyon `(result, error)` döner
- `if err != nil` pattern'i
- Custom error'lar: `var ErrNotFound = errors.New(...)`

### 3. **Interface'ler**

- Abstract type (contract)
- Implicit implementation (otomatik)
- Dependency Injection için kullan

### 4. **Context**

- Her fonksiyonun ilk parametresi
- Timeout, cancel, request-scoped values
- `defer cancel()` unutma

### 5. **Struct Tags**

- `json:"field_name"` = JSON serialization
- `gorm:"column:field"` = Database mapping
- `binding:"required"` = Validation

### 6. **Concurrency**

- `go func()` = Goroutine (lightweight thread)
- Channel = Goroutine'ler arası iletişim
- `defer` = Cleanup fonksiyonları

### 7. **Package System**

- Büyük harf = Exported (public)
- Küçük harf = Unexported (private)
- `internal/` = Private packages

---

## 📖 Daha Fazla Öğrenmek İçin

1. **Official Go Tour**: https://go.dev/tour/
2. **Effective Go**: https://go.dev/doc/effective_go
3. **Go by Example**: https://gobyexample.com/
4. **Gin Documentation**: https://gin-gonic.com/docs/
5. **GORM Documentation**: https://gorm.io/docs/

---

## 💡 Pro Tips

1. **IDE**: VS Code + Go extension (otomatik import, format, lint)
2. **Formatting**: `go fmt` otomatik kullanılır (save on format)
3. **Linting**: `golangci-lint` kullan
4. **Testing**: `go test ./...` (test dosyaları: `*_test.go`)
5. **Documentation**: `godoc` ile otomatik docs oluştur

**Başarılar! 🚀**

// Package usecase - Application Layer (Clean Architecture)
// Bu katman iş mantığını (business logic) içerir.
// Domain katmanını kullanır ama Infrastructure'dan bağımsızdır.
package usecase

import (
	"context"     // Go'nun context paketi - timeout, cancel işlemleri için
	"errors"      // Hata tanımlamaları için
	"time"        // Zaman işlemleri için (token expiry vs.)

	"auth-service/internal/application/dto"  // Data Transfer Objects - API request/response
	"auth-service/internal/domain"           // Domain entities ve repository interfaces
	"auth-service/pkg/security"              // JWT ve şifreleme servisleri

	"github.com/google/uuid"  // UUID oluşturma ve parse için
)

// Hata Tanımlamaları
// Go'da error handling için custom error'lar oluşturuyoruz.
// Bu error'ları handler'da yakalayıp uygun HTTP status code döneceğiz.
var (
	// ErrInvalidCredentials - Email/username veya şifre yanlış
	ErrInvalidCredentials = errors.New("invalid credentials")
	
	// ErrUserAlreadyExists - Kayıt olurken email veya username zaten kullanılıyor
	ErrUserAlreadyExists  = errors.New("user already exists")
	
	// ErrUserNotFound - Kullanıcı veritabanında bulunamadı
	ErrUserNotFound       = errors.New("user not found")
	
	// ErrInvalidToken - JWT token geçersiz veya süresi dolmuş
	ErrInvalidToken       = errors.New("invalid or expired token")
	
	// ErrUserInactive - Kullanıcı hesabı pasif (banned veya deleted)
	ErrUserInactive       = errors.New("user account is inactive")
)

// AuthUseCase - Kimlik doğrulama iş mantığını yöneten ana struct
// Clean Architecture'da Use Case = Business Logic katmanı
// Bu struct tüm authentication işlemlerini koordine eder
type AuthUseCase struct {
	// userRepo - Kullanıcı veritabanı işlemleri için interface (Dependency Injection)
	// Interface kullanmamızın sebebi: test'lerde mock repository kullanabilmek
	userRepo         domain.UserRepository
	
	// refreshTokenRepo - Refresh token'ları veritabanında saklamak için
	refreshTokenRepo domain.RefreshTokenRepository
	
	// jwtService - JWT token oluşturma ve doğrulama servisi
	// Pointer kullanıyoruz çünkü servis içinde state var (secret key vs.)
	jwtService       *security.JWTService
	
	// passwordService - Şifre hash'leme ve karşılaştırma servisi (bcrypt)
	passwordService  *security.PasswordService
	
	// accessTokenTTL - Access token'ın ne kadar süre geçerli olacağı (örn: 15 dakika)
	// time.Duration = Go'nun süre tipi (15*time.Minute gibi)
	accessTokenTTL   time.Duration
	
	// refreshTokenTTL - Refresh token'ın ne kadar süre geçerli olacağı (örn: 7 gün)
	refreshTokenTTL  time.Duration
}

// NewAuthUseCase - AuthUseCase oluşturan constructor fonksiyon
// Go'da constructor için New prefix'i kullanılır (convention)
// 
// Dependency Injection Pattern:
// Tüm bağımlılıklar (dependencies) dışarıdan parametre olarak veriliyor.
// Bu sayede:
// 1. Test edilebilir kod (mock'lar inject edebiliriz)
// 2. Loose coupling (gevşek bağlılık)
// 3. Değiştirilebilir implementasyonlar
func NewAuthUseCase(
	userRepo domain.UserRepository,              // Kullanıcı repository interface'i
	refreshTokenRepo domain.RefreshTokenRepository, // Token repository interface'i
	jwtService *security.JWTService,             // JWT servisi
	passwordService *security.PasswordService,   // Password servisi
	accessTokenTTL time.Duration,                // Access token süresi
	refreshTokenTTL time.Duration,               // Refresh token süresi
) *AuthUseCase {  // Pointer döndürüyoruz (struct büyük olduğu için memory efficient)
	// Struct'ı oluştur ve pointer'ını döndür
	// & operatörü = pointer almak için kullanılır
	return &AuthUseCase{
		userRepo:         userRepo,
		refreshTokenRepo: refreshTokenRepo,
		jwtService:       jwtService,
		passwordService:  passwordService,
		accessTokenTTL:   accessTokenTTL,
		refreshTokenTTL:  refreshTokenTTL,
	}
}

// Register - Yeni kullanıcı kaydı oluşturur (Sign Up)
// Method syntax: func (receiver) MethodName(params) (returns)
// (uc *AuthUseCase) = Bu method AuthUseCase struct'ına aittir
// uc = "use case" kısaltması (convention), receiver'ın adı
func (uc *AuthUseCase) Register(ctx context.Context, req *dto.RegisterRequest) (*dto.AuthResponse, error) {
	// Context nedir?
	// Go'da her request için context taşınır. İçinde:
	// - Timeout bilgisi
	// - Cancel signal
	// - Request-scoped değerler (user ID, trace ID vs.)
	
	// ADIM 1: Email'in daha önce kullanılıp kullanılmadığını kontrol et
	exists, err := uc.userRepo.ExistsByEmail(ctx, req.Email)
	// Go'da error handling pattern:
	// Fonksiyon (sonuç, error) şeklinde 2 değer döner
	if err != nil {  // nil = Go'da "null" anlamına gelir
		// Database hatası varsa, hemen çık ve hatayı döndür
		return nil, err  // nil = boş response, err = hata
	}
	if exists {
		// Email zaten kayıtlı, custom error döndür
		return nil, ErrUserAlreadyExists
	}

	// ADIM 2: Username'in daha önce kullanılıp kullanılmadığını kontrol et
	exists, err = uc.userRepo.ExistsByUsername(ctx, req.Username)
	if err != nil {
		return nil, err
	}
	if exists {
		return nil, ErrUserAlreadyExists
	}

	// ADIM 3: Şifreyi hash'le (bcrypt kullanarak)
	// Plain text şifre asla veritabanına kaydedilmez! Güvenlik 101
	passwordHash, err := uc.passwordService.HashPassword(req.Password)
	if err != nil {
		return nil, err
	}

	// ADIM 4: User entity'sini oluştur
	// & operatörü = struct'ın pointer'ını almak için
	// Pointer kullanmamızın sebebi: büyük struct'ları kopyalamamak (performance)
	user := &domain.User{
		Email:        req.Email,          // Request'ten gelen email
		Username:     req.Username,       // Request'ten gelen username
		PasswordHash: passwordHash,       // Hash'lenmiş şifre (güvenli)
		FirstName:    req.FirstName,      // İsim (opsiyonel)
		LastName:     req.LastName,       // Soyisim (opsiyonel)
		IsActive:     true,               // Yeni kullanıcı aktif olarak başlar
		IsVerified:   false,              // Email doğrulaması yapılmamış
	}

	// ADIM 5: User'ı veritabanına kaydet
	// Create fonksiyonu user'a ID, CreatedAt, UpdatedAt ekleyecek (GORM)
	if err := uc.userRepo.Create(ctx, user); err != nil {
		return nil, err
	}

	// ADIM 6: JWT token'ları oluştur ve kullanıcıya döndür
	// Bu sayede kullanıcı kayıt olduktan sonra otomatik login olur
	return uc.generateAuthResponse(ctx, user)
}

// Login - Kullanıcı girişi yapar (Sign In)
// Email veya username ile giriş yapılabilir
func (uc *AuthUseCase) Login(ctx context.Context, req *dto.LoginRequest) (*dto.AuthResponse, error) {
	// ADIM 1: Kullanıcıyı bul (email veya username ile)
	// Go'da variable declaration:
	// var name type = değer
	// var user *domain.User = pointer tipinde değişken
	var user *domain.User
	var err error  // error tipi Go'nun built-in tipi

	// Önce email olarak dene
	user, err = uc.userRepo.GetByEmail(ctx, req.EmailOrUsername)
	// || = veya (OR) operatörü
	if err != nil || user == nil {  // == nil = pointer boş mu kontrolü
		// Email'le bulamadık, username olarak dene
		user, err = uc.userRepo.GetByUsername(ctx, req.EmailOrUsername)
		if err != nil || user == nil {
			// İkisiyle de bulamadık, geçersiz credential
			// Güvenlik notu: "Email bulunamadı" dememizin sebebi:
			// Hacker'a hangi email'lerin kayıtlı olduğunu söylememek
			return nil, ErrInvalidCredentials
		}
	}

	// ADIM 2: Kullanıcı hesabı aktif mi kontrol et
	// ! = değil (NOT) operatörü
	if !user.IsActive {
		// Hesap pasif (banned, deleted vs.)
		return nil, ErrUserInactive
	}

	// ADIM 3: Şifreyi doğrula
	// bcrypt ile hash'lenmiş şifre karşılaştırılır
	if !uc.passwordService.ComparePassword(user.PasswordHash, req.Password) {
		// Şifre yanlış
		return nil, ErrInvalidCredentials
	}

	// ADIM 4: Son giriş zamanını güncelle (analytics için)
	if err := uc.userRepo.UpdateLastLogin(ctx, user.ID); err != nil {
		// Bu hata kritik değil, login'i başarısız yapma
		// Sadece log'la (production'da logging middleware yapacak)
	}

	// ADIM 5: JWT token'ları oluştur ve döndür
	return uc.generateAuthResponse(ctx, user)
}

// RefreshToken - Eski refresh token ile yeni access token al
// JWT Token Strategy:
// - Access Token: Kısa ömürlü (15 dk), her istekte gönderilir
// - Refresh Token: Uzun ömürlü (7 gün), sadece yenileme için kullanılır
// Bu sayede access token çalınsa bile kısa sürede geçersiz olur
func (uc *AuthUseCase) RefreshToken(ctx context.Context, refreshTokenString string) (*dto.AuthResponse, error) {
	// ADIM 1: Refresh token'ı veritabanında bul
	// Refresh token'lar veritabanında saklanır (revoke edebilmek için)
	refreshToken, err := uc.refreshTokenRepo.GetByToken(ctx, refreshTokenString)
	if err != nil || refreshToken == nil {
		// Token veritabanında yok veya hata var
		return nil, ErrInvalidToken
	}

	// ADIM 2: Token geçerli mi kontrol et
	// IsValid() method'u: expired mı, revoked mı kontrol eder
	if !refreshToken.IsValid() {
		// Token süresi dolmuş veya iptal edilmiş
		return nil, ErrInvalidToken
	}

	// ADIM 3: Token'ın sahibi olan kullanıcıyı bul
	user, err := uc.userRepo.GetByID(ctx, refreshToken.UserID)
	if err != nil || user == nil {
		// Kullanıcı silinmiş olabilir
		return nil, ErrUserNotFound
	}

	// ADIM 4: Kullanıcı hesabı aktif mi kontrol et
	if !user.IsActive {
		// Hesap ban yemiş, yeni token verme
		return nil, ErrUserInactive
	}

	// ADIM 5: Eski refresh token'ı iptal et (revoke)
	// Güvenlik: Aynı refresh token tekrar kullanılamasın
	// Token Rotation strategy: Her refresh'te yeni token ver
	if err := uc.refreshTokenRepo.Revoke(ctx, refreshTokenString); err != nil {
		// Bu hata kritik değil, devam et
	}

	// ADIM 6: Yeni access ve refresh token'lar oluştur
	return uc.generateAuthResponse(ctx, user)
}

// Logout - Kullanıcının tüm refresh token'larını iptal eder
// JWT'nin dezavantajı: Access token'lar stateless (server'da saklanmaz)
// Bu yüzden logout yaptıktan sonra bile access token süresi dolana kadar geçerlidir.
// Çözüm: Kısa ömürlü access token (15 dk) + blacklist (opsiyonel)
func (uc *AuthUseCase) Logout(ctx context.Context, userID uuid.UUID) error {
	// Kullanıcının tüm refresh token'larını iptal et
	// Bu sayede yeni access token alamazlar
	// uuid.UUID = Google'un UUID kütüphanesi, universally unique identifier
	return uc.refreshTokenRepo.RevokeAllByUserID(ctx, userID)
}

// generateAuthResponse - Token'ları oluşturup AuthResponse döndüren yardımcı fonksiyon
// Private method (küçük harf ile başlar): Sadece bu package içinden çağrılabilir
// Go'da Access Control:
// - Büyük harf = Public (exported): Register, Login vs.
// - Küçük harf = Private (unexported): generateAuthResponse
func (uc *AuthUseCase) generateAuthResponse(ctx context.Context, user *domain.User) (*dto.AuthResponse, error) {
	// ADIM 1: JWT Access Token oluştur
	// Access token içinde user bilgileri (claims) saklanır:
	// - user_id: Kullanıcının ID'si
	// - email: Email adresi
	// - username: Kullanıcı adı
	// - exp: Token ne zaman expire olacak (expiration)
	accessToken, err := uc.jwtService.GenerateAccessToken(user.ID, user.Email, user.Username)
	if err != nil {
		// JWT oluşturma hatası (secret key problemi vs.)
		return nil, err
	}

	// ADIM 2: Refresh Token string'i oluştur
	// Refresh token = random, secure string (JWT değil)
	// Veritabanında saklanacak
	refreshTokenString, err := uc.jwtService.GenerateRefreshToken()
	if err != nil {
		return nil, err
	}

	// ADIM 3: Refresh token entity'sini oluştur
	refreshToken := &domain.RefreshToken{
		UserID:    user.ID,                                // Hangi kullanıcıya ait
		Token:     refreshTokenString,                     // Token string'i
		ExpiresAt: time.Now().Add(uc.refreshTokenTTL),    // Şimdi + 7 gün (config'den gelir)
		IsRevoked: false,                                  // Aktif token
	}

	// ADIM 4: Refresh token'ı veritabanına kaydet
	if err := uc.refreshTokenRepo.Create(ctx, refreshToken); err != nil {
		return nil, err
	}

	// ADIM 5: AuthResponse DTO'sunu oluştur ve döndür
	// & = struct'tan pointer oluşturma
	return &dto.AuthResponse{
		AccessToken:  accessToken,                          // JWT access token
		RefreshToken: refreshTokenString,                   // Refresh token
		TokenType:    "Bearer",                             // OAuth 2.0 standard: "Bearer" prefix
		ExpiresIn:    int64(uc.accessTokenTTL.Seconds()),  // Kaç saniye sonra expire olur
		// User bilgilerini de dön (frontend'de kullanıcı bilgisini göstermek için)
		User: &dto.UserInfo{
			ID:        user.ID.String(),  // UUID'yi string'e çevir (JSON için)
			Email:     user.Email,
			Username:  user.Username,
			FirstName: user.FirstName,
			LastName:  user.LastName,
			IsActive:  user.IsActive,
		},
	}, nil  // nil = hata yok
}

// Package security - Güvenlik ile ilgili tüm işlemler (JWT, şifreleme)
// pkg/ klasörü = Shared packages (birden fazla servis kullanabilir)
package security

import (
	"crypto/rand"         // Kriptografik random sayı üretimi (güvenli)
	"encoding/base64"     // Base64 encoding/decoding
	"errors"              // Hata tanımlamaları
	"time"                // Zaman işlemleri

	"github.com/golang-jwt/jwt/v5"  // JWT (JSON Web Token) kütüphanesi
	"github.com/google/uuid"        // UUID işlemleri
)

// Hata tanımlamaları
var (
	ErrInvalidToken = errors.New("invalid token")  // Token formatı yanlış veya signature geçersiz
	ErrExpiredToken = errors.New("expired token")  // Token süresi dolmuş
)

// JWTClaims - JWT token içinde saklanacak bilgiler (payload)
// JWT = 3 parça: Header.Payload.Signature
// Claims = Payload kısmında saklanan bilgiler
type JWTClaims struct {
	// Custom claims (bizim eklediğimiz bilgiler)
	UserID   string `json:"user_id"`   // Kullanıcı ID'si
	Email    string `json:"email"`     // Email adresi
	Username string `json:"username"` // Kullanıcı adı
	
	// Standard JWT claims (RFC 7519)
	// jwt.RegisteredClaims = exp, iat, nbf, iss, sub, aud, jti
	jwt.RegisteredClaims  // Embedding (Go'nun inheritance benzeri özelliği)
}

// JWTService - JWT token oluşturma ve doğrulama servisi
// Bu servis JWT işlemlerini kapsüller (encapsulation)
type JWTService struct {
	// secretKey - JWT'yi imzalamak için kullanılan gizli anahtar
	// HMAC-SHA256 algoritması kullanılır: hash(header + payload + secret)
	// Bu key'i bilen herkes token oluşturabilir, bu yüzden GİZLİ tutulmalı!
	// []byte = byte array (string yerine performans için)
	secretKey       []byte
	
	// accessTokenTTL - Access token ne kadar süre geçerli olacak
	// TTL = Time To Live (yaşam süresi)
	// Genelde kısa: 15 dakika - 1 saat
	accessTokenTTL  time.Duration
	
	// refreshTokenTTL - Refresh token ne kadar süre geçerli olacak
	// Genelde uzun: 7 gün - 30 gün
	refreshTokenTTL time.Duration
}

// NewJWTService - JWTService oluşturan factory fonksiyon
// Factory Pattern: Obje oluşturmayı kapsülleyen design pattern
func NewJWTService(secretKey string, accessTokenTTL, refreshTokenTTL time.Duration) *JWTService {
	return &JWTService{
		secretKey:       []byte(secretKey),  // String'i byte array'e çevir
		accessTokenTTL:  accessTokenTTL,
		refreshTokenTTL: refreshTokenTTL,
	}
}

// GenerateAccessToken - Yeni JWT access token oluşturur
// JWT Format: xxxxx.yyyyy.zzzzz
// - xxxxx: Header (algorithm, type)
// - yyyyy: Payload (claims - kullanıcı bilgileri)
// - zzzzz: Signature (doğrulama için)
func (s *JWTService) GenerateAccessToken(userID uuid.UUID, email, username string) (string, error) {
	// Şu anki zaman (token oluşturulma zamanı)
	now := time.Now()
	
	// Claims'leri (payload) oluştur
	claims := &JWTClaims{
		// Custom claims - bizim eklediğimiz bilgiler
		UserID:   userID.String(),  // UUID'yi string'e çevir
		Email:    email,
		Username: username,
		
		// Standard JWT claims (RFC 7519 standardı)
		RegisteredClaims: jwt.RegisteredClaims{
			// ExpiresAt - Token ne zaman expire olacak
			// Şimdi + 15 dakika (veya config'den gelen süre)
			ExpiresAt: jwt.NewNumericDate(now.Add(s.accessTokenTTL)),
			
			// IssuedAt - Token ne zaman oluşturuldu
			IssuedAt:  jwt.NewNumericDate(now),
			
			// NotBefore - Token ne zamandan itibaren geçerli
			// Genelde şimdi, ama gelecekte aktif olacak token'lar için kullanılabilir
			NotBefore: jwt.NewNumericDate(now),
			
			// Issuer - Token'ı kim oluşturdu
			// Mikroservis ortamlarında hangi servis oluşturdu anlamak için
			Issuer:    "auth-service",
			
			// Subject - Token kimin için oluşturuldu
			// Genelde user ID kullanılır
			Subject:   userID.String(),
		},
	}

	// JWT token oluştur
	// SigningMethodHS256 = HMAC-SHA256 algoritması
	// HS256 = Symmetric encryption (aynı key hem imzalar hem doğrular)
	// Alternatif: RS256 (Asymmetric - public/private key)
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	
	// Token'ı secret key ile imzala ve string'e çevir
	// Sonuç: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiMTIzIn0.signature"
	return token.SignedString(s.secretKey)
}

// GenerateRefreshToken - Yeni refresh token oluşturur
// NOT: Refresh token JWT değildir! Sadece random, güvenli bir string'tir.
// Neden JWT değil?
// - Daha hızlı (parsing yok)
// - Daha güvenli (revoke edilebilir, veritabanında saklanır)
// - Daha basit (payload'a gerek yok)
func (s *JWTService) GenerateRefreshToken() (string, error) {
	// 32 byte'lık random byte array oluştur
	// make() = Go'da array/slice oluşturma fonksiyonu
	b := make([]byte, 32)
	
	// Kriptografik random byte'lar üret
	// crypto/rand = Güvenli random (math/rand değil!)
	// math/rand = Tahmin edilebilir (güvenlik için kullanma!)
	if _, err := rand.Read(b); err != nil {
		return "", err
	}
	
	// Byte array'i base64 string'e çevir
	// Base64 = Binary veriyi text'e çevirme yöntemi
	// URLEncoding = URL-safe karakterler (+ ve / yerine - ve _ kullanır)
	// Sonuç: "a3f9Bk2... gibi 43 karakterlik string
	return base64.URLEncoding.EncodeToString(b), nil
}

// ValidateToken - JWT token'ı doğrular ve claims'ı döndürür
// Token doğrulama adımları:
// 1. Format kontrolü (xxxxx.yyyyy.zzzzz)
// 2. Signature doğrulama (secret key ile)
// 3. Expiration kontrolü (süresi dolmuş mu)
// 4. Claims parse etme
func (s *JWTService) ValidateToken(tokenString string) (*JWTClaims, error) {
	// JWT token'ı parse et ve doğrula
	// ParseWithClaims = Token'ı çöz ve claims'ı JWTClaims struct'ına map'le
	token, err := jwt.ParseWithClaims(tokenString, &JWTClaims{}, func(token *jwt.Token) (interface{}, error) {
		// Callback fonksiyon: Signing method kontrolü
		// Token'ın HMAC algoritması ile imzalandığını doğrula
		// Type assertion: token.Method'un *jwt.SigningMethodHMAC tipinde olup olmadığını kontrol et
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			// Yanlış algoritma (belki RS256 veya başka bir şey)
			// Güvenlik: Algorithm confusion attack'ı engellemek için
			return nil, ErrInvalidToken
		}
		// Doğrulama için secret key'i döndür
		return s.secretKey, nil
	})

	// Parse hatası varsa (format yanlış, signature uyuşmuyor vs.)
	if err != nil {
		return nil, err
	}

	// Token geçerli mi kontrol et (expiration vs.)
	if !token.Valid {
		return nil, ErrInvalidToken
	}

	// Claims'ı JWTClaims tipine cast et
	// Type assertion: interface{}'den *JWTClaims'e dönüştürme
	claims, ok := token.Claims.(*JWTClaims)
	if !ok {
		// Cast başarısız (bu normalde olmamalı)
		return nil, ErrInvalidToken
	}

	// Geçerli token, claims'ı döndür
	return claims, nil
}

// ExtractUserID - Token'dan user ID'şi çıkarır
// Yardımcı fonksiyon: Middleware'de kullanıcı ID'sini hızlıca almak için
func (s *JWTService) ExtractUserID(tokenString string) (uuid.UUID, error) {
	// Token'ı doğrula ve claims'ı al
	claims, err := s.ValidateToken(tokenString)
	if err != nil {
		// Token geçersiz
		// uuid.Nil = "00000000-0000-0000-0000-000000000000" (boş UUID)
		return uuid.Nil, err
	}

	// UserID string'ıni UUID tipine parse et
	// UUID format: "550e8400-e29b-41d4-a716-446655440000"
	userID, err := uuid.Parse(claims.UserID)
	if err != nil {
		// UUID formatı yanlış (bu normalde olmamalı)
		return uuid.Nil, ErrInvalidToken
	}

	return userID, nil
}

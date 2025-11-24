package repository

import (
	"context"
	"time"

	"auth-service/internal/domain"

	"github.com/google/uuid"
	"gorm.io/gorm"
)

// RefreshTokenRepositoryImpl implements the RefreshTokenRepository interface
type RefreshTokenRepositoryImpl struct {
	db *gorm.DB
}

// NewRefreshTokenRepository creates a new refresh token repository
func NewRefreshTokenRepository(db *gorm.DB) domain.RefreshTokenRepository {
	return &RefreshTokenRepositoryImpl{db: db}
}

func (r *RefreshTokenRepositoryImpl) Create(ctx context.Context, token *domain.RefreshToken) error {
	return r.db.WithContext(ctx).Create(token).Error
}

func (r *RefreshTokenRepositoryImpl) GetByToken(ctx context.Context, token string) (*domain.RefreshToken, error) {
	var refreshToken domain.RefreshToken
	err := r.db.WithContext(ctx).Where("token = ? AND is_revoked = false", token).First(&refreshToken).Error
	if err != nil {
		return nil, err
	}
	return &refreshToken, nil
}

func (r *RefreshTokenRepositoryImpl) GetByUserID(ctx context.Context, userID uuid.UUID) ([]*domain.RefreshToken, error) {
	var tokens []*domain.RefreshToken
	err := r.db.WithContext(ctx).Where("user_id = ? AND is_revoked = false", userID).Find(&tokens).Error
	return tokens, err
}

func (r *RefreshTokenRepositoryImpl) Revoke(ctx context.Context, token string) error {
	return r.db.WithContext(ctx).Model(&domain.RefreshToken{}).Where("token = ?", token).Update("is_revoked", true).Error
}

func (r *RefreshTokenRepositoryImpl) RevokeAllByUserID(ctx context.Context, userID uuid.UUID) error {
	return r.db.WithContext(ctx).Model(&domain.RefreshToken{}).Where("user_id = ?", userID).Update("is_revoked", true).Error
}

func (r *RefreshTokenRepositoryImpl) DeleteExpired(ctx context.Context) error {
	return r.db.WithContext(ctx).Where("expires_at < ?", time.Now()).Delete(&domain.RefreshToken{}).Error
}

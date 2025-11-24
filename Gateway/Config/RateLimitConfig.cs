namespace Gateway.Config;

/// <summary>
/// Rate Limiting configuration
/// IP bazlı veya endpoint bazlı rate limiting ayarları
/// </summary>
public class RateLimitConfig
{
    public bool EnableRateLimiting { get; set; } = true;
    public int PermitLimit { get; set; } = 100;
    public int WindowSeconds { get; set; } = 60;
    public int QueueLimit { get; set; } = 10;
}

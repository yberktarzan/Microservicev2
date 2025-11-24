using Gateway.Config;
using Gateway.Middleware;
using HealthChecks.UI.Client;
using Microsoft.AspNetCore.Diagnostics.HealthChecks;

namespace Gateway.Extensions;

/// <summary>
/// IApplicationBuilder extension metodları
/// Middleware pipeline'ı organize eder
/// </summary>
public static class ApplicationBuilderExtensions
{
    /// <summary>
    /// Tüm custom middleware'leri sıralı şekilde ekler
    /// Order önemli: Exception handling en üstte olmalı
    /// </summary>
    public static IApplicationBuilder UseGatewayMiddlewares(this IApplicationBuilder app)
    {
        // 1. Exception handling en üstte (tüm hataları yakalar)
        app.UseMiddleware<ExceptionHandlingMiddleware>();

        // 2. Security headers
        app.UseMiddleware<SecurityHeadersMiddleware>();

        // 3. Correlation ID (logging için)
        app.UseMiddleware<CorrelationIdMiddleware>();

        // 4. Request logging
        app.UseMiddleware<RequestLoggingMiddleware>();

        return app;
    }

    /// <summary>
    /// Health check endpoints ekler
    /// </summary>
    public static IApplicationBuilder UseGatewayHealthChecks(this IApplicationBuilder app)
    {
        app.UseHealthChecks("/health", new HealthCheckOptions
        {
            Predicate = _ => true,
            ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
        });

        app.UseHealthChecks("/health/ready", new HealthCheckOptions
        {
            Predicate = check => check.Tags.Contains("ready"),
            ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
        });

        app.UseHealthChecks("/health/live", new HealthCheckOptions
        {
            Predicate = _ => false,
            ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
        });

        return app;
    }

    /// <summary>
    /// Prometheus metrics endpoint'i ekler
    /// </summary>
    public static IApplicationBuilder UseGatewayMetrics(this IApplicationBuilder app)
    {
        // Prometheus metrics will be configured in Program.cs
        return app;
    }

    /// <summary>
    /// CORS policy'yi aktif eder
    /// </summary>
    public static IApplicationBuilder UseGatewayCors(
        this IApplicationBuilder app,
        IConfiguration configuration)
    {
        var corsConfig = configuration.GetSection("CorsConfig").Get<CorsConfig>()
                         ?? new CorsConfig();

        if (corsConfig.EnableCors)
        {
            app.UseCors(corsConfig.PolicyName);
        }

        return app;
    }

    /// <summary>
    /// Rate limiting'i aktif eder
    /// </summary>
    public static IApplicationBuilder UseGatewayRateLimiting(
        this IApplicationBuilder app,
        IConfiguration configuration)
    {
        var rateLimitConfig = configuration.GetSection("RateLimitConfig").Get<RateLimitConfig>()
                              ?? new RateLimitConfig();

        if (rateLimitConfig.EnableRateLimiting)
        {
            app.UseRateLimiter();
        }

        return app;
    }
}

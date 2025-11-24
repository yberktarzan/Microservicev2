using System.Text;
using System.Threading.RateLimiting;
using Gateway.Config;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.IdentityModel.Tokens;
using Polly;
using Polly.Extensions.Http;

namespace Gateway.Extensions;

/// <summary>
/// IServiceCollection extension metodları
/// Dependency Injection konfigürasyonlarını organize eder
/// </summary>
public static class ServiceCollectionExtensions
{
    /// <summary>
    /// YARP Reverse Proxy'yi yapılandırır
    /// </summary>
    public static IServiceCollection AddReverseProxyWithResilience(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        services.AddReverseProxy()
            .LoadFromConfig(configuration.GetSection("ReverseProxy"));

        return services;
    }

    /// <summary>
    /// Polly ile Circuit Breaker ve Retry patterns ekler
    /// Not: .NET 10 için Polly v8 kullanılmalı (eski API deprecated)
    /// </summary>
    public static IServiceCollection AddResiliencePolicies(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        // Polly v8 için modern resilience pipeline kullanılacak
        // Şimdilik basit HttpClient configuration
        services.AddHttpClient("resilient-client");

        return services;
    }

    /// <summary>
    /// JWT Authentication yapılandırması
    /// </summary>
    public static IServiceCollection AddJwtAuthentication(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var jwtConfig = configuration.GetSection("JwtConfig").Get<JwtConfig>()
                        ?? throw new InvalidOperationException("JwtConfig not found in configuration");

        var key = Encoding.UTF8.GetBytes(jwtConfig.Secret);

        services.AddAuthentication(options =>
            {
                options.DefaultAuthenticateScheme = JwtBearerDefaults.AuthenticationScheme;
                options.DefaultChallengeScheme = JwtBearerDefaults.AuthenticationScheme;
            })
            .AddJwtBearer(options =>
            {
                options.RequireHttpsMetadata = false; // Development için
                options.SaveToken = true;
                options.TokenValidationParameters = new TokenValidationParameters
                {
                    ValidateIssuerSigningKey = jwtConfig.ValidateIssuerSigningKey,
                    IssuerSigningKey = new SymmetricSecurityKey(key),
                    ValidateIssuer = jwtConfig.ValidateIssuer,
                    ValidIssuer = jwtConfig.Issuer,
                    ValidateAudience = jwtConfig.ValidateAudience,
                    ValidAudience = jwtConfig.Audience,
                    ValidateLifetime = jwtConfig.ValidateLifetime,
                    ClockSkew = TimeSpan.Zero
                };

                options.Events = new JwtBearerEvents
                {
                    OnAuthenticationFailed = context =>
                    {
                        Console.WriteLine($"JWT Authentication failed: {context.Exception.Message}");
                        return Task.CompletedTask;
                    },
                    OnTokenValidated = context =>
                    {
                        Console.WriteLine("JWT Token validated successfully");
                        return Task.CompletedTask;
                    }
                };
            });

        services.AddAuthorization();

        return services;
    }

    /// <summary>
    /// CORS policy yapılandırması
    /// </summary>
    public static IServiceCollection AddCorsPolicy(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var corsConfig = configuration.GetSection("CorsConfig").Get<CorsConfig>()
                         ?? new CorsConfig();

        if (corsConfig.EnableCors)
        {
            services.AddCors(options =>
            {
                options.AddPolicy(corsConfig.PolicyName, builder =>
                {
                    if (corsConfig.AllowedOrigins.Any())
                    {
                        builder.WithOrigins(corsConfig.AllowedOrigins);
                    }
                    else
                    {
                        builder.AllowAnyOrigin();
                    }

                    if (corsConfig.AllowedMethods.Any())
                    {
                        builder.WithMethods(corsConfig.AllowedMethods);
                    }
                    else
                    {
                        builder.AllowAnyMethod();
                    }

                    if (corsConfig.AllowedHeaders.Any())
                    {
                        builder.WithHeaders(corsConfig.AllowedHeaders);
                    }
                    else
                    {
                        builder.AllowAnyHeader();
                    }

                    if (corsConfig.AllowCredentials)
                    {
                        builder.AllowCredentials();
                    }
                });
            });
        }

        return services;
    }

    /// <summary>
    /// Rate Limiting yapılandırması (.NET 7+ built-in)
    /// </summary>
    public static IServiceCollection AddRateLimiting(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var rateLimitConfig = configuration.GetSection("RateLimitConfig").Get<RateLimitConfig>()
                              ?? new RateLimitConfig();

        if (rateLimitConfig.EnableRateLimiting)
        {
            services.AddRateLimiter(options =>
            {
                options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;

                options.AddPolicy("fixed", context => RateLimitPartition.GetFixedWindowLimiter(
                    partitionKey: context.Connection.RemoteIpAddress?.ToString() ?? "anonymous",
                    factory: _ => new FixedWindowRateLimiterOptions
                    {
                        PermitLimit = rateLimitConfig.PermitLimit,
                        Window = TimeSpan.FromSeconds(rateLimitConfig.WindowSeconds),
                        QueueLimit = rateLimitConfig.QueueLimit
                    }));
            });
        }

        return services;
    }

    /// <summary>
    /// Health Checks yapılandırması
    /// </summary>
    public static IServiceCollection AddGatewayHealthChecks(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        services.AddHealthChecks()
            .AddCheck("self", () => Microsoft.Extensions.Diagnostics.HealthChecks.HealthCheckResult.Healthy("Gateway is running"));

        return services;
    }
}

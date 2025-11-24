using System.Text;
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
        var circuitBreakerConfig = configuration.GetSection("CircuitBreakerConfig").Get<CircuitBreakerConfig>() 
                                   ?? new CircuitBreakerConfig();

        services.AddReverseProxy()
            .LoadFromConfig(configuration.GetSection("ReverseProxy"))
            .ConfigureHttpClient((context, handler) =>
            {
                // Timeout ayarları
                handler.Timeout = TimeSpan.FromSeconds(circuitBreakerConfig.TimeoutSeconds);
            })
            .AddTransforms(transformBuilderContext =>
            {
                // Request headers'a X-Forwarded-* headers ekle
                transformBuilderContext.AddRequestTransform(async transformContext =>
                {
                    var correlationId = transformContext.HttpContext.Items["CorrelationId"]?.ToString();
                    if (!string.IsNullOrEmpty(correlationId))
                    {
                        transformContext.ProxyRequest.Headers.Add("X-Correlation-ID", correlationId);
                    }
                    
                    await Task.CompletedTask;
                });
            });

        return services;
    }

    /// <summary>
    /// Polly ile Circuit Breaker ve Retry patterns ekler
    /// </summary>
    public static IServiceCollection AddResiliencePolicies(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var config = configuration.GetSection("CircuitBreakerConfig").Get<CircuitBreakerConfig>() 
                     ?? new CircuitBreakerConfig();

        services.AddHttpClient("resilient-client")
            .AddPolicyHandler(GetRetryPolicy(config))
            .AddPolicyHandler(GetCircuitBreakerPolicy(config));

        return services;
    }

    private static IAsyncPolicy<HttpResponseMessage> GetRetryPolicy(CircuitBreakerConfig config)
    {
        return HttpPolicyExtensions
            .HandleTransientHttpError()
            .WaitAndRetryAsync(
                config.RetryCount,
                retryAttempt => TimeSpan.FromSeconds(Math.Pow(2, retryAttempt)),
                onRetry: (outcome, timespan, retryCount, context) =>
                {
                    Console.WriteLine($"Retry {retryCount} after {timespan.TotalSeconds}s delay");
                });
    }

    private static IAsyncPolicy<HttpResponseMessage> GetCircuitBreakerPolicy(CircuitBreakerConfig config)
    {
        return HttpPolicyExtensions
            .HandleTransientHttpError()
            .CircuitBreakerAsync(
                config.FailureThreshold,
                config.DurationOfBreak,
                onBreak: (outcome, timespan) =>
                {
                    Console.WriteLine($"Circuit breaker opened for {timespan.TotalSeconds}s");
                },
                onReset: () =>
                {
                    Console.WriteLine("Circuit breaker reset");
                });
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

                options.AddFixedWindowLimiter("fixed", limiterOptions =>
                {
                    limiterOptions.PermitLimit = rateLimitConfig.PermitLimit;
                    limiterOptions.Window = TimeSpan.FromSeconds(rateLimitConfig.WindowSeconds);
                    limiterOptions.QueueProcessingOrder = System.Threading.RateLimiting.QueueProcessingOrder.OldestFirst;
                    limiterOptions.QueueLimit = rateLimitConfig.QueueLimit;
                });

                options.AddSlidingWindowLimiter("sliding", limiterOptions =>
                {
                    limiterOptions.PermitLimit = rateLimitConfig.PermitLimit;
                    limiterOptions.Window = TimeSpan.FromSeconds(rateLimitConfig.WindowSeconds);
                    limiterOptions.SegmentsPerWindow = 4;
                    limiterOptions.QueueProcessingOrder = System.Threading.RateLimiting.QueueProcessingOrder.OldestFirst;
                    limiterOptions.QueueLimit = rateLimitConfig.QueueLimit;
                });
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
            .AddCheck("self", () => Microsoft.Extensions.Diagnostics.HealthChecks.HealthCheckResult.Healthy())
            .AddUrlGroup(new Uri("http://localhost:5001/health"), "user-service", timeout: TimeSpan.FromSeconds(5))
            .AddUrlGroup(new Uri("http://localhost:5002/health"), "order-service", timeout: TimeSpan.FromSeconds(5))
            .AddUrlGroup(new Uri("http://localhost:5003/health"), "product-service", timeout: TimeSpan.FromSeconds(5))
            .AddUrlGroup(new Uri("http://localhost:5004/health"), "auth-service", timeout: TimeSpan.FromSeconds(5));

        return services;
    }
}

using Gateway.Extensions;
using Gateway.HealthChecks;
using HealthChecks.UI.Client;
using Microsoft.AspNetCore.Diagnostics.HealthChecks;
using Prometheus;
using Serilog;

// ============================================================================
// GATEWAY API - REVERSE PROXY ARCHITECTURE
// ============================================================================
// Bu gateway, microservice mimarisinde merkezi giriş noktasıdır
// - YARP ile reverse proxy
// - Polly ile resilience patterns (circuit breaker, retry)
// - Rate limiting ile DDoS koruması
// - JWT authentication ile güvenlik
// - Structured logging ile monitoring
// - Health checks ile service discovery
// ============================================================================

var builder = WebApplication.CreateBuilder(args);

// ============================================================================
// SERILOG CONFIGURATION - Structured Logging
// ============================================================================
Log.Logger = new LoggerConfiguration()
    .ReadFrom.Configuration(builder.Configuration)
    .Enrich.FromLogContext()
    .Enrich.WithMachineName()
    .Enrich.WithThreadId()
    .CreateLogger();

builder.Host.UseSerilog();

Log.Information("🚀 Gateway API starting up...");

try
{
    // ============================================================================
    // SERVICE CONFIGURATION
    // ============================================================================

    // CORS Policy
    builder.Services.AddCorsPolicy(builder.Configuration);

    // JWT Authentication & Authorization
    builder.Services.AddJwtAuthentication(builder.Configuration);

    // Rate Limiting
    builder.Services.AddRateLimiting(builder.Configuration);

    // Resilience Policies (Circuit Breaker, Retry)
    builder.Services.AddResiliencePolicies(builder.Configuration);

    // YARP Reverse Proxy with Custom Configuration
    builder.Services.AddReverseProxyWithResilience(builder.Configuration);

    // Health Checks
    builder.Services.AddHealthChecks()
        .AddCheck("self", () => Microsoft.Extensions.Diagnostics.HealthChecks.HealthCheckResult.Healthy("Gateway is running"));

    // Metrics (Prometheus)
    builder.Services.AddSingleton(Metrics.DefaultRegistry);

    // API Documentation
    builder.Services.AddEndpointsApiExplorer();
    builder.Services.AddSwaggerGen(c =>
    {
        c.SwaggerDoc("v1", new Microsoft.OpenApi.Models.OpenApiInfo
        { 
            Title = "Gateway API", 
            Version = "v1",
            Description = "Reverse Proxy Gateway for Microservices Architecture"
        });
    });

    // ============================================================================
    // APPLICATION PIPELINE
    // ============================================================================
    var app = builder.Build();

    // Swagger UI (Development only)
    if (app.Environment.IsDevelopment())
    {
        app.UseSwagger();
        app.UseSwaggerUI(c => c.SwaggerEndpoint("/swagger/v1/swagger.json", "Gateway API v1"));
    }

    // Custom Middleware Pipeline (Order matters!)
    app.UseGatewayMiddlewares();

    // Metrics Endpoint
    app.UseMetricServer(); // /metrics endpoint
    app.UseHttpMetrics();  // HTTP request metrics

    // CORS
    app.UseGatewayCors(builder.Configuration);

    // HTTPS Redirection
    app.UseHttpsRedirection();

    // Authentication & Authorization
    app.UseAuthentication();
    app.UseAuthorization();

    // Rate Limiting
    app.UseRateLimiter();

    // Health Check Endpoints
    app.MapHealthChecks("/health", new HealthCheckOptions
    {
        Predicate = _ => true,
        ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
    });

    app.MapHealthChecks("/health/ready", new HealthCheckOptions
    {
        Predicate = check => check.Tags.Contains("ready"),
        ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
    });

    app.MapHealthChecks("/health/live", new HealthCheckOptions
    {
        Predicate = _ => false,
        ResponseWriter = UIResponseWriter.WriteHealthCheckUIResponse
    });

    // Gateway Info Endpoint
    app.MapGet("/", () => new
    {
        Service = "API Gateway",
        Version = "1.0.0",
        Status = "Running",
        Timestamp = DateTime.UtcNow,
        Documentation = "/swagger",
        Health = "/health",
        Metrics = "/metrics"
    })
    .WithName("GatewayInfo")
    .WithTags("Gateway");

    // YARP Reverse Proxy - Bu en son eklenmeli (catch-all)
    app.MapReverseProxy();

    Log.Information("✅ Gateway API configured successfully");
    Log.Information("🌐 Listening on: {Urls}", string.Join(", ", builder.Configuration.GetSection("urls").Get<string[]>() ?? new[] { "http://localhost:5000" }));

    app.Run();

    Log.Information("🛑 Gateway API shutting down gracefully");
}
catch (Exception ex)
{
    Log.Fatal(ex, "❌ Gateway API failed to start");
    throw;
}
finally
{
    await Log.CloseAndFlushAsync();
}

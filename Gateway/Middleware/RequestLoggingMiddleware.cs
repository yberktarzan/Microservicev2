using System.Diagnostics;

namespace Gateway.Middleware;

/// <summary>
/// Tüm HTTP requestleri loglar - request/response detayları, timing, status code
/// Performance monitoring ve debugging için kritik
/// </summary>
public class RequestLoggingMiddleware
{
    private readonly RequestDelegate _next;
    private readonly ILogger<RequestLoggingMiddleware> _logger;

    public RequestLoggingMiddleware(RequestDelegate next, ILogger<RequestLoggingMiddleware> logger)
    {
        _next = next;
        _logger = logger;
    }

    public async Task InvokeAsync(HttpContext context)
    {
        var correlationId = context.Items["CorrelationId"]?.ToString() ?? "N/A";
        var stopwatch = Stopwatch.StartNew();

        try
        {
            _logger.LogInformation(
                "Incoming Request: {Method} {Path} | CorrelationId: {CorrelationId} | RemoteIP: {RemoteIP}",
                context.Request.Method,
                context.Request.Path,
                correlationId,
                context.Connection.RemoteIpAddress);

            await _next(context);

            stopwatch.Stop();

            _logger.LogInformation(
                "Outgoing Response: {Method} {Path} | StatusCode: {StatusCode} | Duration: {Duration}ms | CorrelationId: {CorrelationId}",
                context.Request.Method,
                context.Request.Path,
                context.Response.StatusCode,
                stopwatch.ElapsedMilliseconds,
                correlationId);
        }
        catch (Exception ex)
        {
            stopwatch.Stop();

            _logger.LogError(ex,
                "Request Failed: {Method} {Path} | Duration: {Duration}ms | CorrelationId: {CorrelationId}",
                context.Request.Method,
                context.Request.Path,
                stopwatch.ElapsedMilliseconds,
                correlationId);

            throw;
        }
    }
}

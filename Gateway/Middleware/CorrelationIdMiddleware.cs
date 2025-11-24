namespace Gateway.Middleware;

/// <summary>
/// Her request için unique correlation ID oluşturur ve response header'a ekler
/// Distributed tracing ve log korelasyonu için kritik
/// </summary>
public class CorrelationIdMiddleware
{
    private readonly RequestDelegate _next;
    private const string CorrelationIdHeader = "X-Correlation-ID";

    public CorrelationIdMiddleware(RequestDelegate next)
    {
        _next = next;
    }

    public async Task InvokeAsync(HttpContext context)
    {
        // Request'ten correlation ID al veya yeni oluştur
        var correlationId = context.Request.Headers[CorrelationIdHeader].FirstOrDefault() 
                           ?? Guid.NewGuid().ToString();

        // Context'e ekle (downstream'de kullanılabilir)
        context.Items["CorrelationId"] = correlationId;

        // Response header'a ekle
        context.Response.OnStarting(() =>
        {
            context.Response.Headers.TryAdd(CorrelationIdHeader, correlationId);
            return Task.CompletedTask;
        });

        await _next(context);
    }
}

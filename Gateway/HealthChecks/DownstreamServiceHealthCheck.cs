using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace Gateway.HealthChecks;

/// <summary>
/// Downstream servislerin health durumunu kontrol eder
/// </summary>
public class DownstreamServiceHealthCheck : IHealthCheck
{
    private readonly HttpClient _httpClient;
    private readonly string _serviceUrl;
    private readonly string _serviceName;

    public DownstreamServiceHealthCheck(
        HttpClient httpClient,
        string serviceUrl,
        string serviceName)
    {
        _httpClient = httpClient;
        _serviceUrl = serviceUrl;
        _serviceName = serviceName;
    }

    public async Task<HealthCheckResult> CheckHealthAsync(
        HealthCheckContext context,
        CancellationToken cancellationToken = default)
    {
        try
        {
            var response = await _httpClient.GetAsync($"{_serviceUrl}/health", cancellationToken);

            if (response.IsSuccessStatusCode)
            {
                return HealthCheckResult.Healthy($"{_serviceName} is healthy");
            }

            return HealthCheckResult.Unhealthy($"{_serviceName} returned status code {response.StatusCode}");
        }
        catch (Exception ex)
        {
            return HealthCheckResult.Unhealthy($"{_serviceName} is unreachable: {ex.Message}");
        }
    }
}

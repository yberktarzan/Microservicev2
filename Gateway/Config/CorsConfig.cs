namespace Gateway.Config;

/// <summary>
/// CORS policy configuration
/// Frontend uygulamalardan erişim kontrolü
/// </summary>
public class CorsConfig
{
    public bool EnableCors { get; set; } = true;
    public string PolicyName { get; set; } = "DefaultCorsPolicy";
    public string[] AllowedOrigins { get; set; } = Array.Empty<string>();
    public string[] AllowedMethods { get; set; } = Array.Empty<string>();
    public string[] AllowedHeaders { get; set; } = Array.Empty<string>();
    public bool AllowCredentials { get; set; } = true;
}

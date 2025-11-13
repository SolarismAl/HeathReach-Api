# Test the new login-with-password endpoint

$url = "http://localhost:8000/api/auth/login-with-password"
$body = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

Write-Host "Testing Backend-First Login Endpoint..." -ForegroundColor Cyan
Write-Host "URL: $url" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri $url -Method Post -Body $body -ContentType "application/json"
    
    Write-Host "✅ SUCCESS!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Response:" -ForegroundColor Yellow
    $response | ConvertTo-Json -Depth 10
    
    if ($response.success) {
        Write-Host ""
        Write-Host "✅ Login successful!" -ForegroundColor Green
        Write-Host "User: $($response.data.user.name)" -ForegroundColor Cyan
        Write-Host "Role: $($response.data.user.role)" -ForegroundColor Cyan
        Write-Host "Token: $($response.data.token.Substring(0, 50))..." -ForegroundColor Gray
    }
} catch {
    Write-Host "❌ ERROR!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Status Code: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    Write-Host "Error Message: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.ErrorDetails.Message) {
        Write-Host ""
        Write-Host "Response Body:" -ForegroundColor Yellow
        $_.ErrorDetails.Message | ConvertFrom-Json | ConvertTo-Json -Depth 10
    }
}

# Test Backend-First Authentication

Write-Host "Testing Backend-First Authentication" -ForegroundColor Cyan
Write-Host ""

# Test 1: Admin User
Write-Host "Test 1: Admin User" -ForegroundColor Yellow
Write-Host "Email: admin@healthreach.com" -ForegroundColor Gray

$body1 = @{
    email = "admin@healthreach.com"
    password = "admin1234"
} | ConvertTo-Json

try {
    $response1 = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login-with-password" -Method Post -Body $body1 -ContentType "application/json"
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "User: $($response1.data.user.name)" -ForegroundColor White
    Write-Host "Role: $($response1.data.user.role)" -ForegroundColor White
    Write-Host "Token: Present" -ForegroundColor Green
} catch {
    Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $error1 = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "Message: $($error1.message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "----------------------------------------" -ForegroundColor Gray
Write-Host ""

# Test 2: Sample User
Write-Host "Test 2: Sample User" -ForegroundColor Yellow
Write-Host "Email: sample22@gmail.com" -ForegroundColor Gray

$body2 = @{
    email = "sample22@gmail.com"
    password = "1234567890"
} | ConvertTo-Json

try {
    $response2 = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login-with-password" -Method Post -Body $body2 -ContentType "application/json"
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "User: $($response2.data.user.name)" -ForegroundColor White
    Write-Host "Role: $($response2.data.user.role)" -ForegroundColor White
    Write-Host "Token: Present" -ForegroundColor Green
} catch {
    Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        $error2 = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "Message: $($error2.message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Test Complete!" -ForegroundColor Cyan

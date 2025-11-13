# Test with health@gmail.com credentials

Write-Host "=== Testing Backend-First Authentication ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Testing: health@gmail.com" -ForegroundColor Yellow
Write-Host "Password: 123456789" -ForegroundColor Gray
Write-Host ""

$body = @{
    email = "health@gmail.com"
    password = "123456789"
} | ConvertTo-Json

try {
    Write-Host "Sending request to backend..." -ForegroundColor Gray
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login-with-password" `
        -Method Post `
        -Body $body `
        -ContentType "application/json" `
        -TimeoutSec 10
    
    $data = $response.Content | ConvertFrom-Json
    
    Write-Host ""
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "User Details:" -ForegroundColor Cyan
    Write-Host "  User ID: $($data.data.user.user_id)" -ForegroundColor White
    Write-Host "  Name: $($data.data.user.name)" -ForegroundColor White
    Write-Host "  Email: $($data.data.user.email)" -ForegroundColor White
    Write-Host "  Role: $($data.data.user.role)" -ForegroundColor White
    Write-Host ""
    Write-Host "Authentication:" -ForegroundColor Cyan
    Write-Host "  Custom Token: $($data.data.token.Substring(0, 50))..." -ForegroundColor Gray
    Write-Host "  Token Length: $($data.data.token.Length) characters" -ForegroundColor Gray
    
    if ($data.data.firebase_token) {
        Write-Host "  Firebase Token: Present" -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Backend-first authentication is working!" -ForegroundColor Green
    Write-Host "No Firebase Auth initialization needed!" -ForegroundColor Green
    Write-Host ""
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host ""
    Write-Host "FAILED!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Status Code: $statusCode" -ForegroundColor Red
    
    if ($statusCode -eq 401) {
        Write-Host "Reason: Invalid credentials or user does not exist" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Please check:" -ForegroundColor Yellow
        Write-Host "  1. User exists in Firebase Authentication" -ForegroundColor Gray
        Write-Host "  2. Password is correct" -ForegroundColor Gray
        Write-Host "  3. Firebase Console: https://console.firebase.google.com/" -ForegroundColor Gray
    } elseif ($statusCode -eq 500) {
        Write-Host "Reason: Server error" -ForegroundColor Yellow
    }
    
    try {
        $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host ""
        Write-Host "Error Message: $($errorData.message)" -ForegroundColor Yellow
        if ($errorData.error) {
            Write-Host "Details: $($errorData.error)" -ForegroundColor Gray
        }
    } catch {
        Write-Host ""
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Gray
    }
    
    Write-Host ""
}

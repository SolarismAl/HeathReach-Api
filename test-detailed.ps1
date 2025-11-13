# Detailed Backend Test

Write-Host "=== Backend Authentication Test ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Check if endpoint exists
Write-Host "1. Testing endpoint availability..." -ForegroundColor Yellow
try {
    $test = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login-with-password" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
} catch {
    if ($_.Exception.Response.StatusCode.value__ -eq 405) {
        Write-Host "   Backend is running!" -ForegroundColor Green
    } else {
        Write-Host "   Backend is NOT running!" -ForegroundColor Red
        Write-Host "   Start it with: php artisan serve" -ForegroundColor Yellow
        exit
    }
}

Write-Host ""

# Test 2: Try login with admin
Write-Host "2. Testing login with admin@healthreach.com..." -ForegroundColor Yellow

$adminBody = @{
    email = "admin@healthreach.com"
    password = "admin1234"
} | ConvertTo-Json

try {
    $adminResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login-with-password" `
        -Method Post `
        -Body $adminBody `
        -ContentType "application/json" `
        -TimeoutSec 10
    
    $adminData = $adminResponse.Content | ConvertFrom-Json
    Write-Host "   SUCCESS!" -ForegroundColor Green
    Write-Host "   User: $($adminData.data.user.name)" -ForegroundColor White
    Write-Host "   Role: $($adminData.data.user.role)" -ForegroundColor White
    Write-Host "   Email: $($adminData.data.user.email)" -ForegroundColor White
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "   FAILED - Status: $statusCode" -ForegroundColor Red
    
    try {
        $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Error: $($errorResponse.message)" -ForegroundColor Yellow
        
        if ($errorResponse.error) {
            Write-Host "   Details: $($errorResponse.error)" -ForegroundColor Gray
        }
    } catch {
        Write-Host "   Raw error: $($_.Exception.Message)" -ForegroundColor Gray
    }
}

Write-Host ""

# Test 3: Try login with sample user
Write-Host "3. Testing login with sample22@gmail.com..." -ForegroundColor Yellow

$sampleBody = @{
    email = "sample22@gmail.com"
    password = "1234567890"
} | ConvertTo-Json

try {
    $sampleResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login-with-password" `
        -Method Post `
        -Body $sampleBody `
        -ContentType "application/json" `
        -TimeoutSec 10
    
    $sampleData = $sampleResponse.Content | ConvertFrom-Json
    Write-Host "   SUCCESS!" -ForegroundColor Green
    Write-Host "   User: $($sampleData.data.user.name)" -ForegroundColor White
    Write-Host "   Role: $($sampleData.data.user.role)" -ForegroundColor White
    Write-Host "   Email: $($sampleData.data.user.email)" -ForegroundColor White
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "   FAILED - Status: $statusCode" -ForegroundColor Red
    
    try {
        $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "   Error: $($errorResponse.message)" -ForegroundColor Yellow
        
        if ($errorResponse.error) {
            Write-Host "   Details: $($errorResponse.error)" -ForegroundColor Gray
        }
    } catch {
        Write-Host "   Raw error: $($_.Exception.Message)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "If both tests failed with 401:" -ForegroundColor Yellow
Write-Host "  - Users may not exist in Firebase Auth" -ForegroundColor Gray
Write-Host "  - Passwords may be incorrect" -ForegroundColor Gray
Write-Host "  - Check Firebase Console: https://console.firebase.google.com/" -ForegroundColor Gray
Write-Host "  - Go to Authentication > Users to verify" -ForegroundColor Gray

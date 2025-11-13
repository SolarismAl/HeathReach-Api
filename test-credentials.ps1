# Test login with provided credentials

$url = "http://localhost:8000/api/auth/login-with-password"

$credentials = @(
    @{
        email = "admin@healthreach.com"
        password = "admin1234"
        label = "Admin User"
    },
    @{
        email = "sample22@gmail.com"
        password = "1234567890"
        label = "Sample User"
    }
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing Backend-First Authentication" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

foreach ($cred in $credentials) {
    Write-Host "Testing: $($cred.label)" -ForegroundColor Yellow
    Write-Host "Email: $($cred.email)" -ForegroundColor Gray
    Write-Host "URL: $url" -ForegroundColor Gray
    Write-Host ""
    
    $body = @{
        email = $cred.email
        password = $cred.password
    } | ConvertTo-Json
    
    try {
        $response = Invoke-RestMethod -Uri $url -Method Post -Body $body -ContentType "application/json" -TimeoutSec 30
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        Write-Host ""
        
        if ($response.success) {
            Write-Host "Login Details:" -ForegroundColor Cyan
            Write-Host "  User ID: $($response.data.user.user_id)" -ForegroundColor White
            Write-Host "  Name: $($response.data.user.name)" -ForegroundColor White
            Write-Host "  Email: $($response.data.user.email)" -ForegroundColor White
            Write-Host "  Role: $($response.data.user.role)" -ForegroundColor White
            Write-Host "  Token Length: $($response.data.token.Length) chars" -ForegroundColor Gray
            Write-Host "  Token Preview: $($response.data.token.Substring(0, [Math]::Min(50, $response.data.token.Length)))..." -ForegroundColor Gray
            
            if ($response.data.firebase_token) {
                Write-Host "  Firebase Token: Present ✓" -ForegroundColor Green
            }
        } else {
            Write-Host "⚠️  Response success=false" -ForegroundColor Yellow
            Write-Host "Message: $($response.message)" -ForegroundColor Yellow
        }
        
    } catch {
        Write-Host "❌ ERROR!" -ForegroundColor Red
        Write-Host ""
        
        if ($_.Exception.Response) {
            $statusCode = $_.Exception.Response.StatusCode.value__
            Write-Host "Status Code: $statusCode" -ForegroundColor Red
            
            if ($statusCode -eq 401) {
                Write-Host "Reason: Invalid credentials or user does not exist in Firebase" -ForegroundColor Yellow
            } elseif ($statusCode -eq 500) {
                Write-Host "Reason: Server error - check backend logs" -ForegroundColor Yellow
            }
        }
        
        Write-Host "Error Message: $($_.Exception.Message)" -ForegroundColor Red
        
        if ($_.ErrorDetails.Message) {
            try {
                $errorData = $_.ErrorDetails.Message | ConvertFrom-Json
                Write-Host ""
                Write-Host "Response Body:" -ForegroundColor Yellow
                Write-Host "  Message: $($errorData.message)" -ForegroundColor White
                if ($errorData.error) {
                    Write-Host "  Error: $($errorData.error)" -ForegroundColor White
                }
            } catch {
                Write-Host "Raw Response: $($_.ErrorDetails.Message)" -ForegroundColor Gray
            }
        }
    }
    
    Write-Host ""
    Write-Host "----------------------------------------" -ForegroundColor Gray
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

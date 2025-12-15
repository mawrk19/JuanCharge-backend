# Test Dashboard Stats Endpoint (PowerShell)
# This script tests the /api/dashboard/stats endpoint

$BASE_URL = "http://localhost:8000"

Write-Host "=== Testing Dashboard Stats Endpoint ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Login to get token
Write-Host "1. Logging in as LGU admin..." -ForegroundColor Yellow

$loginBody = @{
    email = "admin@juancharge.com"
    password = "admin123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$BASE_URL/api/auth/login" `
        -Method Post `
        -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} `
        -Body $loginBody
    
    $token = $loginResponse.token
    Write-Host "✅ Successfully authenticated" -ForegroundColor Green
    Write-Host "Token: $($token.Substring(0, [Math]::Min(20, $token.Length)))..." -ForegroundColor Gray
} catch {
    Write-Host "❌ Failed to authenticate with admin@juancharge.com" -ForegroundColor Red
    Write-Host "Trying alternative credentials..." -ForegroundColor Yellow
    
    $loginBody = @{
        email = "admin@example.com"
        password = "password123"
    } | ConvertTo-Json
    
    try {
        $loginResponse = Invoke-RestMethod -Uri "$BASE_URL/api/auth/login" `
            -Method Post `
            -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} `
            -Body $loginBody
        
        $token = $loginResponse.token
        Write-Host "✅ Successfully authenticated with test admin" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to authenticate. Please check credentials." -ForegroundColor Red
        exit 1
    }
}

Write-Host ""

# Step 2: Test dashboard stats endpoint
Write-Host "2. Testing GET /api/dashboard/stats..." -ForegroundColor Yellow

try {
    $statsResponse = Invoke-RestMethod -Uri "$BASE_URL/api/dashboard/stats" `
        -Method Get `
        -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"}
    
    Write-Host "Dashboard Stats Response:" -ForegroundColor Cyan
    $statsResponse | ConvertTo-Json -Depth 10 | Write-Host
    Write-Host ""
    
    # Step 3: Verify response structure
    Write-Host "3. Verifying response structure..." -ForegroundColor Yellow
    
    $data = $statsResponse.data
    
    if ($data.PSObject.Properties.Name -contains "total_recyclables_kg") {
        Write-Host "✅ total_recyclables_kg present: $($data.total_recyclables_kg) kg" -ForegroundColor Green
    } else {
        Write-Host "❌ total_recyclables_kg missing" -ForegroundColor Red
    }
    
    if ($data.PSObject.Properties.Name -contains "total_energy_kwh") {
        Write-Host "✅ total_energy_kwh present: $($data.total_energy_kwh) kWh" -ForegroundColor Green
    } else {
        Write-Host "❌ total_energy_kwh missing" -ForegroundColor Red
    }
    
    if ($data.PSObject.Properties.Name -contains "co2_saved_kg") {
        Write-Host "✅ co2_saved_kg present: $($data.co2_saved_kg) kg" -ForegroundColor Green
        
        # Verify CO2 calculation
        $expectedCO2 = [math]::Round($data.total_energy_kwh * 0.5, 2)
        if ($data.co2_saved_kg -eq $expectedCO2) {
            Write-Host "✅ CO2 calculation correct (energy × 0.5)" -ForegroundColor Green
        } else {
            Write-Host "⚠️  CO2 calculation mismatch. Expected: $expectedCO2, Got: $($data.co2_saved_kg)" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ co2_saved_kg missing" -ForegroundColor Red
    }
    
    if ($data.PSObject.Properties.Name -contains "online_kiosks_count") {
        Write-Host "✅ online_kiosks_count present: $($data.online_kiosks_count) kiosks" -ForegroundColor Green
    } else {
        Write-Host "❌ online_kiosks_count missing" -ForegroundColor Red
    }
    
} catch {
    Write-Host "❌ Failed to get dashboard stats: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan

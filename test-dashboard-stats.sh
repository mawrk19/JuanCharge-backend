#!/bin/bash

# Test Dashboard Stats Endpoint
# This script tests the /api/dashboard/stats endpoint

BASE_URL="http://localhost:8000"

echo "=== Testing Dashboard Stats Endpoint ==="
echo ""

# Step 1: Login to get token
echo "1. Logging in as LGU admin..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@juancharge.com","password":"admin123"}')

echo "Login Response: $LOGIN_RESPONSE"
echo ""

# Extract token from response
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | grep -o '[^"]*$')

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get authentication token"
    echo "Trying alternative credentials..."
    
    # Try test admin
    LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{"email":"admin@example.com","password":"password123"}')
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | grep -o '[^"]*$')
fi

if [ -z "$TOKEN" ]; then
    echo "❌ Still failed to authenticate. Please check credentials."
    exit 1
fi

echo "✅ Successfully authenticated"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Step 2: Test dashboard stats endpoint
echo "2. Testing GET /api/dashboard/stats..."
STATS_RESPONSE=$(curl -s -X GET "$BASE_URL/api/dashboard/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Dashboard Stats Response:"
echo "$STATS_RESPONSE" | python -m json.tool 2>/dev/null || echo "$STATS_RESPONSE"
echo ""

# Step 3: Verify response structure
echo "3. Verifying response structure..."
if echo "$STATS_RESPONSE" | grep -q "total_recyclables_kg"; then
    echo "✅ total_recyclables_kg present"
else
    echo "❌ total_recyclables_kg missing"
fi

if echo "$STATS_RESPONSE" | grep -q "total_energy_kwh"; then
    echo "✅ total_energy_kwh present"
else
    echo "❌ total_energy_kwh missing"
fi

if echo "$STATS_RESPONSE" | grep -q "co2_saved_kg"; then
    echo "✅ co2_saved_kg present"
else
    echo "❌ co2_saved_kg missing"
fi

if echo "$STATS_RESPONSE" | grep -q "online_kiosks_count"; then
    echo "✅ online_kiosks_count present"
else
    echo "❌ online_kiosks_count missing"
fi

echo ""
echo "=== Test Complete ==="

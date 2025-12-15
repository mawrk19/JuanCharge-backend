#!/bin/bash

BASE_URL="https://juancharge-backend-production.up.railway.app/api/mobile"

echo "========================================"
echo "Testing Mobile Authentication Endpoints"
echo "========================================"
echo ""

# Test 1: Mobile Login
echo "TEST 1: Mobile Login"
echo "POST $BASE_URL/auth/login"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"mobile@test.com","password":"password"}')

echo "$LOGIN_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$LOGIN_RESPONSE"
echo ""

# Extract tokens if login successful
DEVICE_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"device_token":"[^"]*"' | cut -d'"' -f4)
API_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"api_token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$DEVICE_TOKEN" ]; then
    echo "✓ Device token extracted: ${DEVICE_TOKEN:0:20}..."
    echo "✓ API token extracted: ${API_TOKEN:0:20}..."
    echo ""
    
    # Test 2: Auto-Login
    echo "TEST 2: Auto-Login with Device Token"
    echo "POST $BASE_URL/auth/auto-login"
    AUTO_LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/auto-login" \
      -H "Content-Type: application/json" \
      -d "{\"device_token\":\"$DEVICE_TOKEN\"}")
    
    echo "$AUTO_LOGIN_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$AUTO_LOGIN_RESPONSE"
    echo ""
    
    # Extract new API token
    NEW_API_TOKEN=$(echo "$AUTO_LOGIN_RESPONSE" | grep -o '"api_token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$NEW_API_TOKEN" ]; then
        echo "✓ New API token from auto-login: ${NEW_API_TOKEN:0:20}..."
        echo ""
        
        # Test 3: Logout
        echo "TEST 3: Mobile Logout"
        echo "POST $BASE_URL/auth/logout"
        LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/logout" \
          -H "Authorization: Bearer $NEW_API_TOKEN" \
          -H "Content-Type: application/json")
        
        echo "$LOGOUT_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$LOGOUT_RESPONSE"
        echo ""
        
        # Test 4: Try auto-login after logout (should fail)
        echo "TEST 4: Auto-Login After Logout (Should Fail)"
        echo "POST $BASE_URL/auth/auto-login"
        FAILED_LOGIN=$(curl -s -X POST "$BASE_URL/auth/auto-login" \
          -H "Content-Type: application/json" \
          -d "{\"device_token\":\"$DEVICE_TOKEN\"}")
        
        echo "$FAILED_LOGIN" | python3 -m json.tool 2>/dev/null || echo "$FAILED_LOGIN"
        echo ""
    else
        echo "✗ Auto-login failed"
    fi
else
    echo "✗ Login failed or no device token returned"
fi

echo "========================================"
echo "Test Complete"
echo "========================================"

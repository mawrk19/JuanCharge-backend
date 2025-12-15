#!/bin/bash

# Quick test: Register a new user and login via mobile endpoints
# This bypasses CORS since it's server-to-server

BASE_URL="https://juancharge-backend-production.up.railway.app/api"

echo "=========================================="
echo "Quick Mobile Auth Test (Bypass CORS)"
echo "=========================================="
echo ""

# Register a new test user
echo "1. Registering new test user..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Quick Test User",
    "first_name":"Quick",
    "last_name":"Test",
    "email":"quicktest@example.com",
    "password":"testpass123",
    "password_confirmation":"testpass123",
    "contact_number":"09111222333"
  }')

echo "$REGISTER_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$REGISTER_RESPONSE"
echo ""

# Wait a moment
sleep 2

# Try mobile login
echo "2. Testing mobile login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/mobile/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email":"quicktest@example.com",
    "password":"testpass123"
  }')

echo "$LOGIN_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$LOGIN_RESPONSE"
echo ""

# Check if we got a device_token
if echo "$LOGIN_RESPONSE" | grep -q "device_token"; then
    echo "✓ Mobile auth working! Device token received."
    
    DEVICE_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"device_token":"[^"]*"' | cut -d'"' -f4)
    echo "Device Token (first 30 chars): ${DEVICE_TOKEN:0:30}..."
    echo ""
    
    # Test auto-login
    echo "3. Testing auto-login..."
    AUTO_LOGIN=$(curl -s -X POST "$BASE_URL/mobile/auth/auto-login" \
      -H "Content-Type: application/json" \
      -d "{\"device_token\":\"$DEVICE_TOKEN\"}")
    
    echo "$AUTO_LOGIN" | python3 -m json.tool 2>/dev/null || echo "$AUTO_LOGIN"
    echo ""
    
    if echo "$AUTO_LOGIN" | grep -q "Auto-login successful"; then
        echo "✓✓✓ ALL TESTS PASSED! Mobile auth is working correctly."
        echo ""
        echo "The CORS issue is only affecting browser requests from localhost."
        echo "Set CORS_ALLOWED_ORIGINS on Railway to fix browser access."
    fi
else
    echo "✗ Login failed. Check response above."
fi

echo ""
echo "=========================================="

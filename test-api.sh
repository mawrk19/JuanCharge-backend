#!/bin/bash

echo "=== Testing JuanCharge Backend API ==="
echo ""

# Base URL
BASE_URL="https://juancharge-backend-production.up.railway.app"

echo "1. Testing if server is reachable..."
curl --max-time 5 -s -o /dev/null -w "HTTP Status: %{http_code}\n" $BASE_URL || echo "❌ Server not reachable"
echo ""

echo "2. Testing API root..."
curl --max-time 5 -s -o /dev/null -w "HTTP Status: %{http_code}\n" $BASE_URL/api || echo "❌ API not responding"
echo ""

echo "3. Testing OPTIONS request (CORS preflight)..."
curl --max-time 5 -X OPTIONS $BASE_URL/api/auth/login \
  -H "Origin: https://juan-charge-client-1ang.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -i 2>&1 | head -20
echo ""

echo "4. Testing POST /api/auth/login..."
curl --max-time 5 -X POST $BASE_URL/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: https://juan-charge-client-1ang.vercel.app" \
  -d '{"email":"test@example.com","password":"password123"}' \
  -i 2>&1 | head -30
echo ""

echo "5. Testing GET /api/kiosks (should require auth)..."
curl --max-time 5 -X GET $BASE_URL/api/kiosks \
  -H "Accept: application/json" \
  -H "Origin: https://juan-charge-client-1ang.vercel.app" \
  -i 2>&1 | head -20
echo ""

echo "=== Test Complete ==="

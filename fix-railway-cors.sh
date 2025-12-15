#!/bin/bash

# Quick fix: Add localhost to Railway CORS
# Run this if you have Railway CLI installed

echo "Setting CORS_ALLOWED_ORIGINS on Railway..."
railway variables set CORS_ALLOWED_ORIGINS="https://juan-charge-client-1ang.vercel.app,https://juancharge-client.vercel.app,http://localhost:5173,http://localhost:3000,http://127.0.0.1:5173,http://localhost:8080"

echo ""
echo "✓ Railway environment variable updated!"
echo ""
echo "Now trigger a redeploy:"
echo "  1. Via Dashboard: Railway → Your Service → Click 'Restart'"
echo "  2. Or push any commit to trigger redeploy"
echo ""
echo "After redeploy, test with:"
echo "  curl -i -X OPTIONS 'https://juancharge-backend-production.up.railway.app/api/mobile/auth/login' -H 'Origin: http://localhost:5173' | grep Access-Control-Allow-Origin"

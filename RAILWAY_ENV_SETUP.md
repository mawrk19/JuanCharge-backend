# Railway Environment Variables Setup

## Go to: Railway Dashboard → Your Project → Variables Tab

Add or update these environment variables:

```env
# App Configuration
APP_URL=https://juancharge-backend-production.up.railway.app
APP_ENV=production
APP_DEBUG=false

# CORS Configuration (IMPORTANT!)
CORS_ALLOWED_ORIGINS=https://juan-charge-client-1ang.vercel.app,https://juancharge-client.vercel.app,http://localhost:3000,http://localhost:5173

# Sanctum Configuration (IMPORTANT!)
SANCTUM_STATEFUL_DOMAINS=juan-charge-client-1ang.vercel.app,juancharge-client.vercel.app,localhost:3000,localhost:5173
SESSION_DOMAIN=.vercel.app

# Frontend URL
FRONTEND_URL=https://juan-charge-client-1ang.vercel.app
```

## Steps to Apply:

1. Go to Railway Dashboard: https://railway.app
2. Select your `juancharge-backend-production` project
3. Click on the service
4. Go to "Variables" tab
5. Add each variable above
6. Click "Deploy" or wait for auto-deploy

## After Deploy:

Railway will automatically restart your backend with the new environment variables.

## Test CORS:

Run this command to verify CORS headers:

```bash
curl -I -X OPTIONS https://juancharge-backend-production.up.railway.app/api/auth/login \
  -H "Origin: https://juan-charge-client-1ang.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization"
```

You should see in the response:
- `Access-Control-Allow-Origin: https://juan-charge-client-1ang.vercel.app`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- Status: `204 No Content`


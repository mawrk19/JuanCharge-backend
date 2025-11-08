# 🚨 CORRECTED Railway Environment Variables

## Critical Fix Required

**PROBLEM:** Your Railway `.env` has:
- `APP_URL=https://juancharge-production.up.railway.app` ❌ (404 - app not found)
- `VITE_API_BASE_URL` and other frontend vars ❌ (these belong in Vercel, not backend)

**SOLUTION:** Use the domain below and remove frontend vars.

---

## ✅ Copy-Paste These into Railway Dashboard → Variables

```env
# ===== CORE LARAVEL =====
APP_NAME=JuanCharge
APP_ENV=production
APP_KEY=base64:LjLFUxKnYc4z4kzlYWLBFCDjaP8isrdrn3umKkWCxt4=
APP_DEBUG=false
APP_URL=https://juancharge-backend-production.up.railway.app

# ===== LOGGING =====
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ===== DATABASE =====
DB_CONNECTION=mysql
DB_HOST=crossover.proxy.rlwy.net
DB_PORT=29084
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=VlyQzEzIyLGHHxlCrZkmxJAZyvPdyVCW

# ===== CACHING / QUEUE / SESSION =====
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# ===== REDIS (not used but required) =====
MEMCACHED_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===== MAIL (SENDGRID) - REVOKE THIS KEY! =====
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_NEW_SENDGRID_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=juancharge.juanlove@gmail.com
MAIL_FROM_NAME=JuanCharge

# ===== AWS (not used) =====
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ===== PUSHER (optional) =====
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
MIX_PUSHER_APP_KEY=${PUSHER_APP_KEY}
MIX_PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER}

# ===== JWT =====
JWT_SECRET=ajMgjXRN8eJfshoCkD6A9uWwg7G1gyJCJmowudSLjAdFZdPwAYEntzKP7fjHTKFb

# ===== FRONTEND / CORS CONFIG =====
FRONTEND_URL=https://juan-charge-client-1ang.vercel.app
CORS_ALLOWED_ORIGINS=https://juan-charge-client-1ang.vercel.app,https://juancharge-client.vercel.app,https://juan-charge-client-1ang-git-core-dev-mark-acedos-projects.vercel.app,https://juan-charge-client-1ang-4ow22przf-mark-acedos-projects.vercel.app

# ===== SANCTUM (for cookie-based auth if needed later) =====
SANCTUM_STATEFUL_DOMAINS=juan-charge-client-1ang.vercel.app,juancharge-client.vercel.app,localhost:3000
SESSION_DOMAIN=.vercel.app
```

---

## ❌ REMOVE These Variables from Railway (Frontend-Only)

Delete these from your Railway environment variables:
- `VITE_APP_NAME`
- `VITE_API_BASE_URL`
- `VITE_BACKEND_URL`
- `API_BASE_URL`
- `BACKEND_URL`

---

## 🔴 CRITICAL SECURITY: SendGrid API Key

Your SendGrid key `SG.-dGHfbKeT76jbHokqjPpcA.***` has been exposed multiple times. 

**IMMEDIATE ACTION REQUIRED:**
1. Go to https://app.sendgrid.com/settings/api_keys
2. Delete the exposed key
3. Create a new API key
4. Update `MAIL_PASSWORD` in Railway with the new key

---

## ✅ Next Step After Updating Railway

Railway will auto-redeploy when you save the environment variables. Wait 2-3 minutes, then test:

```bash
curl -I -X OPTIONS "https://juancharge-backend-production.up.railway.app/api/auth/login" \
  -H "Origin: https://juan-charge-client-1ang.vercel.app"
```

You should see:
```
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://juan-charge-client-1ang.vercel.app
```

---

## 📝 Key Changes Made

| Variable | Old Value | New Value | Why |
|----------|-----------|-----------|-----|
| `APP_URL` | `juancharge-production.up.railway.app` | `juancharge-backend-production.up.railway.app` | Old domain returns 404 |
| `LOG_LEVEL` | `debug` | `error` | Production best practice |
| `CORS_ALLOWED_ORIGINS` | 2 domains | 4 domains | Added Vercel preview URLs |
| Frontend vars | Present | **REMOVED** | Don't belong in backend |

---

**After Railway redeploys, update Vercel environment variables (next file).**

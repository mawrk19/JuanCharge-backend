# ✅ Vercel Environment Variables (Frontend)

## Go to: Vercel Dashboard → Your Project → Settings → Environment Variables

**Add these for ALL environments** (Production, Preview, Development):

```env
VITE_API_BASE_URL=https://juancharge-backend-production.up.railway.app/api
```

That's it! Just one variable needed.

---

## Optional (if you use these in your code):

```env
VITE_APP_NAME=JuanCharge
VITE_BACKEND_URL=https://juancharge-backend-production.up.railway.app
```

---

## ⚠️ Important Notes

1. **Use the correct backend domain:** `juancharge-backend-production.up.railway.app` (NOT `juancharge-production.up.railway.app`)

2. **After adding/changing env vars:** Redeploy your frontend
   - Go to Deployments tab
   - Click "..." on latest deployment
   - Click "Redeploy"

3. **Verify in your frontend code** (`src/api/http.js` or similar):
   ```javascript
   const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
   ```
   Should resolve to: `https://juancharge-backend-production.up.railway.app/api`

4. **Test in browser console** after deploy:
   ```javascript
   console.log(import.meta.env.VITE_API_BASE_URL);
   // Should print: https://juancharge-backend-production.up.railway.app/api
   ```

---

## 🧪 Test After Vercel Redeploy

1. Open your Vercel app: `https://juan-charge-client-1ang.vercel.app`
2. Open DevTools (F12) → Network tab
3. Try to login
4. Check request URL should be: `https://juancharge-backend-production.up.railway.app/api/auth/login`
5. Response should include: `Access-Control-Allow-Origin` header

---

**Status:** Ready to copy-paste into Vercel Dashboard!

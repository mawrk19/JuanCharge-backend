# Railway Backend Troubleshooting Checklist

## Current Issue: Server Not Responding (Timeout)

Your Railway backend is timing out on all requests. This means the app is either:
1. Not starting at all
2. Crashing during startup
3. Not listening on the correct PORT

## Check Railway Logs

### Where to find logs:
1. Railway Dashboard → Your Project → Backend Service
2. Click "Deployments" tab
3. Click the latest deployment
4. Check both "Build Logs" and "Deploy Logs"

### What to look for:

#### ❌ Build Failed
```
ERROR: composer install failed
ERROR: php artisan config:cache failed
```
**Solution:** Check if all dependencies are compatible with PHP 8.2

#### ❌ Database Connection Failed
```
SQLSTATE[HY000] [2002] Connection refused
PDOException: SQLSTATE[HY000] [1045] Access denied
```
**Solution:** Verify database credentials in Railway environment variables

#### ❌ Missing APP_KEY
```
RuntimeException: No application encryption key has been specified
```
**Solution:** Generate new APP_KEY: `php artisan key:generate --show`

#### ❌ Port Binding Issue
```
Failed to listen on 0.0.0.0:8080
Address already in use
```
**Solution:** Check if Procfile uses `${PORT}` variable correctly

#### ❌ Migration Failed
```
SQLSTATE[42S01]: Base table or view already exists
Migration failed
```
**Solution:** Use `php artisan migrate:fresh --force` carefully or fix migrations

## Quick Fixes to Try

### Fix 1: Simplify Procfile (Remove Migrations)
Edit `Procfile` to just start the server:
```
web: php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
```

Then run migrations manually in Railway CLI:
```bash
railway run php artisan migrate --force
```

### Fix 2: Check Environment Variables
Required variables in Railway:
- ✅ `APP_KEY` (must be set)
- ✅ `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- ✅ `APP_ENV=production`
- ✅ `APP_DEBUG=false` (or `true` temporarily for debugging)

### Fix 3: Enable Debug Mode Temporarily
In Railway environment variables, set:
```
APP_DEBUG=true
LOG_LEVEL=debug
```

This will show detailed error messages in logs.

### Fix 4: Check PHP Version
Your app uses PHP 8.2, ensure Railway is using the correct version.

In `nixpacks.toml`:
```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer"]
```

### Fix 5: Test Database Connection
Create a simple test endpoint to verify database works:

Add to `routes/api.php`:
```php
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'failed',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

### Fix 6: Simplify nixpacks.toml
Remove caching commands that might fail:

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer"]

[phases.install]
cmds = [
  "composer install --no-dev --optimize-autoloader"
]

[phases.build]
cmds = []

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=$PORT"
```

## Most Likely Issues (In Order of Probability)

### 1. 🔴 Database Connection Failed (90% likely)
Your Railway MySQL service might not be properly linked or credentials are wrong.

**Test:** Check Railway logs for database errors

**Fix:** 
- Verify MySQL service is running
- Check if database credentials are correct
- Ensure services are in same Railway project
- Try connecting with Railway's `DATABASE_URL` instead

### 2. 🟠 APP_KEY Not Set (50% likely)
Laravel requires APP_KEY to start.

**Fix:** 
```bash
# Generate locally
php artisan key:generate --show

# Copy the output and add to Railway environment variables
APP_KEY=base64:your_generated_key_here
```

### 3. 🟡 Composer Dependencies Failed (30% likely)
Some package might be incompatible with PHP 8.2 or Railway environment.

**Fix:** Check build logs for composer errors

### 4. 🟢 Port Binding Issue (10% likely)
App not using Railway's PORT variable.

**Fix:** Ensure using `${PORT}` in Procfile

## Immediate Actions

1. **Check Railway Deploy Logs** - This will tell you exactly what's wrong
2. **Enable APP_DEBUG=true** - Get detailed error messages
3. **Simplify Procfile** - Remove migrations, just start server
4. **Verify DATABASE_URL** - Make sure MySQL is connected
5. **Test locally** - Run `php artisan serve` locally to verify app works

## If All Else Fails

Deploy a minimal test to see if basic PHP works:

Create `public/test.php`:
```php
<?php
phpinfo();
```

If this works, problem is with Laravel. If this doesn't work, problem is with Railway/PHP setup.

## Need the Actual Error?

**Share the Railway deployment logs here** and I can tell you exactly what's wrong!

Look for:
- Red error messages
- Stack traces
- Database connection errors
- "Failed", "Error", "Exception" keywords

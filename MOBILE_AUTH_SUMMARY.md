# Mobile Authentication - Backend Implementation Summary

## 🎯 Overview
New mobile-specific authentication system implemented for patron (kiosk) users with **persistent login** functionality. Users login once and stay logged in for 90 days without re-entering credentials.

---

## ✅ What's Been Implemented

### 1. Database Changes
- **Migration**: `2025_11_08_174035_add_device_token_to_kiosk_users_table.php`
- **New Columns in `kiosk_users` table**:
  - `device_token` (text, nullable) - Stores hashed device token for persistent auth
  - `token_expires_at` (timestamp, nullable) - Token expiration date (90 days)

### 2. New API Endpoints

#### Base URL: `/api/mobile/auth/`

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/login` | POST | ❌ No | Initial login - returns device_token & api_token |
| `/auto-login` | POST | ❌ No | Auto-login using device_token |
| `/logout` | POST | ✅ Yes | Logout and clear device token |
| `/refresh-token` | POST | ❌ No | Extend token expiration by 90 days |

### 3. Test Users Created (Seeder)
Run this on Railway to create test users:
```bash
php artisan db:seed --class=TestMobileUserSeeder
```

**Test Accounts:**
1. **mobile@test.com** / password (Complete profile, 1500 points)
2. **patron@test.com** / password (Incomplete profile - for testing profile prompts)
3. **vip@test.com** / password (VIP user with 10000 points)

---

## 🔄 Authentication Flow

### First Time Login
```
User Input (Email + Password)
        ↓
   POST /api/mobile/auth/login
        ↓
Backend validates credentials
        ↓
Returns: { device_token, api_token, user }
        ↓
Frontend stores device_token securely (SecureStore/Keychain)
```

### Subsequent App Launches (Auto-Login)
```
App Launch
        ↓
Check for stored device_token
        ↓
   POST /api/mobile/auth/auto-login
        ↓
Backend validates device_token
        ↓
Returns: { api_token, user }
        ↓
User is logged in automatically
```

### Logout
```
User clicks Logout
        ↓
   POST /api/mobile/auth/logout (with Bearer token)
        ↓
Backend clears device_token
        ↓
Frontend clears stored tokens
        ↓
Redirect to login screen
```

---

## 📋 API Request/Response Examples

### 1. Mobile Login

**Request:**
```javascript
POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/login
Content-Type: application/json

{
  "email": "mobile@test.com",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Mobile Test User",
    "first_name": "Mobile",
    "last_name": "Tester",
    "email": "mobile@test.com",
    "contact_number": "09123456789",
    "points_balance": 1500,
    "points_total": 3000,
    "points_used": 1500
  },
  "device_token": "xJ9k2mL5nP8qR3sT6vW9yA1bC4dE7fG0hI3jK6mL9nP2qR5sT8vW1xY4zA7bC0dE3fG6hI9j",
  "api_token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890...",
  "token_expires_at": "2026-02-06T17:40:00.000000Z",
  "should_update_profile": false,
  "prompt_message": null
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

**What to do with response:**
- ✅ Store `device_token` in secure storage (never in plain AsyncStorage!)
- ✅ Store `api_token` for API requests
- ✅ Save user data to app state/context
- ✅ If `should_update_profile: true`, show profile completion prompt

---

### 2. Auto-Login

**Request:**
```javascript
POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/auto-login
Content-Type: application/json

{
  "device_token": "xJ9k2mL5nP8qR3sT6vW9yA1bC4dE7fG0hI3jK6mL9nP2qR5sT8vW1xY4zA7bC0dE3fG6hI9j"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Auto-login successful",
  "user": {
    "id": 1,
    "name": "Mobile Test User",
    "first_name": "Mobile",
    "last_name": "Tester",
    "email": "mobile@test.com",
    "contact_number": "09123456789",
    "points_balance": 1500,
    "points_total": 3000,
    "points_used": 1500
  },
  "api_token": "2|XyZaBcDeFgHiJkLmNoPqRsTuVwXy1234567890...",
  "token_expires_at": "2026-02-06T17:40:00.000000Z",
  "should_update_profile": false,
  "prompt_message": null
}
```

**Error Response (401 - Token Expired/Invalid):**
```json
{
  "success": false,
  "message": "Invalid or expired device token",
  "requires_login": true
}
```

**What to do with response:**
- ✅ On success: Update `api_token` and user data, navigate to Home
- ❌ On error with `requires_login: true`: Clear all stored tokens, redirect to Login screen

---

### 3. Logout

**Request:**
```javascript
POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/logout
Authorization: Bearer {api_token}
Content-Type: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**What to do after:**
- ✅ Clear `device_token` from secure storage
- ✅ Clear `api_token` from storage
- ✅ Clear user data from app state
- ✅ Navigate to Login screen

---

## 💻 Frontend Implementation Guide

### React Native / Expo Example

#### 1. Login Function
```javascript
import * as SecureStore from 'expo-secure-store';

const API_BASE = 'https://juancharge-backend-production.up.railway.app/api/mobile';

async function login(email, password) {
  try {
    const response = await fetch(`${API_BASE}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store tokens securely
      await SecureStore.setItemAsync('device_token', data.device_token);
      await SecureStore.setItemAsync('api_token', data.api_token);
      
      // Save user data (e.g., Context, Redux, Zustand)
      setUser(data.user);
      
      // Check if profile update needed
      if (data.should_update_profile) {
        Alert.alert('Profile Incomplete', data.prompt_message);
        navigation.navigate('ProfileSetup');
      } else {
        navigation.navigate('Home');
      }
    } else {
      Alert.alert('Login Failed', data.message);
    }
  } catch (error) {
    console.error('Login error:', error);
    Alert.alert('Error', 'Unable to login. Please try again.');
  }
}
```

#### 2. Auto-Login on App Launch
```javascript
// In App.js or your root navigator
import { useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';

function App() {
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  
  useEffect(() => {
    checkAutoLogin();
  }, []);
  
  async function checkAutoLogin() {
    try {
      const deviceToken = await SecureStore.getItemAsync('device_token');
      
      if (!deviceToken) {
        // No token stored, show login screen
        setIsLoading(false);
        return;
      }
      
      // Try auto-login
      const response = await fetch(`${API_BASE}/auth/auto-login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ device_token: deviceToken })
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Update API token
        await SecureStore.setItemAsync('api_token', data.api_token);
        
        // Set user data
        setUser(data.user);
        setIsAuthenticated(true);
      } else if (data.requires_login) {
        // Token expired, clear storage
        await SecureStore.deleteItemAsync('device_token');
        await SecureStore.deleteItemAsync('api_token');
      }
    } catch (error) {
      console.error('Auto-login error:', error);
    } finally {
      setIsLoading(false);
    }
  }
  
  if (isLoading) {
    return <SplashScreen />;
  }
  
  return (
    <NavigationContainer>
      {isAuthenticated ? <AppStack /> : <AuthStack />}
    </NavigationContainer>
  );
}
```

#### 3. Making Authenticated API Requests
```javascript
async function fetchUserPoints() {
  const apiToken = await SecureStore.getItemAsync('api_token');
  
  const response = await fetch(
    'https://juancharge-backend-production.up.railway.app/api/patron/points/balance',
    {
      headers: {
        'Authorization': `Bearer ${apiToken}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  return await response.json();
}
```

#### 4. Logout Function
```javascript
async function logout() {
  try {
    const apiToken = await SecureStore.getItemAsync('api_token');
    
    // Call backend logout (optional - can skip on network error)
    await fetch(`${API_BASE}/auth/logout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${apiToken}`,
        'Content-Type': 'application/json'
      }
    });
  } catch (error) {
    // Ignore network errors during logout
    console.log('Logout API error (ignored):', error);
  } finally {
    // Always clear local storage
    await SecureStore.deleteItemAsync('device_token');
    await SecureStore.deleteItemAsync('api_token');
    setUser(null);
    navigation.navigate('Login');
  }
}
```

---

## 🔒 Security Features

1. **Token Hashing**: Device tokens are hashed with SHA-256 before storage
2. **Secure Storage**: Frontend must use secure storage (iOS Keychain, Android Keystore)
3. **Token Expiration**: Device tokens expire after 90 days
4. **API Token Refresh**: New Sanctum token generated on each auto-login
5. **Logout Clears All**: Both device_token and all API tokens revoked on logout

---

## 🧪 Testing

### Run Seeder on Railway
```bash
railway run php artisan db:seed --class=TestMobileUserSeeder
```

### Test Script
```bash
./test-mobile-auth.sh
```

### Manual cURL Tests
```bash
# Login
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"mobile@test.com","password":"password"}'

# Auto-login (replace TOKEN with device_token from login response)
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/auto-login \
  -H "Content-Type: application/json" \
  -d '{"device_token":"TOKEN"}'

# Logout (replace TOKEN with api_token)
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/logout \
  -H "Authorization: Bearer TOKEN"
```

---

## 📱 Mobile App Checklist

### Must Implement
- [ ] Install secure storage library (expo-secure-store or react-native-keychain)
- [ ] Implement login screen with email/password form
- [ ] Store device_token in secure storage after successful login
- [ ] Implement auto-login check on app launch
- [ ] Use api_token in Authorization header for all API requests
- [ ] Handle `requires_login: true` error (redirect to login)
- [ ] Implement logout functionality (clear all tokens)
- [ ] Handle `should_update_profile` flag (show profile completion prompt)

### Nice to Have
- [ ] Show token expiration countdown
- [ ] Implement token refresh before expiration
- [ ] Add biometric authentication (Face ID/Touch ID) as optional layer
- [ ] Cache user data locally for offline viewing

---

## 🚀 Deployment Status

✅ **Deployed to Railway**: https://juancharge-backend-production.up.railway.app
✅ **Migration Ready**: Run `php artisan migrate` on Railway
✅ **Seeder Ready**: Run `php artisan db:seed --class=TestMobileUserSeeder`
✅ **CORS Configured**: Mobile domains already allowed

---

## 📞 Support Endpoints

All existing patron endpoints still work with the new auth system:
- `GET /api/patron/points/balance` - Get points balance
- `GET /api/patron/points/transactions` - Get transaction history
- `GET /api/patron/dashboard/stats` - Get dashboard stats
- `POST /api/charging/redeem` - Redeem charging session
- `GET /api/charging/active` - Get active session
- `GET /api/charging/history` - Get charging history

Just use the `api_token` in the Authorization header!

---

## 🐛 Troubleshooting

### Issue: Auto-login fails with "Invalid or expired device token"
**Solution**: Device token expired (90 days). User must login again with email/password.

### Issue: API requests return 401 Unauthorized
**Solution**: api_token expired. Call `/auto-login` to get fresh token OR redirect to login.

### Issue: User logged out unexpectedly
**Solution**: Check if device_token was cleared. May need to re-authenticate.

### Issue: CORS errors from mobile app
**Solution**: CORS is already configured for mobile. Check if you're using correct domain.

---

## 📚 Additional Documentation

- Full API docs: `MOBILE_AUTH_API.md`
- Original API docs: `MOBILE_AUTH_API.md` (comprehensive guide)
- CORS configuration: `CORS_FIX_SUMMARY.md`
- Railway deployment: `RAILWAY_ENV_CORRECT.md`

---

**Implementation Date**: November 8, 2025  
**Backend Version**: Laravel 8/9  
**Auth Method**: Laravel Sanctum + Custom Device Tokens  
**Token Expiration**: 90 days  
**Status**: ✅ Ready for Integration

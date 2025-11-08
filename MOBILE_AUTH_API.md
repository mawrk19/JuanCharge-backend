# Mobile Authentication API Documentation

## Overview
Mobile-specific authentication endpoints for patron (kiosk) users that implement persistent login using device tokens. Users login once and the app stores a device token for automatic re-authentication.

## Base URL
```
Production: https://juancharge-backend-production.up.railway.app/api/mobile
```

---

## Authentication Flow

### Initial Login Flow
1. User enters email and password
2. App calls `/api/mobile/auth/login`
3. Server validates credentials and returns:
   - `device_token` - Store this securely in mobile device storage
   - `api_token` - Use this in Authorization header for API requests
4. Mobile app stores the `device_token` locally

### Auto-Login Flow (Subsequent App Launches)
1. App checks for stored `device_token`
2. If found, call `/api/mobile/auth/auto-login` with the token
3. Server validates token and returns new `api_token`
4. User is logged in without entering credentials

### Logout Flow
1. App calls `/api/mobile/auth/logout` (requires Authorization header)
2. Server clears device token and revokes API tokens
3. User must login again next time

---

## Endpoints

### 1. Mobile Login
**POST** `/api/mobile/auth/login`

#### Request Body
```json
{
  "email": "patron@example.com",
  "password": "password123"
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "patron@example.com",
    "contact_number": "09123456789",
    "points_balance": 1500,
    "points_total": 3000,
    "points_used": 1500
  },
  "device_token": "xJ9k2mL5nP8qR3sT6vW9yA1bC4dE7fG0hI3jK6mL9nP2qR5sT8vW1xY4zA7bC0dE",
  "api_token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "token_expires_at": "2026-02-06T17:40:00.000000Z",
  "should_update_profile": false,
  "prompt_message": null
}
```

#### Error Response (401)
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

**Important:** 
- Store `device_token` securely in device storage (e.g., SecureStore, Keychain)
- Use `api_token` in Authorization header for all subsequent API requests
- Token expires in 90 days

---

### 2. Auto-Login (Token Validation)
**POST** `/api/mobile/auth/auto-login`

Use this endpoint when the app launches to automatically log the user in using their stored device token.

#### Request Body
```json
{
  "device_token": "xJ9k2mL5nP8qR3sT6vW9yA1bC4dE7fG0hI3jK6mL9nP2qR5sT8vW1xY4zA7bC0dE"
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Auto-login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "patron@example.com",
    "contact_number": "09123456789",
    "points_balance": 1500,
    "points_total": 3000,
    "points_used": 1500
  },
  "api_token": "2|XyZaBcDeFgHiJkLmNoPqRsTuVw...",
  "token_expires_at": "2026-02-06T17:40:00.000000Z",
  "should_update_profile": false,
  "prompt_message": null
}
```

#### Error Response (401 - Token Invalid/Expired)
```json
{
  "success": false,
  "message": "Invalid or expired device token",
  "requires_login": true
}
```

**When you receive `requires_login: true`:**
- Clear stored device_token
- Redirect user to login screen
- User must login again with email/password

---

### 3. Mobile Logout
**POST** `/api/mobile/auth/logout`

**Requires Authorization:** Bearer token in header

#### Headers
```
Authorization: Bearer {api_token}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**After logout:**
- Clear stored `device_token` from device
- Clear stored `api_token` from device
- Redirect user to login screen

---

### 4. Refresh Device Token (Optional)
**POST** `/api/mobile/auth/refresh-token`

Extends the device token expiration by another 90 days.

#### Request Body
```json
{
  "device_token": "xJ9k2mL5nP8qR3sT6vW9yA1bC4dE7fG0hI3jK6mL9nP2qR5sT8vW1xY4zA7bC0dE"
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Device token refreshed",
  "token_expires_at": "2026-02-06T17:40:00.000000Z"
}
```

---

## Mobile App Implementation Guide

### Step 1: Initial Login
```javascript
// User submits login form
const loginResponse = await fetch('https://juancharge-backend-production.up.railway.app/api/mobile/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: userEmail,
    password: userPassword
  })
});

const data = await loginResponse.json();

if (data.success) {
  // Store device token securely (e.g., AsyncStorage with encryption or SecureStore)
  await SecureStore.setItemAsync('device_token', data.device_token);
  await SecureStore.setItemAsync('api_token', data.api_token);
  
  // Navigate to home screen
  navigation.navigate('Home');
}
```

### Step 2: Auto-Login on App Launch
```javascript
// On app launch (e.g., in App.js or splash screen)
useEffect(() => {
  async function checkAutoLogin() {
    const deviceToken = await SecureStore.getItemAsync('device_token');
    
    if (deviceToken) {
      try {
        const response = await fetch('https://juancharge-backend-production.up.railway.app/api/mobile/auth/auto-login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ device_token: deviceToken })
        });
        
        const data = await response.json();
        
        if (data.success) {
          // Update stored API token
          await SecureStore.setItemAsync('api_token', data.api_token);
          
          // User is logged in, go to home
          navigation.navigate('Home');
        } else if (data.requires_login) {
          // Token expired, clear and go to login
          await SecureStore.deleteItemAsync('device_token');
          await SecureStore.deleteItemAsync('api_token');
          navigation.navigate('Login');
        }
      } catch (error) {
        // Network error or server down, go to login
        navigation.navigate('Login');
      }
    } else {
      // No token stored, go to login
      navigation.navigate('Login');
    }
  }
  
  checkAutoLogin();
}, []);
```

### Step 3: Making API Requests
```javascript
// Use the stored api_token for all API requests
const apiToken = await SecureStore.getItemAsync('api_token');

const response = await fetch('https://juancharge-backend-production.up.railway.app/api/patron/points/balance', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'Content-Type': 'application/json'
  }
});
```

### Step 4: Logout
```javascript
async function logout() {
  const apiToken = await SecureStore.getItemAsync('api_token');
  
  try {
    await fetch('https://juancharge-backend-production.up.railway.app/api/mobile/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${apiToken}`,
        'Content-Type': 'application/json'
      }
    });
  } catch (error) {
    // Ignore network errors during logout
  }
  
  // Clear local storage
  await SecureStore.deleteItemAsync('device_token');
  await SecureStore.deleteItemAsync('api_token');
  
  // Navigate to login
  navigation.navigate('Login');
}
```

---

## Security Notes

1. **Device Token Storage**
   - Never store device tokens in plain text
   - Use secure storage (iOS Keychain, Android Keystore, Expo SecureStore)
   - Never log or expose device tokens

2. **API Token Usage**
   - API tokens are short-lived (session-based)
   - Always include in Authorization header
   - Refresh via auto-login when expired

3. **Token Expiration**
   - Device tokens expire after 90 days of inactivity
   - Check `token_expires_at` field
   - Use refresh endpoint to extend expiration

4. **Error Handling**
   - Always handle `requires_login: true` responses
   - Clear stored tokens on logout or token expiration
   - Implement proper error screens

---

## Testing with cURL

### Test Login
```bash
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

### Test Auto-Login
```bash
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/auto-login \
  -H "Content-Type: application/json" \
  -d '{"device_token":"YOUR_DEVICE_TOKEN_HERE"}'
```

### Test Logout
```bash
curl -X POST https://juancharge-backend-production.up.railway.app/api/mobile/auth/logout \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

---

## Migration Instructions

The migration has been created and must be run on Railway:

```bash
# On Railway (runs automatically during deployment)
php artisan migrate
```

Or manually trigger via Railway CLI:
```bash
railway run php artisan migrate
```

This adds the following columns to `kiosk_users` table:
- `device_token` (text, nullable) - Hashed device token for persistent auth
- `token_expires_at` (timestamp, nullable) - Token expiration date

#!/bin/bash
# test-kiosk-integration.sh

HOST="http://localhost:8000"
SECRET="default_secret_key"
KIOSK_CODE="KIOSK-001"
USER_ID="1" # Replace with valid user ID from your local DB

echo "--- 1. Testing Heartbeat ---"
curl -X POST "$HOST/api/kiosk/heartbeat" \
     -H "Content-Type: application/json" \
     -d '{
       "kiosk_code": "'"$KIOSK_CODE"'",
       "status": "online",
       "ports": [
         { "port": 1, "status": "active", "remaining_time": 300 },
         { "port": 2, "status": "idle", "remaining_time": 0 }
       ]
     }'
echo -e "\n"

echo "--- 2. Testing Redemption (Signed) ---"
POINTS=5
TIMESTAMP=$(date +%s%3N) # MS timestamp
PAYLOAD="${KIOSK_CODE}${USER_ID}${POINTS}${TIMESTAMP}"

# Calculate HMAC SHA256 signature (Requires openssl)
# Note: echo -n to avoid newline
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

echo "Payload: $PAYLOAD"
echo "Signature: $SIGNATURE"

curl -X POST "$HOST/api/kiosk/redeem" \
     -H "Content-Type: application/json" \
     -d '{
       "kiosk_code": "'"$KIOSK_CODE"'",
       "user_id": "'"$USER_ID"'",
       "points_to_redeem": '"$POINTS"',
       "timestamp": '"$TIMESTAMP"',
       "signature": "'"$SIGNATURE"'"
     }'
echo -e "\n"

# Dashboard Test Data Seeders

This document describes the seeders created to populate test data for the JuanCharge dashboard statistics.

## Seeders Created

### 1. MultipleKiosksSeeder

Creates 7 kiosks with different statuses:

-   **5 Active kiosks** (Downtown Plaza, City Hall, University Campus, Shopping Mall, Public Park)
-   **1 Inactive kiosk** (Train Station)
-   **1 Maintenance kiosk** (Community Center)

### 2. RecyclablesSeeder

Updates all kiosk users with random recyclables weight data (5kg - 150kg per user).

### 3. ChargingSessionSeeder

Generates realistic charging sessions:

-   **50+ completed sessions** across all users
-   **3 cancelled sessions** for testing
-   Sessions dated over the past 30 days
-   Realistic energy calculations (0.167 Wh/min for 10W port)

### 4. DashboardTestSeeder

Master seeder that runs all seeders in the correct order:

1. MultipleKiosksSeeder
2. TestMobileUserSeeder
3. RecyclablesSeeder
4. ChargingSessionSeeder

## How to Use

Run the master seeder:

```bash
php artisan db:seed --class=DashboardTestSeeder
```

Then test the dashboard endpoint:

```bash
powershell -ExecutionPolicy Bypass -File test-dashboard-stats.ps1
```

## Expected Results

After seeding, the dashboard should show:

-   **Total Recyclables**: ~200-500 kg (varies based on random data)
-   **Total Energy**: ~50-200 kWh (varies based on sessions)
-   **CO2 Saved**: ~25-100 kg (calculated as energy × 0.5)
-   **Online Kiosks**: 5 kiosks

## Files Created

-   `database/seeders/MultipleKiosksSeeder.php`
-   `database/seeders/RecyclablesSeeder.php`
-   `database/seeders/ChargingSessionSeeder.php`
-   `database/seeders/DashboardTestSeeder.php`

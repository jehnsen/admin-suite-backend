# Dashboard Metrics Guide

## Overview

The Dashboard Metrics module provides intelligent, predictive alerts for Administrative Officers to proactively manage school operations. This feature implements three critical metrics that automatically monitor budgets, inventory, and employee records to surface actionable insights.

**Module Location:** `app/Http/Controllers/Api/DashboardController.php`
**Added:** February 2026
**Version:** 1.0.0

---

## Features

### 1. 📊 Expiring Budget Alert

Automatically detects budgets approaching their end date to prevent fund lapses.

**Business Logic:**
- Monitors active budgets with `end_date` within a configurable threshold (default: 60 days)
- Calculates days until expiry using `DATEDIFF(end_date, CURDATE())`
- Shows remaining balance to help prioritize spending

**Example Alert:**
```
"MOOE - Training and Development Fund expires in 45 days. You have ₱15,000.00 remaining."
```

**Use Cases:**
- Identify funds that must be obligated before fiscal year end
- Prioritize procurement requests based on budget availability
- Generate reports for quarterly budget reviews

---

### 2. 📦 Stock Critical Level (AI/Usage-Based Prediction)

Predicts inventory stockouts using consumption pattern analysis from the last 30 days.

**Business Logic:**
- Analyzes "Stock Out" transactions from the last 30 days
- Calculates average daily consumption: `total_consumed / 30 days`
- Predicts stockout date: `current_balance / avg_daily_consumption`
- Only shows items predicted to run out within 14 days (configurable)

**Example Alert:**
```
"Based on last month's usage, your Bond Paper (A4) will run out in 7 days (by Thursday, February 13, 2026)."
```

**Use Cases:**
- Proactive procurement to avoid service disruptions
- Identify high-consumption items for bulk ordering
- Seasonal usage pattern analysis

**Technical Implementation:**
- Uses complex SQL subqueries to join `inventory_items` with `stock_cards`
- Filters only items with usage history (excludes zero-consumption items)
- Provides predicted stockout date with day-of-week for planning

---

### 3. 👥 Employee Step Increment Due (Document Maturity)

Tracks permanent employees eligible for step increment based on DepEd's 3-year rule.

**Business Logic:**
- Queries permanent, active employees
- Calculates months since last step increment from service records
- Uses latest "Promotion" or "New Appointment" date, or falls back to `date_hired`
- DepEd rule: Step increment eligibility every 36 months (3 years)
- Shows employees due within 60 days (configurable)

**Example Alert:**
```
"Maria Juana Santos's Step Increment (Step 3 → 4) is due in 9 days (February 15, 2026). Prepare NOSA (Notice of Salary Adjustment)."
```

**Use Cases:**
- Prepare Notice of Salary Adjustment (NOSA) documents in advance
- Track step progression for all permanent employees
- Ensure timely processing of salary increments per CSC regulations

**Technical Implementation:**
- Joins `employees` with `service_records` to find last step change date
- Calculates `TIMESTAMPDIFF(MONTH, last_increment_date, CURDATE())`
- Projects next increment date 36 months from last increment

---

## API Endpoints

### Primary Endpoint (All Metrics)

**GET** `/api/dashboard/metrics`

Returns all three metrics in a single response with summary counts and high-priority alerts.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `budget_days` | integer | 60 | Days to check for expiring budgets |
| `stock_prediction_days` | integer | 14 | Days to predict stockout |
| `stock_usage_window` | integer | 30 | Days to analyze for usage patterns |
| `step_increment_days` | integer | 60 | Days to check for due step increments |
| `step_increment_months` | integer | 36 | Months required for step increment eligibility |

**Example Request:**
```bash
GET /api/dashboard/metrics?budget_days=45&stock_prediction_days=7
Authorization: Bearer {token}
```

**Response Structure:**
```json
{
  "data": {
    "alerts": {
      "expiring_budgets": [...],
      "critical_stock_items": [...],
      "step_increments_due": [...]
    },
    "summary": {
      "total_alerts": 15,
      "expiring_budgets_count": 3,
      "critical_stock_items_count": 7,
      "step_increments_due_count": 5,
      "high_priority_count": 4
    },
    "high_priority_alerts": [
      {
        "type": "stock_critical",
        "priority": "high",
        "days_remaining": 2,
        "message": "Based on last month's usage, your Bond Paper (A4) will run out in 2 days (by Monday, February 10, 2026).",
        "data": { /* full item details */ }
      }
    ],
    "generated_at": "2026-02-06T10:30:00.000000Z"
  }
}
```

---

### Individual Endpoints

#### 1. Expiring Budgets Only

**GET** `/api/dashboard/expiring-budgets`

**Query Parameters:**
- `within_days` (integer, default: 60)

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "budget_name": "Training and Development Fund",
      "fund_source": "MOOE",
      "remaining_balance": 15000.00,
      "end_date": "2026-04-15",
      "days_until_expiry": 45,
      "alert_message": "MOOE - Training and Development Fund expires in 45 days. You have ₱15,000.00 remaining."
    }
  ]
}
```

#### 2. Critical Stock Items Only

**GET** `/api/dashboard/critical-stock`

**Query Parameters:**
- `prediction_days` (integer, default: 14)
- `usage_window` (integer, default: 30)

**Response:**
```json
{
  "data": [
    {
      "id": 12,
      "item_name": "Bond Paper (A4)",
      "unit": "reams",
      "current_balance": 25,
      "avg_daily_consumption": 3.5,
      "days_until_stockout": 7,
      "predicted_stockout_date": "2026-02-13",
      "alert_message": "Based on last month's usage, your Bond Paper (A4) will run out in 7 days (by Thursday, February 13, 2026)."
    }
  ]
}
```

#### 3. Step Increments Due Only

**GET** `/api/dashboard/step-increments-due`

**Query Parameters:**
- `within_days` (integer, default: 60)
- `eligibility_months` (integer, default: 36)

**Response:**
```json
{
  "data": [
    {
      "id": 8,
      "employee_number": "2021-0045",
      "employee_name": "Maria Juana Santos",
      "position": "Teacher III",
      "current_step": 3,
      "next_step": 4,
      "last_increment_date": "2023-02-15",
      "months_since_last_increment": 36,
      "next_increment_date": "2026-02-15",
      "days_until_due": 9,
      "alert_message": "Maria Juana Santos's Step Increment (Step 3 → 4) is due in 9 days (February 15, 2026). Prepare NOSA (Notice of Salary Adjustment)."
    }
  ]
}
```

---

## High Priority Alerts

The unified `/api/dashboard/metrics` endpoint includes a `high_priority_alerts` array that automatically identifies:

- **High Priority:** Items due within 7 days (budgets, stock) or 30 days (step increments with priority=medium if 8-30 days, high if ≤7 days)
- **Sorted by urgency:** Most urgent items appear first
- **Categorized by type:** `budget_expiring`, `stock_critical`, `step_increment_due`

This allows the frontend to display a "Top Alerts" widget on the dashboard.

---

## Architecture

### Three-Layer Structure

```
┌─────────────────────────────────────────┐
│    DashboardController.php              │
│    - metrics()                          │
│    - expiringBudgets()                  │
│    - criticalStock()                    │
│    - stepIncrementsDue()                │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│    DashboardService.php                 │
│    - Business logic                     │
│    - calculateHighPriorityAlerts()      │
│    - Option handling                    │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│    DashboardRepository.php              │
│    - Complex SQL queries                │
│    - Usage pattern calculations         │
│    - Date arithmetic                    │
└─────────────────────────────────────────┘
```

**Key Files:**
- Interface: `app/Interfaces/DashboardRepositoryInterface.php`
- Repository: `app/Repositories/DashboardRepository.php`
- Service: `app/Services/DashboardService.php`
- Controller: `app/Http/Controllers/Api/DashboardController.php`

**Registration:**
- Service Provider: `app/Providers/RepositoryServiceProvider.php` (line 101)
- Routes: `routes/api.php` (lines 57-64)

---

## Database Queries

### No Migrations Required

This feature leverages existing table structures:
- `budgets`: end_date, remaining_balance, status, fund_source
- `stock_cards`: transaction_date, transaction_type, quantity_out, balance
- `employees`: date_hired, step_increment, status, employment_status
- `service_records`: date_from, action_type, step_increment

### Query Performance

All queries use:
- Indexed columns for WHERE clauses
- Subqueries optimized by MySQL query planner
- DB::raw() for complex calculations
- Efficient joins with COALESCE for null handling

**Expected Response Time:** < 2 seconds for typical school data (100-500 employees, 1000+ stock cards)

---

## Configuration

### Default Thresholds

```php
// In DashboardService.php
$budgetDays = 60;              // 2 months before budget expiry
$stockPredictionDays = 14;     // 2 weeks stockout warning
$stockUsageWindow = 30;        // 1 month usage analysis
$stepIncrementDays = 60;       // 2 months before increment due
$stepIncrementMonths = 36;     // 3 years per DepEd rule
```

These can be overridden via query parameters on each request.

### High Priority Threshold

Items flagged as high priority:
- Budgets expiring within **7 days**
- Stock running out within **7 days**
- Step increments due within **30 days** (medium) or **7 days** (high)

---

## Testing

### Manual Testing Checklist

1. **Expiring Budget Alert**
   - Create budget with `end_date` = today + 30 days
   - Set `remaining_balance` = 15000
   - Call `/api/dashboard/expiring-budgets?within_days=60`
   - Verify budget appears with correct `days_until_expiry`

2. **Critical Stock Alert**
   - Create inventory item (e.g., "Bond Paper")
   - Add stock cards showing consumption:
     - Week 1: 10 units out
     - Week 2: 12 units out
     - Week 3: 8 units out
     - Week 4: 10 units out
   - Set current balance = 25
   - Call `/api/dashboard/critical-stock?prediction_days=14`
   - Verify `avg_daily_consumption` ≈ 1.43, `days_until_stockout` ≈ 17

3. **Step Increment Alert**
   - Create permanent employee with `date_hired` = today - 35 months
   - Call `/api/dashboard/step-increments-due?within_days=60`
   - Verify employee appears with `days_until_due` ≈ 30

### Integration Testing

```bash
# Test unified endpoint
curl -X GET "http://localhost:8000/api/dashboard/metrics" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Test with custom thresholds
curl -X GET "http://localhost:8000/api/dashboard/metrics?budget_days=30&stock_prediction_days=7" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Frontend Integration

### Recommended Dashboard Layout

```
┌─────────────────────────────────────────────────────┐
│  ADMINSUITE DASHBOARD                               │
├─────────────────────────────────────────────────────┤
│  🚨 High Priority Alerts (4)                        │
│  ┌───────────────────────────────────────────────┐ │
│  │ Bond Paper (A4) - runs out in 2 days         │ │
│  │ MOOE Training Fund - expires in 5 days       │ │
│  └───────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────┤
│  📊 Expiring Budgets (3)        📦 Critical Stock (7) │
│  💼 Step Increments Due (5)                         │
└─────────────────────────────────────────────────────┘
```

### Refresh Strategy

- **On page load:** Fetch `/api/dashboard/metrics`
- **Auto-refresh:** Every 5 minutes
- **Cache:** Consider client-side caching for 1-2 minutes to reduce server load

### Notification Strategy

- **Desktop notifications:** For high-priority alerts (requires permission)
- **Badge counters:** Show alert counts on sidebar/nav
- **Email digest:** Send daily summary at 8:00 AM (future enhancement)

---

## Business Rules

### Budget Expiry
- Only considers "Active" budgets
- Excludes "Draft", "Closed", "Cancelled" budgets
- Calculates based on `end_date` field

### Stock Prediction
- Requires at least 1 "Stock Out" transaction in the usage window
- Items with zero consumption are excluded
- Uses floor division for days calculation (conservative estimate)

### Step Increment Eligibility
- **Permanent employees only** (excludes Temporary, Casual, Contractual)
- **Active status only** (excludes Inactive, Retired, Resigned)
- **3-year rule:** Based on DepEd/CSC regulations (36 months)
- Falls back to `date_hired` if no service record exists

---

## Troubleshooting

### No alerts showing

**Check:**
1. Are there active budgets with `end_date` in the near future?
2. Do inventory items have Stock Out transactions in the last 30 days?
3. Are there permanent employees hired ~3 years ago?

### Incorrect consumption calculation

**Verify:**
- Stock cards have `transaction_type` = "Stock Out" (exact match, case-sensitive)
- `transaction_date` is within the usage window
- `quantity_out` values are positive

### Step increments not appearing

**Verify:**
- Employee `employment_status` = "Permanent"
- Employee `status` = "Active"
- Service records exist with `action_type` IN ('Promotion', 'New Appointment')
- Date arithmetic: 36 months from last increment is within the threshold

---

## Future Enhancements

1. **Email Notifications:** Daily digest of high-priority alerts
2. **SMS Alerts:** Critical alerts (< 3 days) via SMS gateway
3. **Historical Tracking:** Store daily snapshots for trend analysis
4. **Machine Learning:** Improve stock predictions using ML models
5. **Custom Thresholds:** Allow admins to set school-specific thresholds
6. **Export to PDF:** Generate printable alert summary for meetings
7. **Mobile Push:** Real-time alerts via mobile app

---

## Support

For issues or questions:
- **Technical:** Check implementation in `app/Http/Controllers/Api/DashboardController.php`
- **Business Logic:** Review calculations in `app/Repositories/DashboardRepository.php`
- **Testing:** See Postman collection in `documentation/AdminSuite_API.postman_collection.json`

---

**Last Updated:** February 6, 2026
**Author:** AdminSuite Development Team
**Version:** 1.0.0

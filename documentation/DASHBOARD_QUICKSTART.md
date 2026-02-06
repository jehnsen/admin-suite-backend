# Dashboard Metrics - Quick Start Guide

## 🚀 Quick Access

The Dashboard Metrics feature is now fully implemented and documented!

### 📚 Full Documentation
**Location:** [`documentation/DASHBOARD_METRICS_GUIDE.md`](./DASHBOARD_METRICS_GUIDE.md)

### 📮 Postman Collection
**Location:** [`documentation/AdminSuite_API.postman_collection.json`](./AdminSuite_API.postman_collection.json)
**Folder:** "Dashboard - Command Center" (located after Authentication folder)

---

## ⚡ Quick Test (5 Minutes)

### 1. Start Laravel Server
```bash
php artisan serve
```

### 2. Import Postman Collection
- Open Postman
- Import `documentation/AdminSuite_API.postman_collection.json`
- Import environment `documentation/AdminSuite_Environment.postman_environment.json`

### 3. Authenticate
- Run "Authentication → Login" with credentials:
  ```json
  {
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
  }
  ```
- Token will be auto-saved to environment as `{{auth_token}}`

### 4. Test Dashboard Endpoint
- Navigate to "Dashboard - Command Center" folder
- Run "Get All Dashboard Metrics"
- Expected response:
  ```json
  {
    "data": {
      "alerts": {
        "expiring_budgets": [],
        "critical_stock_items": [],
        "step_increments_due": []
      },
      "summary": {
        "total_alerts": 0,
        "expiring_budgets_count": 0,
        "critical_stock_items_count": 0,
        "step_increments_due_count": 0,
        "high_priority_count": 0
      },
      "high_priority_alerts": [],
      "generated_at": "2026-02-06T..."
    }
  }
  ```

---

## 📊 Available Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/dashboard/metrics` | GET | All metrics (unified) |
| `/api/dashboard/expiring-budgets` | GET | Budget expiry alerts only |
| `/api/dashboard/critical-stock` | GET | Stock prediction alerts only |
| `/api/dashboard/step-increments-due` | GET | Step increment alerts only |

---

## 🎯 Example Alerts

### Expiring Budget
```
"MOOE - Training and Development Fund expires in 45 days. You have ₱15,000.00 remaining."
```

### Critical Stock (AI-Powered)
```
"Based on last month's usage, your Bond Paper (A4) will run out in 7 days (by Thursday, February 13, 2026)."
```

### Step Increment Due
```
"Maria Juana Santos's Step Increment (Step 3 → 4) is due in 9 days (February 15, 2026). Prepare NOSA (Notice of Salary Adjustment)."
```

---

## 🔧 Configuration

### Default Thresholds

| Parameter | Default | Description |
|-----------|---------|-------------|
| `budget_days` | 60 | Days before budget expiry |
| `stock_prediction_days` | 14 | Stockout prediction window |
| `stock_usage_window` | 30 | Usage analysis period |
| `step_increment_days` | 60 | Days before increment due |
| `step_increment_months` | 36 | DepEd 3-year rule |

### Customizing Thresholds

Add query parameters to any endpoint:

```bash
GET /api/dashboard/metrics?budget_days=30&stock_prediction_days=7&step_increment_days=45
```

---

## 📖 Documentation Files

1. **Full Guide:** [`DASHBOARD_METRICS_GUIDE.md`](./DASHBOARD_METRICS_GUIDE.md)
   - Complete feature documentation
   - Business logic explanation
   - API reference
   - Architecture overview
   - Troubleshooting guide

2. **This Quick Start:** [`DASHBOARD_QUICKSTART.md`](./DASHBOARD_QUICKSTART.md)
   - 5-minute setup
   - Quick testing guide

3. **Postman Collection:** [`AdminSuite_API.postman_collection.json`](./AdminSuite_API.postman_collection.json)
   - Updated with 4 dashboard endpoints
   - Ready-to-use requests with descriptions

---

## 🏗️ Implementation Files

**Created:**
- `app/Interfaces/DashboardRepositoryInterface.php`
- `app/Repositories/DashboardRepository.php`
- `app/Services/DashboardService.php`
- `app/Http/Controllers/Api/DashboardController.php`

**Modified:**
- `app/Providers/RepositoryServiceProvider.php` (line 101)
- `routes/api.php` (lines 57-64)

---

## 🎨 Frontend Integration Tips

### Polling Strategy
```javascript
// Refresh dashboard every 5 minutes
setInterval(() => {
  fetchDashboardMetrics();
}, 300000);
```

### High Priority Alerts Widget
```javascript
const highPriorityAlerts = response.data.high_priority_alerts;
const criticalCount = highPriorityAlerts.filter(a => a.priority === 'high').length;
// Display badge with count
```

### Individual Metric Cards
```javascript
// Expiring Budgets Card
const budgetCount = response.data.summary.expiring_budgets_count;

// Critical Stock Card
const stockCount = response.data.summary.critical_stock_items_count;

// Step Increments Card
const incrementCount = response.data.summary.step_increments_due_count;
```

---

## 📞 Support

**Need Help?**
- Check [`DASHBOARD_METRICS_GUIDE.md`](./DASHBOARD_METRICS_GUIDE.md) for detailed documentation
- Review Postman collection for request examples
- Inspect implementation in `app/Http/Controllers/Api/DashboardController.php`

---

**Last Updated:** February 6, 2026
**Version:** 1.0.0

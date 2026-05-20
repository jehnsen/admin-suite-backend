<!-- # **Project: AdminSuite**
**"The Digital Backbone of Philippine Public Schools"** -->

### **1. Executive Summary**
**AdminSuite** is a cloud-based Enterprise Resource Planning (ERP) system specifically engineered for the **Administrative Officer II (AO II)** of the Department of Education (DepEd).

It replaces fragmented manual systems (logbooks, Excel files, physical folders) with a centralized command center that automates:
1.  **Personnel Management** (201 Files, Service Credits, Leave Tracking).
2.  **Property Custodianship** (Inventory, Issuances, RIS, QR Tagging).
3.  **Attendance** (Biometric CSV Import, DTR Computation).
4.  **Financial Operations** (MOOE Tracking, Cash Disbursement, Liquidation).
5.  **Government Reports** (Form 6, RIS, DV, IAR, PDS).

**Primary Goal:** To reduce the AO's administrative workload by 60% and ensure 100% compliance with COA and CSC auditing standards.

---

### **2. System Blueprint (Architecture)**

This system uses a **Headless Architecture**. The frontend is decoupled from the backend to ensure speed, security, and scalability.

**The Data Flow:**
1.  **The Client (Frontend):** The AO accesses the dashboard via **Next.js 16**. It renders the UI instantly using cached data.
2.  **The Gateway (API):** Requests are sent to the **Laravel 11 API**.
3.  **The Guard (Security):** Laravel Sanctum validates the "Bearer Token" to ensure the user is authorized.
4.  **The Brain (Service Layer):** The API calculates complex logic (e.g., *Is the teacher allowed to take leave based on current credits?*).
5.  **The Vault (Database):** Data is stored in **MySQL**, with strict relationships linking *People* to *Items* and *Money*.

**Code layer responsibilities:**
```
Request → Controller → FormRequest (validation)
                    → Service   (business logic)
                    → Repository (data access, no raw query strings in Services)
                    → Model
```

---

### **3. Coding Conventions**

#### UUID vs Integer IDs
- Every model exposes a **UUID as its public identifier** (`uuid` column, `HasUuid` trait, `getRouteKeyName()` returns `'uuid'`).
- Route model binding resolves the UUID automatically, but **services and repositories always work with integer primary keys** internally.
- Controllers are responsible for the translation: `$model = Model::where('uuid', $uuid)->firstOrFail(); $service->doThing($model->id);`
- **Exception — RIS module:** `StoreRequisitionSlipRequest` and `ApproveRequisitionSlipRequest` still accept integer IDs for employee and inventory item fields. This is a known inconsistency; do not spread the pattern.

#### Response Shape
All API responses follow:
```json
{
    "data": { ... },        // single resource or paginated list
    "message": "..."        // present on mutations
}
```
Errors from FormRequests return `422` with `{"errors": {"field": ["message"]}}`. Not-found resources return `404` with `{"message": "...not found."}`.

#### Permissions
Permissions are checked with `$this->authorize('permission-name')` or middleware `permission:name` on the route. The four roles and their typical access:

| Permission area | Super Admin | School Head | Admin Officer | Teacher/Staff |
|----------------|:-----------:|:-----------:|:-------------:|:-------------:|
| User management | ✓ | view only | ✓ | — |
| Employee CRUD | ✓ | ✓ | ✓ | — |
| Leave requests | ✓ | approve | create/manage | create own |
| Inventory / Issuance | ✓ | ✓ | ✓ | — |
| RIS approve | ✓ | ✓ | ✓ | — |
| Attendance import | ✓ | — | ✓ | — |
| Export reports | ✓ | ✓ | ✓ | — |
| Financial | ✓ | view | ✓ | — |

#### Enum values (must match DB `ENUM` definition exactly)
- `InventoryItem.fund_source`: `MOOE`, `SEF`, `DepEd Central`, `Other`
- `InventoryItem.condition`: `Serviceable`, `Unserviceable`, `For Disposal`
- `InventoryItem.status`: `In Stock`, `Issued`, `Condemned`
- `Issuance.document_type`: `ICS`, `PAR`, `General`
- `Issuance.status`: `Active`, `Returned`, `Transferred`, `Damaged`
- `RequisitionSlip.status`: `Draft`, `Pending`, `Approved`, `Released`, `Cancelled`
- `ServiceRecord.government_service`: `Yes`, `No` (not boolean)
- `Training.training_type`: `Seminar`, `Workshop`, `Conference`, `LAC Session`, `Other`

---

### **4. Module Overview**

#### HR
- **Employees** — 201 file system; `Employee` model linked to `User` (optional).
- **Leave Requests** — 12 leave types; statuses: Draft → Recommended → Approved / Disapproved.
- **Service Records** — Required fields: `designation`, `status_of_appointment`, `salary_grade`, `step_increment`, `monthly_salary`, `station_place_of_assignment`, `office_entity`, `action_type`, `government_service`.
- **Trainings** — Required fields: `training_title`, `training_type` (enum).

#### Inventory
- **InventoryItem** — Required fields: `item_code`, `item_name`, `category`, `unit_of_measure`, `unit_cost`, `quantity`, `condition`, `status`, `fund_source`, `date_acquired`. No DB defaults for `fund_source` or `date_acquired`.
- **Issuances** — Full lifecycle: create → acknowledge → return / transfer. `scopeOverdue()` filters Active records where `expected_return_date < today`.
- **Requisition & Issue Slips (RIS)** — Workflow: Pending → Approved (sets `approved_quantities` per item) → Released (sets `issued_quantities`, decrements inventory stock). Cancel at any non-released stage.

#### Attendance
- **AttendanceController** (`Api\Attendance\AttendanceController`) handles CSV import at `POST /api/attendance/import`. The **separate** `Api\HR\AttendanceRecordController` handles manual DTR entries.
- **CSV formats supported** by `AttendanceImportService`:
  - `employee_number,datetime` (primary)
  - `emp_no` / `employee_no` / `id` / `emp_id` + `datetime` / `timestamp` / `punch_time`
  - Separate `date` + `time` columns
- Import creates an `AttendanceImportBatch`, then processes logs and upserts `DailyTimeRecord` rows.
- DTR computation: earliest punch = time_in, latest punch = time_out; grace period = 10 minutes for lateness; holidays fetched via `Holiday::inRange()`.
- `attendance_import_batches.period_start/period_end` are stored as **datetime** (`2025-06-01 00:00:00`) even when date-only input is provided.

#### Reports
- All report endpoints return **JSON data only**. The frontend (Next.js) is responsible for rendering and printing.
- Permission required: `export_reports` (Admin Officer and School Head).
- Routes: `GET /api/reports/{form6|ris|dv|iar|pds}/{uuid}`.

---

### **5. Testing**

**Stack:** PHPUnit, SQLite in-memory, `RefreshDatabase` trait.

**Test setup in `Tests\TestCase`:**
- `RoleAndPermissionSeeder` is called in `setUp()` so roles and permissions exist in every test.
- Rate limiters are disabled in the test environment.
- Helper `userWithRole(string $role): User` — creates a User with a Sanctum token and the given role.
- Helper `userWithEmployee(string $role): array` — returns `[$user, $employee]`.

**Storage:** Tests that touch file uploads must call `Storage::fake('local')` to intercept disk writes.

**Running tests:**
```bash
php artisan test
# or a single file:
php artisan test tests/Feature/Inventory/IssuanceTest.php
```

**Key gotchas discovered:**
- `InventoryItem` inserts will fail with NOT NULL violations if `fund_source` or `date_acquired` are omitted.
- `ServiceRecord` inserts require all of: `designation`, `status_of_appointment`, `salary_grade`, `step_increment`, `monthly_salary`, `station_place_of_assignment`, `office_entity`, `action_type`, `government_service` (use `'Yes'`/`'No'`, not `true`/`false`).
- `Training` inserts require `training_type` (enum string, not free text).
- `assertDatabaseHas` on `period_start`/`period_end` requires the full datetime string: `'2025-06-01 00:00:00'`.
- `Collection::mapWithKeys()` is the correct method; `mapKeys()` does not exist.

---

### **6. API Documentation**

API docs are generated by Scribe:
```bash
php artisan scribe:generate
```
Then visit `/docs`. Scribe config is at `config/scribe.php`.

# System Architecture & Feature Enhancements

## 1. Proposed Module: Payroll Management

To complete the HR lifecycle, a Payroll module is essential for DepEd schools to handle salaries and mandatory government deductions.

### Key Features
- **General Payroll:** Monthly salary processing based on Salary Grade (SG) and Step Increment.
- **Deductions Manager:** Configurable formulas for GSIS, PhilHealth, Pag-IBIG, and Withholding Tax.
- **Payslip Generation:** PDF generation of individual payslips.
- **Remittance Reports:** Auto-generation of remittance lists for government agencies.

### Integration Points
- **HR Module:** Pulls `salary_grade` and `step_increment` from `employees` table.
- **Financial Module:** Creates a `Disbursement Voucher` automatically when payroll is approved.

---

## 2. Technical Enhancement: Global Audit Trail

For accountability and COA compliance, all write operations (CREATE, UPDATE, DELETE) must be logged.

### Implementation Strategy
Use `spatie/laravel-activitylog` or a custom `AuditLog` model.

**Schema:**
```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    action VARCHAR(255),      -- e.g., "Approved PR", "Updated Stock"
    module VARCHAR(50),       -- e.g., "Inventory", "HR"
    record_id BIGINT,         -- ID of the affected record
    old_values JSON,          -- Snapshot before change
    new_values JSON,          -- Snapshot after change
    ip_address VARCHAR(45),
    created_at TIMESTAMP
);
```

---

## 3. Workflow Enhancement: Notification System

Move from passive status checks to active notifications using Laravel's Event system.

### Notification Channels
- **In-App:** Database notifications visible in the dashboard.
- **Email:** Official DepEd email notifications for approvals.

### Critical Triggers
1. **Approval Required:** When PR, PO, Leave, or Liquidation is submitted.
2. **Status Change:** When an item is Approved, Rejected, or Released.
3. **Inventory Alerts:** When stock quantity drops below `reorder_point`.
4. **Financial Alerts:** When Cash Advance is 3 days before due date.

---

## 4. Feature: Digital Document Management

Support for uploading and linking digital copies of supporting documents.

### Requirements
- **Storage:** Local (storage/app/public) or S3.
- **Linking:** Polymorphic relationship to `PurchaseRequests`, `Liquidations`, `InventoryAdjustments`.

### Use Cases
- **Liquidations:** Photo of Official Receipts (OR) is mandatory.
- **Inventory:** Photo of equipment for Property Card.
- **Procurement:** Scanned copy of signed Purchase Order and Delivery Receipt.

---

## 5. Reporting Engine (PDF)

Generate official government forms compliant with DepEd/COA formats.

### Required Forms
- **Form 6:** Application for Leave.
- **PDS (CS Form 212):** Personal Data Sheet.
- **IAR:** Inspection and Acceptance Report.
- **RIS:** Requisition and Issue Slip.
- **DV:** Disbursement Voucher.
- **SALN:** Statement of Assets, Liabilities, and Net Worth.

---

## 6. Refined Database Schema Suggestions

### A. Add `reorder_point` to Inventory Items
To enable proactive procurement.

```php
Schema::table('inventory_items', function (Blueprint $table) {
    $table->integer('reorder_point')->default(5);
    $table->integer('critical_level')->default(2);
});
```

### B. Add `tax_class` to Suppliers
To automate tax withholding calculations (VAT vs Non-VAT).

```php
Schema::table('suppliers', function (Blueprint $table) {
    $table->enum('tax_type', ['VAT_Registered', 'Non_VAT'])->default('VAT_Registered');
    $table->decimal('tax_rate', 5, 2)->default(12.00);
});
```

### C. Add `signatories` Table
To manage dynamic approvers for reports without hardcoding names.

```php
Schema::create('signatories', function (Blueprint $table) {
    $table->id();
    $table->string('report_type'); // e.g., "Form 6", "DV"
    $table->foreignId('employee_id');
    $table->string('designation_override')->nullable();
    $table->integer('sequence_order');
});
```
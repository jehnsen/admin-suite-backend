# Inventory & Financial Management Modules Guide

## Overview

Complete implementation of **Inventory Management** and **Financial Management** modules for DepEd AdminSuite, enabling full lifecycle tracking from procurement to stock management, with comprehensive financial controls including MOOE tracking, cash advances, disbursements, and liquidations.

---

## 🏭 INVENTORY MANAGEMENT MODULE

### Features Implemented

#### 1. **Stock Card System**
Complete double-entry inventory tracking with automatic balance calculation.

**Transaction Types:**
- Stock In (from deliveries, purchases)
- Stock Out (issuances, transfers)
- Adjustment (corrections, donations)
- Transfer In/Out (between locations)
- Donation (received equipment)
- Return (returned items)
- Disposal (condemned items)

**Key Features:**
- Automatic balance calculation
- Transaction history tracking
- Unit cost and total cost tracking
- Reference to source documents (DR, IAR, RIS)
- Multi-user transaction tracking

#### 2. **Inventory Adjustments**
Manage inventory corrections and donations.

**Adjustment Types:**
- Increase (found items, donations, corrections)
- Decrease (lost, damaged, expired, stolen)
- Correction (fixing errors)
- Donation Received (donated equipment)
- Disposal (condemned/obsolete items)
- Transfer (between locations)

**Workflow:**
1. Admin Officer prepares adjustment with reason
2. Documents supporting evidence
3. School Head/Principal approves
4. System automatically creates stock card entry
5. Inventory balance updated

#### 3. **Physical Count/Inventory**
Periodic physical inventory verification.

**Process:**
1. Conduct physical count of items
2. Compare with system quantity (from stock card)
3. Calculate variance (shortage/overage/match)
4. Document explanation for variances
5. Verify count results
6. Create adjustments if needed

**Variance Types:**
- Shortage (actual < system)
- Overage (actual > system)
- Match (actual = system)

---

## 💰 FINANCIAL MANAGEMENT MODULE

### Features Implemented

#### 1. **Cash Advances**
Track employee cash advances with liquidation monitoring.

**Workflow:**
1. Employee requests cash advance for project/activity
2. Specify purpose, amount, and fund source (MOOE/SEF)
3. School Head approves
4. Disbursing officer releases cash
5. System tracks due date for liquidation
6. Automatic overdue flagging

**Status Flow:**
`Pending` → `Approved` → `Released` → `Partially Liquidated` → `Fully Liquidated`

**Key Features:**
- Due date tracking with overdue alerts
- Unliquidated balance monitoring
- Link to budget allocation
- Employee history tracking

#### 2. **Disbursements (Disbursement Vouchers)**
Complete disbursement voucher system for MOOE tracking.

**Workflow:**
1. Create DV for payment (PO, expense, or cash advance)
2. Administrative Officer certifies accuracy
3. School Head approves
4. Disbursing Officer marks as paid
5. Track check numbers and payment modes

**Payment Modes:**
- Check
- Cash
- Bank Transfer
- Other

**Tax Handling:**
- Gross amount
- Tax withheld calculation
- Net amount (auto-calculated)

**Integration Points:**
- Links to Purchase Orders
- Links to Expenses
- Links to Cash Advances
- Budget allocation tracking

#### 3. **Liquidations**
Complete liquidation system with automatic calculations.

**Workflow:**
1. Employee submits liquidation for cash advance
2. List all expenses with receipts/invoices
3. System calculates total expenses
4. Auto-calculates refund or additional cash needed
5. Verifier checks supporting documents
6. School Head approves
7. Process refund or additional payment

**Automatic Calculations:**
- Total expenses from line items
- Amount to refund (if CA > expenses)
- Additional cash needed (if expenses > CA)
- Updates cash advance liquidation status

**Supporting Documents:**
- Receipts
- Invoices
- Official Receipts (OR)
- Expense categories tracking

---

## Database Schema

### Inventory Tables

**stock_cards** (Transaction ledger)
- All inventory movements
- Running balance
- Cost tracking
- Source document references

**inventory_adjustments** (Corrections & donations)
- Adjustment workflow
- Quantity before/after tracking
- Approval system
- Supporting documentation

**physical_counts** (Physical inventory)
- System vs actual quantities
- Variance calculation
- Count verification
- Investigation notes

### Financial Tables

**cash_advances** (CA tracking)
- Employee cash advances
- Purpose and amount
- Due date tracking
- Liquidation status
- Overdue monitoring

**disbursements** (Disbursement Vouchers)
- DV workflow
- Payee information
- Tax withholding
- Payment tracking
- Multi-level approval

**liquidations** (CA liquidations)
- Expense itemization
- Auto-calculations
- Document tracking
- Refund/payment management

**liquidation_items** (Expense line items)
- Individual expenses
- OR/Invoice numbers
- Expense categories
- Date tracking

---

## API Endpoints

### Inventory - Stock Cards
```
GET    /api/stock-cards                      - List all transactions
POST   /api/stock-cards/stock-in             - Record stock in
POST   /api/stock-cards/stock-out            - Record stock out
POST   /api/stock-cards/donation             - Record donation
GET    /api/stock-cards/item/{itemId}        - Get item history
GET    /api/stock-cards/item/{itemId}/balance - Get current balance
GET    /api/stock-cards/statistics           - Get stock statistics
```

### Inventory - Adjustments
```
GET    /api/inventory-adjustments            - List all adjustments
POST   /api/inventory-adjustments            - Create adjustment
GET    /api/inventory-adjustments/pending    - Get pending adjustments
GET    /api/inventory-adjustments/{id}       - Get adjustment details
PUT    /api/inventory-adjustments/{id}       - Update adjustment
DELETE /api/inventory-adjustments/{id}       - Delete adjustment
PUT    /api/inventory-adjustments/{id}/approve - Approve adjustment
PUT    /api/inventory-adjustments/{id}/reject  - Reject adjustment
GET    /api/inventory-adjustments/statistics - Get adjustment statistics
```

### Inventory - Physical Counts
```
GET    /api/physical-counts                  - List all counts
POST   /api/physical-counts                  - Create count
GET    /api/physical-counts/variance         - Get counts with variance
GET    /api/physical-counts/{id}             - Get count details
PUT    /api/physical-counts/{id}             - Update count
PUT    /api/physical-counts/{id}/verify      - Verify count
GET    /api/physical-counts/statistics       - Get count statistics
```

### Financial - Cash Advances
```
GET    /api/cash-advances                    - List all cash advances
POST   /api/cash-advances                    - Create cash advance
GET    /api/cash-advances/pending            - Get pending CAs
GET    /api/cash-advances/overdue            - Get overdue CAs
GET    /api/cash-advances/employee/{id}      - Get by employee
GET    /api/cash-advances/{id}               - Get CA details
PUT    /api/cash-advances/{id}               - Update CA
DELETE /api/cash-advances/{id}               - Delete CA
PUT    /api/cash-advances/{id}/approve       - Approve CA
PUT    /api/cash-advances/{id}/release       - Release CA
GET    /api/cash-advances/statistics         - Get CA statistics
```

### Financial - Disbursements
```
GET    /api/disbursements                    - List all disbursements
POST   /api/disbursements                    - Create disbursement
GET    /api/disbursements/pending            - Get pending DVs
GET    /api/disbursements/{id}               - Get DV details
PUT    /api/disbursements/{id}               - Update DV
DELETE /api/disbursements/{id}               - Delete DV
PUT    /api/disbursements/{id}/certify       - Certify DV
PUT    /api/disbursements/{id}/approve       - Approve DV
PUT    /api/disbursements/{id}/pay           - Mark as paid
GET    /api/disbursements/statistics         - Get DV statistics
```

### Financial - Liquidations
```
GET    /api/liquidations                     - List all liquidations
POST   /api/liquidations                     - Create liquidation
GET    /api/liquidations/pending             - Get pending liquidations
GET    /api/liquidations/{id}                - Get liquidation details
PUT    /api/liquidations/{id}                - Update liquidation
DELETE /api/liquidations/{id}                - Delete liquidation
PUT    /api/liquidations/{id}/verify         - Verify liquidation
PUT    /api/liquidations/{id}/approve        - Approve liquidation
PUT    /api/liquidations/{id}/reject         - Reject liquidation
GET    /api/liquidations/statistics          - Get liquidation statistics
```

---

## Business Logic & Workflows

### Stock Card Workflow
```
1. Transaction occurs (delivery, issuance, adjustment)
2. System retrieves current balance
3. Calculates new balance (balance + in - out)
4. Records transaction with:
   - Reference number (DR, RIS, IAR, etc.)
   - Transaction type
   - Quantities in/out
   - New balance
   - Unit cost and total cost
5. Links to source document (delivery, issuance, adjustment)
```

### Adjustment Approval Workflow
```
1. Admin Officer creates adjustment
   - Gets current balance from stock card
   - Specifies adjustment type and quantity
   - Provides reason and supporting docs
2. Status: Pending
3. School Head reviews and approves
4. System creates stock card entry automatically
5. Inventory balance updated
6. Status: Approved
```

### Cash Advance → Liquidation Flow
```
1. Employee requests CA
   └─ Status: Pending
2. School Head approves
   └─ Status: Approved
3. Disbursing Officer releases cash
   └─ Status: Released
   └─ System tracks due date
4. Employee submits liquidation with receipts
   └─ Liquidation Status: Pending
5. Verifier checks documents
   └─ Liquidation Status: Verified
6. School Head approves liquidation
   └─ Liquidation Status: Approved
7. System calculates:
   - If expenses < CA: Refund needed
   - If expenses > CA: Additional payment needed
   - If expenses = CA: Balanced
8. CA Status updated:
   └─ Fully Liquidated or Partially Liquidated
```

### Disbursement Approval Chain
```
1. DV created for payment
   └─ Status: Pending
2. Administrative Officer certifies
   └─ Status: Certified
3. School Head approves
   └─ Status: Approved
4. Disbursing Officer pays
   └─ Status: Paid
   └─ Records check number/payment details
```

---

## Integration Points

### With Procurement Module
- **Deliveries** → Auto-create stock card "Stock In" entries
- **Purchase Orders** → Link to disbursements for payment
- **Supplier payments** → Create disbursement vouchers

### With Budget Module
- **Cash Advances** → Link to budget allocation
- **Disbursements** → Track MOOE/SEF spending
- **Fund source** → Budget vs actual tracking

### With Inventory Module
- **Issuances** → Auto-create stock card "Stock Out" entries
- **Physical counts** → Generate adjustments for variances
- **Adjustments** → Update stock card balances

---

## Sample Workflows

### Example 1: Recording Donated Equipment
```
POST /api/inventory-adjustments
{
  "adjustment_type": "Donation Received",
  "inventory_item_id": 1,
  "quantity_adjusted": 5,
  "reason": "Donation from alumni association",
  "supporting_document": "Deed of Donation dated Jan 15, 2025",
  "prepared_by": 3
}

→ Status: Pending
→ Approve: PUT /api/inventory-adjustments/{id}/approve
→ System creates stock card entry
→ Inventory balance increased by 5
```

### Example 2: Employee Cash Advance
```
1. Create CA:
POST /api/cash-advances
{
  "employee_id": 2,
  "purpose": "Attend DepEd Regional Training",
  "amount": 5000.00,
  "fund_source": "MOOE",
  "date_needed": "2025-01-20",
  "due_date_liquidation": "2025-01-27"
}

2. Approve CA:
PUT /api/cash-advances/{id}/approve

3. Release CA:
PUT /api/cash-advances/{id}/release

4. Submit Liquidation:
POST /api/liquidations
{
  "cash_advance_id": 1,
  "items": [
    {
      "expense_date": "2025-01-20",
      "particulars": "Transportation - Manila to Pampanga",
      "amount": 1500.00,
      "category": "Transportation"
    },
    {
      "expense_date": "2025-01-21",
      "particulars": "Meals and accommodation",
      "amount": 2800.00,
      "category": "Meals"
    }
  ]
}

→ System calculates:
  Total expenses: ₱4,300
  Amount to refund: ₱700 (5000 - 4300)

5. Verify & Approve Liquidation
→ CA updated to "Fully Liquidated"
```

### Example 3: Disbursement for PO Payment
```
POST /api/disbursements
{
  "purchase_order_id": 1,
  "payee_name": "Manila Office Supplies Corporation",
  "payee_tin": "123-456-789-000",
  "purpose": "Payment for office supplies per PO-2025-0001",
  "gross_amount": 24500.00,
  "tax_withheld": 735.00,
  "fund_source": "MOOE",
  "payment_mode": "Check"
}

→ Net amount auto-calculated: ₱23,765
→ Certify → Approve → Pay
→ Budget utilization tracked
```

---

## Authentication & Permissions

All endpoints require authentication:
```
Authorization: Bearer {token}
```

**Role-Based Access:**
- **Super Admin**: Full access
- **School Head**: Approve adjustments, CAs, disbursements, liquidations
- **Admin Officer**: Create and manage all transactions
- **Teacher/Staff**: Request cash advances, submit liquidations

---

## Statistics & Reporting

### Inventory Statistics
- Total transactions
- Stock in vs stock out counts
- Total value in vs out
- Adjustment counts by type
- Physical count variance summary

### Financial Statistics
- Total cash advances
- Unliquidated balance
- Overdue cash advances
- Total disbursements by fund source
- Liquidation completion rate

---

## Next Steps

1. **Start MySQL** via XAMPP
2. **Run all migrations:**
   ```bash
   php artisan migrate
   ```
3. **Seed data:**
   ```bash
   php artisan db:seed
   ```
4. **Test endpoints** via Postman
5. **Generate reports** using statistics endpoints

---

**Total Endpoints Added:**
- **Inventory**: 22 endpoints
- **Financial**: 30 endpoints
- **Grand Total with all modules**: **137+ endpoints** 🎉

Built with ❤️ for DepEd Administrative Officers

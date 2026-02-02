# Procurement Module Guide

## Overview

The Procurement Module is a comprehensive system for managing the entire procurement lifecycle in DepEd schools, from purchase request creation to delivery acceptance. It follows government procurement standards and implements proper approval workflows.

## Module Components

### 1. **Suppliers Management**
Manage vendor information and track supplier performance.

**Features:**
- Supplier registration with complete business details
- Government registrations tracking (TIN, PhilGEPS, SEC/DTI, Mayor's Permit)
- Banking information for payments
- Product categories classification
- Performance rating system
- Supplier status management (Active/Inactive/Blacklisted)

**API Endpoints:**
```
GET    /api/suppliers              - List all suppliers
POST   /api/suppliers              - Create new supplier
GET    /api/suppliers/active       - Get active suppliers only
GET    /api/suppliers/search       - Search suppliers
GET    /api/suppliers/statistics   - Get supplier statistics
GET    /api/suppliers/{id}         - Get supplier details
PUT    /api/suppliers/{id}         - Update supplier
DELETE /api/suppliers/{id}         - Delete supplier
```

### 2. **Purchase Requests (PR)**
Create and manage purchase requests following DepEd procurement procedures.

**Workflow:**
1. **Draft** → Employee creates PR with items
2. **Pending** → PR submitted for review
3. **Recommended** → Immediate supervisor recommends
4. **Approved** → School Head/Principal approves
5. **For Quotation** → Ready for supplier canvassing
6. **For PO Creation** → After quotation selection
7. **Completed** → PO created

**Features:**
- Multiple procurement modes (Small Value, Shopping, Public Bidding, etc.)
- Fund source tracking (MOOE, SEF, etc.)
- Item-level details with specifications
- Multi-level approval workflow
- Budget tracking and validation

**API Endpoints:**
```
GET    /api/purchase-requests                   - List all PRs
POST   /api/purchase-requests                   - Create new PR
GET    /api/purchase-requests/pending           - Get pending PRs
GET    /api/purchase-requests/statistics        - Get PR statistics
GET    /api/purchase-requests/{id}              - Get PR details
PUT    /api/purchase-requests/{id}              - Update PR
DELETE /api/purchase-requests/{id}              - Delete PR
PUT    /api/purchase-requests/{id}/submit       - Submit PR for approval
PUT    /api/purchase-requests/{id}/recommend    - Recommend PR
PUT    /api/purchase-requests/{id}/approve      - Approve PR
PUT    /api/purchase-requests/{id}/disapprove   - Disapprove PR
PUT    /api/purchase-requests/{id}/cancel       - Cancel PR
```

### 3. **Quotations**
Manage supplier quotations and bid evaluation.

**Features:**
- Link quotations to purchase requests
- Multiple quotations per PR for comparison
- Item-level pricing from suppliers
- Tax, discount, and shipping calculations
- Quotation validity tracking
- Bid evaluation and ranking
- Automatic selection of winning quotation

**API Endpoints:**
```
GET    /api/quotations                              - List all quotations
POST   /api/quotations                              - Create new quotation
GET    /api/quotations/purchase-request/{prId}      - Get quotations for PR
GET    /api/quotations/{id}                         - Get quotation details
PUT    /api/quotations/{id}                         - Update quotation
DELETE /api/quotations/{id}                         - Delete quotation
PUT    /api/quotations/{id}/select                  - Select winning quotation
PUT    /api/quotations/purchase-request/{prId}/evaluate - Evaluate quotations
```

### 4. **Purchase Orders (PO)**
Generate and manage purchase orders to suppliers.

**Workflow:**
1. **Pending** → PO created, awaiting approval
2. **Approved** → Approved by authorized signatory
3. **Sent to Supplier** → PO transmitted to supplier
4. **Partially Delivered** → Some items delivered
5. **Fully Delivered** → All items delivered
6. **Completed** → PO closed
7. **Cancelled** → PO cancelled

**Features:**
- Auto-generation from approved PR and selected quotation
- Supplier details snapshot at time of PO
- Fund source allocation
- Delivery tracking
- Budget integration
- Multi-level approval
- Terms and conditions management

**API Endpoints:**
```
GET    /api/purchase-orders                     - List all POs
POST   /api/purchase-orders                     - Create new PO
GET    /api/purchase-orders/pending             - Get pending POs
GET    /api/purchase-orders/statistics          - Get PO statistics
GET    /api/purchase-orders/{id}                - Get PO details
PUT    /api/purchase-orders/{id}                - Update PO
DELETE /api/purchase-orders/{id}                - Delete PO
PUT    /api/purchase-orders/{id}/approve        - Approve PO
PUT    /api/purchase-orders/{id}/send-to-supplier - Send PO to supplier
PUT    /api/purchase-orders/{id}/cancel         - Cancel PO
```

### 5. **Deliveries**
Track and manage item deliveries and acceptance.

**Workflow:**
1. **Pending Inspection** → Delivery received, awaiting inspection
2. **Under Inspection** → Quality inspection in progress
3. **Accepted** → Items passed inspection and accepted
4. **Rejected** → Items failed inspection
5. **Partially Accepted** → Some items accepted, some rejected

**Features:**
- Delivery receipt recording
- Supplier DR and invoice tracking
- Item-level delivery quantities
- Quality inspection process
- Acceptance workflow
- Serial number tracking for equipment
- Batch and expiry date tracking
- Delivery condition assessment
- Automatic PO item quantity updates

**API Endpoints:**
```
GET    /api/deliveries                          - List all deliveries
POST   /api/deliveries                          - Create new delivery
GET    /api/deliveries/pending                  - Get pending deliveries
GET    /api/deliveries/statistics               - Get delivery statistics
GET    /api/deliveries/purchase-order/{poId}    - Get deliveries for PO
GET    /api/deliveries/{id}                     - Get delivery details
PUT    /api/deliveries/{id}                     - Update delivery
DELETE /api/deliveries/{id}                     - Delete delivery
PUT    /api/deliveries/{id}/inspect             - Inspect delivery
PUT    /api/deliveries/{id}/accept              - Accept delivery
PUT    /api/deliveries/{id}/reject              - Reject delivery
```

## Database Schema

### Suppliers Table
- Basic business information (name, owner, type)
- Contact details (email, phone, mobile, address)
- Government registrations (TIN, PhilGEPS, SEC/DTI, Mayor's Permit)
- Banking information
- Product categories
- Performance metrics (rating, transactions, total amount)

### Purchase Requests Table
- PR details (number, date, purpose)
- Requestor and department information
- Procurement mode and fund source
- Budget allocation
- Delivery requirements
- Approval workflow fields (recommended_by, approved_by)
- Status tracking

### Purchase Request Items Table
- Item description and specifications
- Unit of measure and quantity
- Cost details (unit cost, total cost)
- Stock tracking (on hand, monthly consumption)

### Quotations Table
- Reference to PR and supplier
- Quotation details (number, date, validity)
- Pricing breakdown (subtotal, tax, discount, shipping)
- Payment and delivery terms
- Evaluation fields (ranking, score, remarks)
- Selection status

### Quotation Items Table
- Item details with brand/model
- Quantities and pricing
- Specifications and delivery period

### Purchase Orders Table
- PO details (number, date)
- References to PR, quotation, supplier
- Supplier snapshot (name, address, contact, TIN)
- Financial details and fund allocation
- Delivery requirements
- Approval and preparation tracking
- Status workflow

### Purchase Order Items Table
- Item details and specifications
- Ordered vs delivered quantities tracking
- Pricing information
- Delivery status per item

### Deliveries Table
- Delivery receipt details
- Supplier DR and invoice tracking
- Delivery person information
- Reception details (received by, location, time)
- Inspection workflow (inspector, result, remarks)
- Acceptance tracking
- Physical condition assessment

### Delivery Items Table
- Item quantities (ordered, delivered, accepted, rejected)
- Item condition tracking
- Serial numbers and batch tracking
- Expiry dates for consumables

## Business Logic

### Purchase Request Workflow
```
1. Admin Officer creates PR with items
2. Immediate supervisor recommends PR
3. School Head/Principal approves PR
4. Status changes to "For Quotation" for procurement modes requiring canvassing
5. After quotation selection, status changes to "For PO Creation"
6. When PO is created, status changes to "Completed"
```

### Quotation Selection Process
```
1. Multiple suppliers submit quotations for approved PR
2. BAC evaluates quotations (ranking, scoring)
3. Winning quotation is selected
4. PR status updated to "For PO Creation"
5. Selected quotation is used to create PO
```

### Purchase Order Creation
```
1. System validates:
   - PR is approved
   - Quotation is selected (if applicable)
   - Supplier is active
2. Auto-generates PO number
3. Snapshots supplier details
4. Copies fund source from PR
5. Creates PO items from quotation/PR items
6. Sets status to "Pending"
```

### Delivery Acceptance Workflow
```
1. Delivery received → Create delivery record
2. Inspector conducts quality check
3. Set inspection result (Passed/Failed/Partially Passed)
4. If passed, authorized person accepts delivery
5. System updates PO item quantities delivered
6. If all items delivered, PO marked as "Fully Delivered"
```

## Sample Data

### Sample Suppliers (5 seeded)
1. **Manila Office Supplies Corporation** - Office/School Supplies
2. **Philippine IT Solutions Inc.** - IT Equipment
3. **Dela Cruz Enterprises** - Furniture
4. **DepEd Bookstore and Supplies** - Books/Educational Materials
5. **Green Clean Janitorial Services** - Janitorial Supplies

### Sample Purchase Requests (3 seeded)
1. **PR-2025-0001** - Office Supplies Replenishment (Approved)
   - 5 items, ₱24,500
2. **PR-2025-0002** - IT Equipment Replacement (For Quotation)
   - 2 items (5 desktops, 2 printers), ₱148,000
3. **PR-2025-0003** - Faculty Room Furniture (Pending)
   - 3 items (tables, chairs, cabinets), ₱44,500

## Integration Points

### With Budget Module
- PO creation validates budget availability
- Expenses are linked to POs
- Fund source tracking (MOOE, SEF)

### With Inventory Module
- Accepted deliveries can be added to inventory
- Item categories align with inventory system
- Serial number tracking for equipment

### With HR Module
- Requestor, recommender, approver linked to User/Employee
- Department tracking
- Custodianship assignment for delivered items

## Best Practices

### Purchase Request Creation
- Provide detailed item specifications
- Include accurate quantity estimates
- Reference PPMP (Project Procurement Management Plan)
- Use appropriate procurement mode based on amount

### Quotation Evaluation
- Compare at least 3 quotations for transparency
- Consider price, quality, delivery terms, and supplier reputation
- Document evaluation criteria and scoring

### Purchase Order Management
- Verify all details before approval
- Send PO to supplier promptly after approval
- Track delivery schedules
- Monitor partial deliveries

### Delivery Acceptance
- Inspect items thoroughly upon delivery
- Document any discrepancies or damages
- Accept only items meeting specifications
- Keep delivery receipts and supporting documents

## Reporting Capabilities

### Available Statistics

**Supplier Statistics:**
- Total suppliers count
- Active/Inactive/Blacklisted breakdown
- Suppliers by business type
- Average supplier rating

**Purchase Request Statistics:**
- Total PRs by status
- PRs by fund source
- PRs by procurement mode
- Total estimated budget

**Purchase Order Statistics:**
- Total POs by status
- Total PO amount
- POs by fund source
- Average PO value

**Delivery Statistics:**
- Deliveries by status
- Inspection results distribution
- Items by condition
- Acceptance rate

## Next Steps After Setup

1. **Start MySQL/MariaDB** via XAMPP Control Panel
2. **Run migrations:**
   ```bash
   php artisan migrate
   ```
3. **Seed data:**
   ```bash
   php artisan db:seed --class=SupplierSeeder
   php artisan db:seed --class=ProcurementSeeder
   ```
4. **Test endpoints** using Postman collection
5. **Create your first PR** via API
6. **Complete the procurement cycle:**
   - Submit PR → Recommend → Approve
   - Create quotations from suppliers
   - Select winning quotation
   - Create and approve PO
   - Record delivery and accept items

## Authentication

All procurement endpoints require authentication via Laravel Sanctum:
```
Authorization: Bearer {token}
```

Get your token by logging in:
```
POST /api/auth/login
{
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
}
```

---

**Built with ❤️ for DepEd Administrative Officers**

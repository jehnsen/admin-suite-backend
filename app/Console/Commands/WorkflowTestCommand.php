<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * AdminSuite End-to-End Workflow Test Runner
 *
 * Simulates a complete AO day by making real HTTP calls to the running API:
 *   Auth → HR → Inventory → Procurement → Finance → Dashboard
 *
 * Usage:
 *   php artisan workflow:test                           # run all modules
 *   php artisan workflow:test --module=hr              # run only HR
 *   php artisan workflow:test --module=procurement     # run only Procurement
 *   php artisan workflow:test --no-cleanup             # keep test data in DB
 *   php artisan workflow:test --url=http://localhost:8000
 */
class WorkflowTestCommand extends Command
{
    protected $signature = 'workflow:test
        {--module=all : Module to test: all, auth, hr, inventory, procurement, finance, dashboard}
        {--no-cleanup : Keep test data after run (tagged with [WFTEST] prefix)}
        {--url= : Override base URL (default: APP_URL from .env)}';

    protected $description = 'Run end-to-end workflow tests against the live AdminSuite API';

    // ── Auth State ────────────────────────────────────────────────────────────
    private string $baseUrl;
    private string $token = '';
    private int $userId = 0;

    // Unique timestamp suffix — prevents collisions if --no-cleanup was used
    private string $ts;

    // ── Test Tracking ─────────────────────────────────────────────────────────
    private int $passCount = 0;
    private int $failCount = 0;
    private array $failedSteps = [];
    private float $startTime;

    private array $moduleStatus = [
        'auth'        => 'pending',
        'hr'          => 'pending',
        'inventory'   => 'pending',
        'procurement' => 'pending',
        'finance'     => 'pending',
        'dashboard'   => 'pending',
    ];

    // ── Created Record IDs (shared across modules, used for cleanup) ──────────
    private array $ids = [
        'employee'        => null,
        'leaveRequest'    => null,
        'attendanceRecord'=> null,
        'serviceCredit'   => null,
        'inventoryItem'   => null,
        'adjustment'      => null,
        'supplier1'       => null,
        'supplier2'       => null,
        'supplier3'       => null,
        'purchaseRequest' => null,
        'quotation1'      => null,
        'quotation2'      => null,
        'quotation3'      => null,
        'purchaseOrder'   => null,
        'delivery'        => null,
        'budget'          => null,
        'cashAdvance'     => null,
        'disbursement'    => null,
        'liquidation'     => null,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $this->ts        = substr((string) time(), -6); // 6-digit suffix
        $this->baseUrl   = rtrim($this->option('url') ?? config('app.url'), '/');
        $module          = $this->option('module') ?? 'all';

        $this->line('');
        $this->line('<fg=cyan;options=bold>AdminSuite API — Workflow Test Suite</>');
        $this->line("<fg=gray>Base URL : {$this->baseUrl}</>");
        $this->line("<fg=gray>Module   : {$module}</>");
        $this->line(str_repeat('─', 60));

        // Auth always runs unless a specific non-auth module was requested
        if ($this->shouldRunModule('auth', $module)) {
            $passed = $this->runSection('AUTH', 'Authentication', fn () => $this->testAuth());
            $this->moduleStatus['auth'] = $passed ? 'passed' : 'failed';
        }

        if ($this->shouldRunModule('hr', $module)) {
            $passed = $this->runSection('HR', 'Human Resources', fn () => $this->testHr());
            $this->moduleStatus['hr'] = $passed ? 'passed' : 'failed';
        }

        if ($this->shouldRunModule('inventory', $module)) {
            $passed = $this->runSection('INVENTORY', 'Inventory Management', fn () => $this->testInventory());
            $this->moduleStatus['inventory'] = $passed ? 'passed' : 'failed';
        }

        if ($this->shouldRunModule('procurement', $module)) {
            $passed = $this->runSection('PROCUREMENT', 'Procurement Workflow', fn () => $this->testProcurement());
            $this->moduleStatus['procurement'] = $passed ? 'passed' : 'failed';
        }

        if ($this->shouldRunModule('finance', $module)) {
            $passed = $this->runSection('FINANCE', 'Budget & Finance', fn () => $this->testFinance());
            $this->moduleStatus['finance'] = $passed ? 'passed' : 'failed';
        }

        if ($this->shouldRunModule('dashboard', $module)) {
            $passed = $this->runSection('DASHBOARD', 'Dashboard & Reports', fn () => $this->testDashboard());
            $this->moduleStatus['dashboard'] = $passed ? 'passed' : 'failed';
        }

        $this->cleanup();
        $this->printSummary();

        return $this->failCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 0 — AUTHENTICATION
    // ─────────────────────────────────────────────────────────────────────────

    private function testAuth(): bool
    {
        $ok = $this->step('Login as Super Admin', function () {
            $res = $this->post('/api/auth/login', [
                'email'    => 'superadmin@deped.gov.ph',
                'password' => 'SuperAdmin123!',
            ]);
            if (empty($res['token'])) {
                throw new \RuntimeException('Login response missing token');
            }
            $this->token  = $res['token'];
            $this->userId = (int) ($res['user']['id'] ?? 0);
        });

        if ($ok) {
            $this->step('Verify token works (dashboard smoke test)', function () {
                $this->get('/api/dashboard/metrics');
            });
        }

        return $ok;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 1 — HUMAN RESOURCES
    // ─────────────────────────────────────────────────────────────────────────

    private function testHr(): bool
    {
        if ($this->moduleStatus['auth'] !== 'passed') {
            $this->warn('  [SKIP] HR module requires auth to pass first');
            return false;
        }

        // ── Employee ─────────────────────────────────────────────────────────
        $empCreated = $this->step('Create employee', function () {
            $res = $this->post('/api/employees', [
                'first_name'        => '[WFTEST]',
                'last_name'         => 'TestEmployee',
                'email'             => "wftest.{$this->ts}@deped.gov.ph",
                'mobile_number'     => "0917{$this->ts}",
                'address'           => '123 Test Street',
                'city'              => 'Quezon City',
                'province'          => 'Metro Manila',
                'zip_code'          => '1100',
                'date_of_birth'     => '1990-01-15',
                'gender'            => 'Male',
                'civil_status'      => 'Single',
                'plantilla_item_no' => "WFTEST-{$this->ts}",
                'position'          => 'Teacher I',
                'employment_status' => 'Permanent',
                'date_hired'        => '2020-01-01',
                'salary_grade'      => 11,
                'step_increment'    => 1,
                'monthly_salary'    => 25000.00,
            ]);
            $this->ids['employee'] = $res['data']['id'];
        });

        if (!$empCreated) {
            return false;
        }

        $empId = $this->ids['employee'];

        $this->step('Update employee city', function () use ($empId) {
            $this->put("/api/employees/{$empId}", ['city' => 'Manila']);
        });

        $this->step('Promote employee to Teacher II', function () use ($empId) {
            $this->post("/api/employees/{$empId}/promote", [
                'new_position'      => 'Teacher II',
                'new_salary_grade'  => 12,
                'new_monthly_salary'=> 30000.00,
                'effective_date'    => date('Y') . '-07-01',
            ]);
        });

        // ── Leave Request ─────────────────────────────────────────────────────
        $leaveCreated = $this->step('Create vacation leave request', function () use ($empId) {
            $res = $this->post('/api/leave-requests', [
                'employee_id' => $empId,
                'leave_type'  => 'Vacation Leave',
                'start_date'  => now()->addDays(7)->toDateString(),
                'end_date'    => now()->addDays(9)->toDateString(),
                'days_requested' => 3,
                'reason'      => '[WFTEST] Workflow test leave',
            ]);
            $this->ids['leaveRequest'] = $res['data']['id'];
        });

        if ($leaveCreated) {
            $leaveId = $this->ids['leaveRequest'];

            $this->step('Recommend leave request', function () use ($leaveId) {
                $this->put("/api/leave-requests/{$leaveId}/recommend", [
                    'remarks' => '[WFTEST] Recommended',
                ]);
            });

            $this->step('Approve leave request', function () use ($leaveId) {
                $this->put("/api/leave-requests/{$leaveId}/approve", [
                    'remarks' => '[WFTEST] Approved',
                ]);
            });
        }

        // ── Attendance Record (DTR) ───────────────────────────────────────────
        $attCreated = $this->step('Create attendance record (DTR)', function () use ($empId) {
            $res = $this->post('/api/attendance-records', [
                'employee_id'     => $empId,
                'attendance_date' => now()->subDay()->toDateString(),
                'time_in'         => '08:00:00',
                'time_out'        => '17:00:00',
                'lunch_out'       => '12:00:00',
                'lunch_in'        => '13:00:00',
                'status'          => 'Present',
            ]);
            $this->ids['attendanceRecord'] = $res['data']['id'];
        });

        if ($attCreated) {
            $attId = $this->ids['attendanceRecord'];
            $this->step('Approve attendance record', function () use ($attId) {
                $this->put("/api/attendance-records/{$attId}/approve");
            });
        }

        // ── Service Credit ────────────────────────────────────────────────────
        $scCreated = $this->step('Create service credit (Holiday Work)', function () use ($empId) {
            $res = $this->post('/api/service-credits', [
                'employee_id'   => $empId,
                'credit_type'   => 'Holiday Work',
                'work_date'     => now()->subDays(3)->toDateString(),
                'hours_worked'  => 8,
                'credits_earned'=> 1,
                'description'   => '[WFTEST] Holiday work service credit',
            ]);
            $this->ids['serviceCredit'] = $res['data']['id'];
        });

        if ($scCreated) {
            $scId = $this->ids['serviceCredit'];
            $this->step('Approve service credit', function () use ($scId) {
                $this->put("/api/service-credits/{$scId}/approve");
            });
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 2 — INVENTORY MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    private function testInventory(): bool
    {
        if ($this->moduleStatus['auth'] !== 'passed') {
            $this->warn('  [SKIP] Inventory module requires auth to pass first');
            return false;
        }

        $itemCreated = $this->step('Create inventory item', function () {
            $res = $this->post('/api/inventory-items', [
                'item_code'       => "WFTEST-{$this->ts}",
                'item_name'       => '[WFTEST] Bond Paper A4 80gsm',
                'description'     => '[WFTEST] Bond paper for office use',
                'category'        => 'Office Supplies',
                'unit_of_measure' => 'ream',
                'unit_cost'       => 250.00,
                'quantity'        => 0,
                'total_cost'      => 0,
                'fund_source'     => 'MOOE',
                'date_acquired'   => now()->toDateString(),
                'condition'       => 'Serviceable',
                'status'          => 'In Stock',
            ]);
            $this->ids['inventoryItem'] = $res['data']['id'];
        });

        if (!$itemCreated) {
            return false;
        }

        $itemId = $this->ids['inventoryItem'];

        $this->step('Stock in (50 reams)', function () use ($itemId) {
            $this->post('/api/stock-cards/stock-in', [
                'inventory_item_id' => $itemId,
                'quantity_in'       => 50,
                'unit_cost'         => 250.00,
                'transaction_date'  => now()->toDateString(),
                'reference_number'  => "WFTEST-STOCKIN-{$this->ts}",
                'remarks'           => '[WFTEST] Initial stock in',
            ]);
        });

        $adjCreated = $this->step('Create inventory adjustment (decrease 2)', function () use ($itemId) {
            $res = $this->post('/api/inventory-adjustments', [
                'inventory_item_id' => $itemId,
                'adjustment_type'   => 'Decrease',
                'quantity_adjusted' => 2,
                'reason'            => '[WFTEST] Damaged items removed',
                'adjustment_date'   => now()->toDateString(),
            ]);
            $this->ids['adjustment'] = $res['data']['id'];
        });

        if ($adjCreated) {
            $adjId = $this->ids['adjustment'];
            $this->step('Approve inventory adjustment', function () use ($adjId) {
                $this->put("/api/inventory-adjustments/{$adjId}/approve");
            });
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 3 — PROCUREMENT WORKFLOW
    //   Supplier → PR → Submit → Recommend → Approve →
    //   3 Quotations → Evaluate → Select → PO → Approve →
    //   Send to Supplier → Delivery → Inspect → Accept
    // ─────────────────────────────────────────────────────────────────────────

    private function testProcurement(): bool
    {
        if ($this->moduleStatus['auth'] !== 'passed') {
            $this->warn('  [SKIP] Procurement module requires auth to pass first');
            return false;
        }

        $suffix = $this->ts;

        // ── Create 3 Suppliers ────────────────────────────────────────────────
        for ($i = 1; $i <= 3; $i++) {
            $key = "supplier{$i}";
            $this->step("Create supplier #{$i}", function () use ($i, $key, $suffix) {
                $res = $this->post('/api/suppliers', [
                    'business_name'          => "[WFTEST] Supplier {$i} Corp {$suffix}",
                    'owner_name'             => "[WFTEST] Owner {$i}",
                    'email'                  => "wftest.supplier{$i}.{$suffix}@example.com",
                    'phone_number'           => "0{$i}8{$suffix}",
                    'business_type'          => 'Corporation',
                    'address'                => "{$i} Test Business Road",
                    'city'                   => 'Quezon City',
                    'province'               => 'Metro Manila',
                    'zip_code'               => '1100',
                    'tin'                    => "00{$i}-{$suffix}-00{$i}",
                    'status'                 => 'Active',
                ]);
                $this->ids[$key] = $res['data']['id'];
            });
        }

        if (!$this->ids['supplier1']) {
            return false;
        }

        // ── Purchase Request ──────────────────────────────────────────────────
        $prCreated = $this->step('Create purchase request', function () {
            $res = $this->post('/api/purchase-requests', [
                'pr_date'          => now()->toDateString(),
                'requested_by'     => $this->userId,
                'department'       => 'Administrative Department',
                'section'          => 'Office',
                'purpose'          => '[WFTEST] Workflow test procurement',
                'fund_source'      => 'MOOE',
                'procurement_mode' => 'Small Value Procurement',
                'estimated_budget' => 15000.00,
                'date_needed'      => now()->addDays(30)->toDateString(),
                'delivery_location'=> 'School Warehouse',
                'items' => [
                    [
                        'item_description' => '[WFTEST] Bond Paper A4 80gsm',
                        'unit_of_measure'  => 'ream',
                        'quantity'         => 10,
                        'unit_price'       => 250.00,
                        'total_amount'     => 2500.00,
                    ],
                ],
            ]);
            $this->ids['purchaseRequest'] = $res['data']['id'];
        });

        if (!$prCreated) {
            return false;
        }

        $prId = $this->ids['purchaseRequest'];

        $this->step('Submit purchase request (Draft → Pending)', function () use ($prId) {
            $this->put("/api/purchase-requests/{$prId}/submit");
        });

        $this->step('Recommend purchase request (Pending → Recommended)', function () use ($prId) {
            $this->put("/api/purchase-requests/{$prId}/recommend", [
                'remarks' => '[WFTEST] Recommended for approval',
            ]);
        });

        $this->step('Approve purchase request (Recommended → Approved)', function () use ($prId) {
            $this->put("/api/purchase-requests/{$prId}/approve", [
                'remarks' => '[WFTEST] Approved',
            ]);
        });

        // ── Create 3 Quotations (one per supplier) ────────────────────────────
        // First quotation creation will move PR → For Quotation
        $amounts = [2600.00, 2500.00, 2450.00]; // supplier 3 is cheapest (winner)
        for ($i = 1; $i <= 3; $i++) {
            $key      = "quotation{$i}";
            $suppKey  = "supplier{$i}";
            $amount   = $amounts[$i - 1];
            $unitPrice = round($amount / 10, 2);

            $this->step("Create quotation from supplier #{$i} (₱{$amount})", function () use ($prId, $suppKey, $amount, $unitPrice, $key) {
                $res = $this->post('/api/quotations', [
                    'purchase_request_id' => $prId,
                    'supplier_id'         => $this->ids[$suppKey],
                    'quotation_date'      => now()->toDateString(),
                    'validity_date'       => now()->addDays(30)->toDateString(),
                    'total_amount'        => $amount,
                    'payment_terms'       => 'COD',
                    'delivery_terms'      => '7 days',
                    'items' => [
                        [
                            'item_description' => '[WFTEST] Bond Paper A4 80gsm',
                            'unit_of_measure'  => 'ream',
                            'quantity'         => 10,
                            'unit_price'       => $unitPrice,
                            'total_amount'     => $amount,
                        ],
                    ],
                ]);
                $this->ids[$key] = $res['data']['id'];
            });
        }

        // ── Evaluate quotations (rank all 3) ──────────────────────────────────
        $this->step('Evaluate & rank quotations', function () use ($prId) {
            $this->put("/api/quotations/purchase-request/{$prId}/evaluate", [
                'evaluations' => [
                    ['quotation_id' => $this->ids['quotation1'], 'ranking' => 3, 'evaluation_score' => 78],
                    ['quotation_id' => $this->ids['quotation2'], 'ranking' => 2, 'evaluation_score' => 85],
                    ['quotation_id' => $this->ids['quotation3'], 'ranking' => 1, 'evaluation_score' => 95],
                ],
            ]);
        });

        // ── Select winning quotation → PR moves to "For PO Creation" ─────────
        $this->step('Select winning quotation (supplier #3 — lowest price)', function () {
            $this->put("/api/quotations/{$this->ids['quotation3']}/select");
        });

        // ── Purchase Order ────────────────────────────────────────────────────
        $poCreated = $this->step('Create purchase order', function () use ($prId) {
            $res = $this->post('/api/purchase-orders', [
                'po_date'           => now()->toDateString(),
                'purchase_request_id' => $prId,
                'quotation_id'      => $this->ids['quotation3'],
                'supplier_id'       => $this->ids['supplier3'],
                'total_amount'      => 2450.00,
                'subtotal'          => 2450.00,
                'fund_source'       => 'MOOE',
                'delivery_location' => 'School Warehouse',
                'delivery_date'     => now()->addDays(14)->toDateString(),
                'payment_terms'     => 'Net 30',
                'payment_method'    => 'Check',
                'items' => [
                    [
                        'item_description' => '[WFTEST] Bond Paper A4 80gsm',
                        'unit_of_measure'  => 'ream',
                        'quantity'         => 10,
                        'unit_price'       => 245.00,
                        'total_amount'     => 2450.00,
                    ],
                ],
            ]);
            $this->ids['purchaseOrder'] = $res['data']['id'];
        });

        if (!$poCreated) {
            return false;
        }

        $poId = $this->ids['purchaseOrder'];

        $this->step('Approve purchase order (Pending → Approved)', function () use ($poId) {
            $this->put("/api/purchase-orders/{$poId}/approve");
        });

        $this->step('Send PO to supplier (Approved → Sent to Supplier)', function () use ($poId) {
            $this->put("/api/purchase-orders/{$poId}/send-to-supplier");
        });

        // ── Fetch PO item IDs for use in delivery items ───────────────────────
        $poItemId = null;
        $this->step('Fetch PO item IDs for delivery', function () use ($poId, &$poItemId) {
            $res = $this->get("/api/purchase-orders/{$poId}");
            $items = $res['data']['items'] ?? [];
            if (empty($items)) {
                throw new \RuntimeException('PO returned no items — cannot build delivery items array');
            }
            $poItemId = $items[0]['id'];
        });

        // ── Delivery → Inspect → Accept ───────────────────────────────────────
        $deliveryCreated = $this->step('Create delivery record', function () use ($poId, $poItemId) {
            $deliveryItems = $poItemId ? [
                [
                    'purchase_order_item_id' => $poItemId,
                    'quantity_delivered'     => 10,
                    'quantity_accepted'      => 10,
                    'condition'              => 'Good',
                ],
            ] : [];

            $res = $this->post('/api/deliveries', [
                'purchase_order_id'      => $poId,
                'supplier_id'            => $this->ids['supplier3'],
                'delivery_date'          => now()->toDateString(),
                'delivery_receipt_number'=> "WFTEST-DR-{$this->ts}",
                'delivered_by_name'      => '[WFTEST] Delivery Man',
                'delivered_by_contact'   => '09181234567',
                'received_location'      => 'School Warehouse',
                'items'                  => $deliveryItems,
            ]);
            $this->ids['delivery'] = $res['data']['id'];
        });

        if ($deliveryCreated) {
            $deliveryId = $this->ids['delivery'];

            $this->step('Inspect delivery (result: Passed)', function () use ($deliveryId) {
                $this->put("/api/deliveries/{$deliveryId}/inspect", [
                    'inspection_result'  => 'Passed',
                    'inspection_remarks' => '[WFTEST] All items in good condition',
                ]);
            });

            $this->step('Accept delivery (creates inventory items + stock cards)', function () use ($deliveryId) {
                $this->put("/api/deliveries/{$deliveryId}/accept");
            });
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 4 — BUDGET & FINANCE
    //   Budget → Approve → Activate →
    //   Cash Advance → Approve → Release →
    //   Disbursement → Certify → Approve → Mark Paid →
    //   Liquidation → Add Item → Approve
    // ─────────────────────────────────────────────────────────────────────────

    private function testFinance(): bool
    {
        if ($this->moduleStatus['auth'] !== 'passed') {
            $this->warn('  [SKIP] Finance module requires auth to pass first');
            return false;
        }

        $empId = $this->ids['employee']; // may be null if HR module was skipped

        // ── Budget ────────────────────────────────────────────────────────────
        $budgetCreated = $this->step('Create budget (status: Draft)', function () {
            $res = $this->post('/api/budgets', [
                'budget_name'     => "[WFTEST] Operations Budget {$this->ts}",
                'description'     => '[WFTEST] Workflow test budget allocation',
                'fund_source'     => 'MOOE',
                'classification'  => 'AIP',
                'fiscal_year'     => (int) date('Y'),
                'allocated_amount'=> 100000.00,
                'category'        => 'Operating Expenses',
                'sub_category'    => 'Supplies',
                'status'          => 'Draft',
                'start_date'      => date('Y') . '-01-01',
                'end_date'        => date('Y') . '-12-31',
            ]);
            $this->ids['budget'] = $res['data']['id'];
        });

        if ($budgetCreated) {
            $budgetId = $this->ids['budget'];

            $this->step('Approve budget (Draft → Approved)', function () use ($budgetId) {
                $this->put("/api/budgets/{$budgetId}/approve");
            });

            $this->step('Activate budget (Approved → Active)', function () use ($budgetId) {
                $this->put("/api/budgets/{$budgetId}/activate");
            });
        }

        // ── Cash Advance ──────────────────────────────────────────────────────
        $caCreated = $this->step('Create cash advance request', function () use ($empId) {
            $data = [
                'ca_date'              => now()->toDateString(),
                'purpose'              => '[WFTEST] Seminar attendance and meals',
                'amount'               => 5000.00,
                'fund_source'          => 'MOOE',
                'date_needed'          => now()->addDays(3)->toDateString(),
                'due_date_liquidation' => now()->addDays(30)->toDateString(),
                'user_id'              => $this->userId,
            ];
            if ($empId) {
                $data['employee_id'] = $empId;
            }
            if ($this->ids['budget']) {
                $data['budget_id'] = $this->ids['budget'];
            }
            $res = $this->post('/api/cash-advances', $data);
            $this->ids['cashAdvance'] = $res['data']['id'];
        });

        if ($caCreated) {
            $caId = $this->ids['cashAdvance'];

            $this->step('Approve cash advance (Pending → Approved)', function () use ($caId) {
                $this->put("/api/cash-advances/{$caId}/approve", [
                    'approved_by' => $this->userId,
                ]);
            });

            $this->step('Release cash advance (Approved → Released)', function () use ($caId) {
                $this->put("/api/cash-advances/{$caId}/release", [
                    'released_by' => $this->userId,
                ]);
            });
        }

        // ── Disbursement Voucher ──────────────────────────────────────────────
        $dvCreated = $this->step('Create disbursement voucher (DV)', function () {
            $data = [
                'dv_date'       => now()->toDateString(),
                'payee_name'    => '[WFTEST] Test Payee',
                'payee_address' => '123 Test Street, Quezon City',
                'purpose'       => '[WFTEST] Payment for office supplies',
                'gross_amount'  => 5000.00,
                'net_amount'    => 5000.00,
                'amount'        => 5000.00,
                'fund_source'   => 'MOOE',
                'payment_mode'  => 'Cash',
            ];
            if ($this->ids['budget']) {
                $data['budget_id'] = $this->ids['budget'];
            }
            if ($this->ids['cashAdvance']) {
                $data['cash_advance_id'] = $this->ids['cashAdvance'];
            }
            $res = $this->post('/api/disbursements', $data);
            $this->ids['disbursement'] = $res['data']['id'];
        });

        if ($dvCreated) {
            $dvId = $this->ids['disbursement'];

            $this->step('Certify disbursement', function () use ($dvId) {
                $this->put("/api/disbursements/{$dvId}/certify");
            });

            $this->step('Approve disbursement', function () use ($dvId) {
                $this->put("/api/disbursements/{$dvId}/approve");
            });

            $this->step('Mark disbursement as paid', function () use ($dvId) {
                $this->put("/api/disbursements/{$dvId}/mark-paid", [
                    'payment_date' => now()->toDateString(),
                ]);
            });
        }

        // ── Liquidation ───────────────────────────────────────────────────────
        if ($caCreated) {
            $caId = $this->ids['cashAdvance'];

            $liquidCreated = $this->step('Create liquidation report', function () use ($caId) {
                $res = $this->post('/api/liquidations', [
                    'cash_advance_id'       => $caId,
                    'liquidation_date'      => now()->toDateString(),
                    'cash_advance_amount'   => 5000.00,
                    'total_expenses'        => 4800.00,
                    'amount_to_refund'      => 200.00,
                    'additional_cash_needed'=> 0,
                    'summary_of_expenses'   => '[WFTEST] Office supplies purchased during seminar',
                ]);
                $this->ids['liquidation'] = $res['data']['id'];
            });

            if ($liquidCreated) {
                $liquidId = $this->ids['liquidation'];

                $this->step('Add liquidation item (receipt)', function () use ($liquidId) {
                    $this->post("/api/liquidations/{$liquidId}/items", [
                        'expense_date'      => now()->toDateString(),
                        'particulars'       => '[WFTEST] Bond Paper A4 - 10 reams',
                        'amount'            => 2500.00,
                        'or_invoice_number' => "WFTEST-OR-{$this->ts}",
                    ]);
                });

                $this->step('Approve liquidation', function () use ($liquidId) {
                    $this->put("/api/liquidations/{$liquidId}/approve");
                });
            }
        }

        return $budgetCreated;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 5 — DASHBOARD & REPORTS
    // ─────────────────────────────────────────────────────────────────────────

    private function testDashboard(): bool
    {
        if ($this->moduleStatus['auth'] !== 'passed') {
            $this->warn('  [SKIP] Dashboard module requires auth to pass first');
            return false;
        }

        $this->step('Dashboard metrics', function () {
            $this->get('/api/dashboard/metrics');
        });

        $this->step('Expiring budgets (within 60 days)', function () {
            $this->get('/api/dashboard/expiring-budgets');
        });

        $this->step('Critical stock items', function () {
            $this->get('/api/dashboard/critical-stock');
        });

        $this->step('Step increments due', function () {
            $this->get('/api/dashboard/step-increments-due');
        });

        $this->step('Audit logs (latest 5)', function () {
            $this->get('/api/audit/logs', ['per_page' => 5]);
        });

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLEANUP — Delete test records in reverse order (FK-safe)
    // ─────────────────────────────────────────────────────────────────────────

    private function cleanup(): void
    {
        $this->line('');
        $this->line(str_repeat('─', 60));

        if ($this->option('no-cleanup')) {
            $this->warn('  [--no-cleanup] Test data retained in the database.');
            $this->warn('  Search for "[WFTEST]" or suffix "' . $this->ts . '" to find it.');
            return;
        }

        $this->line('<fg=cyan>Cleaning up test data...</>');

        // Order matters: children before parents to respect FK constraints
        $deletes = [
            ['liquidation',     '/api/liquidations'],
            ['disbursement',    '/api/disbursements'],
            ['cashAdvance',     '/api/cash-advances'],
            ['budget',          '/api/budgets'],
            ['delivery',        '/api/deliveries'],
            ['purchaseOrder',   '/api/purchase-orders'],
            ['quotation3',      '/api/quotations'],
            ['quotation2',      '/api/quotations'],
            ['quotation1',      '/api/quotations'],
            ['purchaseRequest', '/api/purchase-requests'],
            ['supplier3',       '/api/suppliers'],
            ['supplier2',       '/api/suppliers'],
            ['supplier1',       '/api/suppliers'],
            ['adjustment',      '/api/inventory-adjustments'],
            ['inventoryItem',   '/api/inventory-items'],
            ['serviceCredit',   '/api/service-credits'],
            ['attendanceRecord','/api/attendance-records'],
            ['leaveRequest',    '/api/leave-requests'],
            ['employee',        '/api/employees'],
        ];

        foreach ($deletes as [$key, $basePath]) {
            $id = $this->ids[$key] ?? null;
            if (!$id) {
                continue;
            }
            try {
                $this->delete("{$basePath}/{$id}");
                $this->line("  <fg=green>✓</> Deleted {$key} #{$id}");
            } catch (\Throwable $e) {
                // Cleanup errors are warnings only — never fail the test run
                $this->line("  <fg=yellow>⚠</> Could not delete {$key} #{$id}: " . $e->getMessage());
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUMMARY
    // ─────────────────────────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $elapsed = round(microtime(true) - $this->startTime, 1);
        $total   = $this->passCount + $this->failCount;

        $this->line('');
        $this->line('<fg=cyan;options=bold>' . str_repeat('═', 60) . '</>');
        $this->line('<fg=cyan;options=bold>  WORKFLOW TEST SUMMARY</>');
        $this->line('<fg=cyan;options=bold>' . str_repeat('═', 60) . '</>');
        $this->line('');

        $labels = [
            'auth'        => 'Authentication',
            'hr'          => 'Human Resources',
            'inventory'   => 'Inventory Management',
            'procurement' => 'Procurement Workflow',
            'finance'     => 'Budget & Finance',
            'dashboard'   => 'Dashboard & Reports',
        ];

        foreach ($labels as $key => $label) {
            $status = $this->moduleStatus[$key];
            if ($status === 'pending') {
                continue;
            }
            $color = match ($status) {
                'passed'  => 'green',
                'failed'  => 'red',
                default   => 'yellow',
            };
            $icon  = $status === 'passed' ? '✓' : ($status === 'failed' ? '✗' : '↷');
            $this->line("  <fg={$color}>{$icon}</> {$label}: <fg={$color};options=bold>" . strtoupper($status) . '</>');
        }

        $this->line('');
        $this->line(str_repeat('─', 60));

        if ($this->failCount === 0) {
            $this->line("<fg=green;options=bold>  ALL {$total} TESTS PASSED ✓</>");
        } else {
            $this->line("<fg=red;options=bold>  {$this->passCount}/{$total} passed — {$this->failCount} FAILED</>");
            $this->line('');
            $this->line('  Failed steps:');
            foreach ($this->failedSteps as $s) {
                $this->line("    <fg=red>• {$s}</>");
            }
        }

        $this->line("  Duration : {$elapsed}s");
        $this->line("  Base URL : {$this->baseUrl}");
        $this->line('<fg=cyan;options=bold>' . str_repeat('═', 60) . '</>');
        $this->line('');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function runSection(string $tag, string $title, \Closure $fn): bool
    {
        $this->line('');
        $this->line("<fg=cyan;options=bold>[{$tag}]</> {$title}");
        return (bool) $fn();
    }

    private function shouldRunModule(string $module, string $selected): bool
    {
        return $selected === 'all' || $selected === $module;
    }

    /**
     * Run a single test step, record pass/fail, print result.
     * Returns true on success, false on any exception.
     */
    private function step(string $label, \Closure $fn): bool
    {
        try {
            $fn();
            $this->passCount++;
            $this->line("  <fg=green>✓</> {$label}");
            return true;
        } catch (\Throwable $e) {
            $this->failCount++;
            $this->failedSteps[] = $label;
            $this->line("  <fg=red>✗</> {$label}");
            $this->line("    <fg=red>  → " . $e->getMessage() . '</>');
            return false;
        }
    }

    // ── HTTP Helpers ──────────────────────────────────────────────────────────

    private function get(string $path, array $query = []): array
    {
        $response = Http::withToken($this->token)
            ->accept('application/json')
            ->timeout(30)
            ->get($this->baseUrl . $path, $query);

        return $this->parseResponse($response, 'GET', $path);
    }

    private function post(string $path, array $data): array
    {
        $response = Http::withToken($this->token)
            ->accept('application/json')
            ->timeout(30)
            ->post($this->baseUrl . $path, $data);

        return $this->parseResponse($response, 'POST', $path);
    }

    private function put(string $path, array $data = []): array
    {
        $response = Http::withToken($this->token)
            ->accept('application/json')
            ->timeout(30)
            ->put($this->baseUrl . $path, $data);

        return $this->parseResponse($response, 'PUT', $path);
    }

    private function delete(string $path): void
    {
        $response = Http::withToken($this->token)
            ->accept('application/json')
            ->timeout(30)
            ->delete($this->baseUrl . $path);

        $this->parseResponse($response, 'DELETE', $path);
    }

    /**
     * Parse HTTP response. Throws RuntimeException on non-2xx with a
     * descriptive message including validation errors if present.
     */
    private function parseResponse(
        \Illuminate\Http\Client\Response $response,
        string $method,
        string $path
    ): array {
        $body = $response->json() ?? [];

        if ($response->failed()) {
            $status  = $response->status();
            $message = $body['message'] ?? $body['error'] ?? 'Unknown error';

            $errors = '';
            if (!empty($body['errors'])) {
                $flat = [];
                foreach ($body['errors'] as $field => $msgs) {
                    $flat[] = "{$field}: " . implode(', ', (array) $msgs);
                }
                $errors = ' [Validation: ' . implode(' | ', $flat) . ']';
            }

            throw new \RuntimeException("{$method} {$path} → HTTP {$status}: {$message}{$errors}");
        }

        return $body;
    }
}

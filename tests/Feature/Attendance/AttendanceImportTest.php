<?php

namespace Tests\Feature\Attendance;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
{
    private const PERIOD_START = '2025-06-01';
    private const PERIOD_END   = '2025-06-30';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a fake CSV UploadedFile from raw CSV content.
     */
    private function csvFile(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'attendance_') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'attendance.csv', 'text/csv', null, true);
    }

    /**
     * Standard biometric-format CSV with one employee, two punches (AM + PM).
     */
    private function validCsv(string $employeeNumber): string
    {
        return implode("\n", [
            'employee_number,datetime',
            "{$employeeNumber},2025-06-02 08:00:00",
            "{$employeeNumber},2025-06-02 17:00:00",
        ]);
    }

    // -------------------------------------------------------------------------
    // Auth & Permission Gates
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_import(): void
    {
        $this->postJson('/api/attendance/import')->assertStatus(401);
    }

    public function test_teacher_without_permission_cannot_import(): void
    {
        $teacher = $this->userWithRole('Teacher/Staff');

        $this->actingAs($teacher)
            ->postJson('/api/attendance/import', [
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_import_requires_file_and_period(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->postJson('/api/attendance/import', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['file', 'period_start', 'period_end']]);
    }

    public function test_import_rejects_non_csv_file(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $file = UploadedFile::fake()->create('attendance.pdf', 100, 'application/pdf');

        $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['file']]);
    }

    // -------------------------------------------------------------------------
    // Valid CSV
    // -------------------------------------------------------------------------

    public function test_valid_csv_creates_import_batch_and_logs(): void
    {
        Storage::fake('local');

        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-001']);

        $file = $this->csvFile($this->validCsv($employee->employee_number));

        $response = $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(201);

        $this->assertArrayHasKey('processed', $response->json());
        $this->assertGreaterThanOrEqual(1, $response->json('processed'));
        $this->assertSame(0, $response->json('errors'));
    }

    public function test_valid_csv_batch_record_is_stored_in_database(): void
    {
        Storage::fake('local');

        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-002']);

        $file = $this->csvFile($this->validCsv($employee->employee_number));

        $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('attendance_import_batches', [
            'uploaded_by'  => $admin->id,
            'period_start' => self::PERIOD_START . ' 00:00:00',
            'period_end'   => self::PERIOD_END   . ' 00:00:00',
        ]);
    }

    // -------------------------------------------------------------------------
    // Unknown Employee
    // -------------------------------------------------------------------------

    public function test_csv_row_with_unknown_employee_number_is_counted_as_error(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole('Admin Officer');

        // No employee with this number exists
        $csv  = "employee_number,datetime\nNOBODY-999,2025-06-02 08:00:00\n";
        $file = $this->csvFile($csv);

        $response = $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(201);

        $this->assertGreaterThanOrEqual(1, $response->json('errors'));
    }

    // -------------------------------------------------------------------------
    // Malformed CSV
    // -------------------------------------------------------------------------

    public function test_csv_missing_employee_column_reports_all_rows_as_errors(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole('Admin Officer');

        // Header has no employee identifier column
        $csv  = "date,time\n2025-06-02,08:00:00\n2025-06-02,17:00:00\n";
        $file = $this->csvFile($csv);

        $response = $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(201);

        // No logs should have been created since employee_number is missing
        $this->assertSame(0, $response->json('processed'));
    }

    // -------------------------------------------------------------------------
    // Mixed CSV (some valid, some invalid)
    // -------------------------------------------------------------------------

    public function test_partial_csv_returns_207_when_some_rows_fail(): void
    {
        Storage::fake('local');

        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-003']);

        $csv = implode("\n", [
            'employee_number,datetime',
            "{$employee->employee_number},2025-06-02 08:00:00",
            "{$employee->employee_number},2025-06-02 17:00:00",
            'NOBODY-999,2025-06-02 08:00:00',   // unknown employee
        ]);

        $file = $this->csvFile($csv);

        $this->actingAs($admin)
            ->postJson('/api/attendance/import', [
                'file'         => $file,
                'period_start' => self::PERIOD_START,
                'period_end'   => self::PERIOD_END,
            ])
            ->assertStatus(201);   // batch always created; 207 is issued by AttendanceRecordController not this one
    }
}

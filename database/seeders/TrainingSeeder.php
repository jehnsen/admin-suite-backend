<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Training;
use App\Models\Employee;
use App\Models\User;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample employees
        $employees = Employee::limit(5)->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Please run EmployeeSeeder first.');
            return;
        }

        $user = User::first();

        $trainings = [
            [
                'employee_id' => $employees[0]->id,
                'training_title' => 'Learning Action Cell (LAC) Session - Differentiated Instruction',
                'description' => 'Monthly LAC session focused on implementing differentiated instruction strategies in the classroom.',
                'training_type' => 'Workshop',
                'conducted_by' => 'DepEd Division Office',
                'venue' => 'School Conference Room',
                'venue_type' => 'In-house',
                'date_from' => '2024-09-15',
                'date_to' => '2024-09-15',
                'number_of_hours' => 4.00,
                'ld_units' => 0.50,
                'certificate_number' => 'LAC-2024-09-001',
                'certificate_date' => '2024-09-15',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[0]->id,
                'training_title' => 'Basic Education Research Fundamentals',
                'description' => 'Three-day training on conducting action research in basic education.',
                'training_type' => 'Training Course',
                'conducted_by' => 'DepEd Regional Office',
                'venue' => 'Regional Training Center',
                'venue_type' => 'External',
                'date_from' => '2024-07-08',
                'date_to' => '2024-07-10',
                'number_of_hours' => 24.00,
                'ld_units' => 3.00,
                'certificate_number' => 'BERF-2024-07-045',
                'certificate_date' => '2024-07-10',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[1]->id,
                'training_title' => 'Mental Health and Psychosocial Support (MHPSS) Training',
                'description' => 'Training on providing mental health support to students and handling psychosocial issues.',
                'training_type' => 'Seminar',
                'conducted_by' => 'Department of Health - DepEd Partnership',
                'venue' => 'Division Office Auditorium',
                'venue_type' => 'External',
                'date_from' => '2024-08-22',
                'date_to' => '2024-08-23',
                'number_of_hours' => 16.00,
                'ld_units' => 2.00,
                'certificate_number' => 'MHPSS-2024-234',
                'certificate_date' => '2024-08-23',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[1]->id,
                'training_title' => 'Google Workspace for Education Training',
                'description' => 'Online training on utilizing Google Workspace tools for blended learning.',
                'training_type' => 'Webinar',
                'conducted_by' => 'Google for Education',
                'venue' => 'Online via Google Meet',
                'venue_type' => 'Online',
                'date_from' => '2024-06-15',
                'date_to' => '2024-06-15',
                'number_of_hours' => 3.00,
                'ld_units' => 0.38,
                'certificate_number' => 'GWS-EDU-2024-1234',
                'certificate_date' => '2024-06-15',
                'sponsorship' => 'Private',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[2]->id,
                'training_title' => 'School-Based Feeding Program Management',
                'description' => 'Training on implementing and managing the National School-Based Feeding Program.',
                'training_type' => 'Training Course',
                'conducted_by' => 'DepEd - SBFP Division',
                'venue' => 'Regional Office',
                'venue_type' => 'External',
                'date_from' => '2024-05-20',
                'date_to' => '2024-05-22',
                'number_of_hours' => 24.00,
                'ld_units' => 3.00,
                'certificate_number' => 'SBFP-2024-089',
                'certificate_date' => '2024-05-22',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[2]->id,
                'training_title' => 'New Teacher Induction Program (NTIP)',
                'description' => 'Comprehensive orientation program for newly hired teachers covering DepEd policies, teaching methodologies, and classroom management.',
                'training_type' => 'Orientation',
                'conducted_by' => 'DepEd Division Office - Human Resource Division',
                'venue' => 'Division Conference Hall',
                'venue_type' => 'External',
                'date_from' => '2024-01-08',
                'date_to' => '2024-01-12',
                'number_of_hours' => 40.00,
                'ld_units' => 5.00,
                'certificate_number' => 'NTIP-2024-001',
                'certificate_date' => '2024-01-12',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[3]->id,
                'training_title' => 'National Educators Academy of the Philippines (NEAP) - Master Teacher Training',
                'description' => 'Advanced professional development program for master teachers on instructional leadership and coaching.',
                'training_type' => 'Professional Development',
                'conducted_by' => 'National Educators Academy of the Philippines',
                'venue' => 'NEAP Training Center, Quezon City',
                'venue_type' => 'External',
                'date_from' => '2024-03-11',
                'date_to' => '2024-03-15',
                'number_of_hours' => 40.00,
                'ld_units' => 5.00,
                'certificate_number' => 'NEAP-MT-2024-567',
                'certificate_date' => '2024-03-15',
                'sponsorship' => 'Government',
                'cost' => 15000.00,
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[3]->id,
                'training_title' => 'Educational Technology Integration Seminar',
                'description' => 'Two-day seminar on integrating educational technology tools and apps in teaching.',
                'training_type' => 'Seminar',
                'conducted_by' => 'Philippine Association of EdTech Professionals',
                'venue' => 'SMX Convention Center, Manila',
                'venue_type' => 'External',
                'date_from' => '2024-10-05',
                'date_to' => '2024-10-06',
                'number_of_hours' => 16.00,
                'ld_units' => 2.00,
                'certificate_number' => null,
                'certificate_date' => null,
                'sponsorship' => 'Self-funded',
                'cost' => 3500.00,
                'status' => 'Completed',
                'remarks' => 'Certificate to be claimed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[4]->id,
                'training_title' => 'Inclusive Education for Learners with Special Needs',
                'description' => 'Training on implementing inclusive education strategies for students with disabilities.',
                'training_type' => 'Workshop',
                'conducted_by' => 'DepEd Special Education Division',
                'venue' => 'Division SPED Center',
                'venue_type' => 'External',
                'date_from' => '2024-11-18',
                'date_to' => '2024-11-20',
                'number_of_hours' => 24.00,
                'ld_units' => 3.00,
                'certificate_number' => 'SPED-INC-2024-123',
                'certificate_date' => '2024-11-20',
                'sponsorship' => 'Government',
                'status' => 'Completed',
                'created_by' => $user?->id,
            ],
            [
                'employee_id' => $employees[4]->id,
                'training_title' => 'Data Privacy and Information Security in Education',
                'description' => 'Ongoing webinar series on data privacy compliance and cybersecurity in educational institutions.',
                'training_type' => 'Webinar',
                'conducted_by' => 'National Privacy Commission',
                'venue' => 'Online via Zoom',
                'venue_type' => 'Online',
                'date_from' => '2024-12-01',
                'date_to' => '2024-12-15',
                'number_of_hours' => 12.00,
                'ld_units' => 1.50,
                'certificate_number' => null,
                'certificate_date' => null,
                'sponsorship' => 'Government',
                'status' => 'Ongoing',
                'remarks' => 'Series of 4 sessions, currently in progress',
                'created_by' => $user?->id,
            ],
        ];

        foreach ($trainings as $training) {
            Training::create($training);
        }

        $this->command->info('Training records seeded successfully!');
    }
}

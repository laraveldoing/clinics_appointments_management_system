<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AppointmentConcurrencyTest extends TestCase
{
    public function test_concurrent_reschedules_cannot_double_book_a_doctor(): void
    {
        if (getenv('APPOINTMENT_CONCURRENCY_WORKER') === '1') {
            $this->runWorker();

            return;
        }

        if (getenv('APPOINTMENT_CONCURRENCY_TEST') !== '1' || DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This integration test requires an isolated PostgreSQL test database.');
        }

        $clinic = Clinic::factory()->create();
        $doctor = User::factory()->doctor()->for($clinic)->create();
        $firstPatient = Patient::factory()->for($clinic)->create();
        $secondPatient = Patient::factory()->for($clinic)->create();
        $initialStart = now()->addDays(3)->setTime(9, 0);

        $appointments = collect([$firstPatient, $secondPatient])
            ->values()
            ->map(function (Patient $patient, int $index) use ($clinic, $doctor, $initialStart): Appointment {
                $start = $initialStart->copy()->addHours($index);

                return Appointment::factory()->create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'user_id' => $doctor->id,
                    'start_time' => $start,
                    'end_time' => $start->copy()->addMinutes(30),
                ]);
            });

        $targetStart = now()->addDays(5)->setTime(10, 0);
        $directory = sys_get_temp_dir().'/appointment-concurrency-'.bin2hex(random_bytes(8));

        if (! mkdir($directory, 0700) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the concurrency test coordination directory.');
        }

        $startFile = $directory.'/start';
        $processes = [];
        $resultFiles = [];
        $readyFiles = [];

        try {
            foreach ($appointments as $appointment) {
                $id = (string) $appointment->id;
                $readyFile = $directory.'/ready-'.$id;
                $resultFile = $directory.'/result-'.$id;
                $readyFiles[] = $readyFile;
                $resultFiles[] = $resultFile;

                $process = new Process([
                    PHP_BINARY,
                    base_path('artisan'),
                    'test',
                    'tests/Feature/AppointmentConcurrencyTest.php',
                    '--filter=test_concurrent_reschedules_cannot_double_book_a_doctor',
                    '--compact',
                ], base_path(), [
                    'APPOINTMENT_CONCURRENCY_TEST' => '1',
                    'APPOINTMENT_CONCURRENCY_WORKER' => '1',
                    'APPOINTMENT_ID' => $id,
                    'APPOINTMENT_START_FILE' => $startFile,
                    'APPOINTMENT_READY_FILE' => $readyFile,
                    'APPOINTMENT_RESULT_FILE' => $resultFile,
                    'APPOINTMENT_TARGET_START' => $targetStart->format('Y-m-d H:i:s'),
                ]);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 15;

            while (count(array_filter($readyFiles, 'is_file')) !== count($readyFiles)) {
                foreach ($processes as $process) {
                    if (! $process->isRunning()) {
                        throw new RuntimeException($process->getErrorOutput().$process->getOutput());
                    }
                }

                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('Concurrency workers did not become ready in time.');
                }

                usleep(10_000);
            }

            if (file_put_contents($startFile, 'start') === false) {
                throw new RuntimeException('Could not release the concurrency test workers.');
            }

            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue(
                    $process->isSuccessful(),
                    $process->getErrorOutput().$process->getOutput(),
                );
            }

            $outcomes = array_map(
                static fn (string $file): string|false => file_get_contents($file),
                $resultFiles,
            );

            $this->assertEqualsCanonicalizing(['conflict', 'success'], $outcomes);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }

            foreach ([$startFile, ...$readyFiles, ...$resultFiles] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            rmdir($directory);
        }
    }

    private function runWorker(): void
    {
        $appointmentId = $this->requiredEnvironmentValue('APPOINTMENT_ID');
        $startFile = $this->requiredEnvironmentValue('APPOINTMENT_START_FILE');
        $readyFile = $this->requiredEnvironmentValue('APPOINTMENT_READY_FILE');
        $resultFile = $this->requiredEnvironmentValue('APPOINTMENT_RESULT_FILE');
        $targetStart = $this->requiredEnvironmentValue('APPOINTMENT_TARGET_START');

        DB::connection()->getPdo();

        if (file_put_contents($readyFile, 'ready') === false) {
            throw new RuntimeException('Could not signal readiness to the concurrency test coordinator.');
        }

        $deadline = microtime(true) + 15;

        while (! is_file($startFile)) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Concurrency worker did not receive its start signal.');
            }

            usleep(10_000);
        }

        try {
            $appointment = Appointment::query()->findOrFail((int) $appointmentId);
            app(AppointmentService::class)->reschedule($appointment, Carbon::parse($targetStart), 30);
            $outcome = 'success';
        } catch (ValidationException) {
            $outcome = 'conflict';
        }

        if (file_put_contents($resultFile, $outcome) === false) {
            throw new RuntimeException('Could not write the concurrency worker outcome.');
        }
    }

    private function requiredEnvironmentValue(string $name): string
    {
        $value = getenv($name);

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("The {$name} environment variable is required.");
        }

        return $value;
    }
}

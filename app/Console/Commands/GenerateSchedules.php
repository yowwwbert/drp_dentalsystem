<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Clinic\Branches;
use App\Models\Clinic\Schedule;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateSchedules extends Command
{
    protected $signature = 'schedules:generate';
    protected $description = 'Maintain 30-day schedules for all branches by adding new days with 1-hour slots and removing expired unbooked schedules';

    public function handle()
    {
        $this->info('Checking and generating schedules to maintain 30-day coverage with 1-hour slots...');

        try {
            $branches = Branches::all();

            if ($branches->isEmpty()) {
                $this->error('No branches found.');
                return 1;
            }

            $today = Carbon::today();
            $endDate = $today->copy()->addDays(29); // August 25 to September 23, 2025
            $this->info("Generating schedules from {$today->toDateString()} to {$endDate->toDateString()}");

            // Delete only unbooked (is_active = true) schedules before today
            $deleted = Schedule::where('schedule_date', '<', $today->toDateString())
                ->where('is_active', true)
                ->delete();
            $this->info("Deleted {$deleted} expired unbooked schedules.");

            DB::beginTransaction();
            $schedulesCreated = 0;

            foreach ($branches as $branch) {
                $operatingDays = $branch->operating_days ?: [];
                $operatingDays = array_map('ucfirst', $operatingDays);

                // Find dentists assigned to this branch
                $dentists = User::where('user_type', 'Dentist')
                    ->join('user_branch', 'users.user_id', '=', 'user_branch.user_id')
                    ->where('user_branch.branch_id', $branch->branch_id)
                    ->pluck('users.user_id')
                    ->toArray();

                if (empty($dentists)) {
                    $this->info("No dentists found for branch {$branch->branch_id}. Skipping schedule assignment.");
                }

                $currentDate = $today->copy();
                while ($currentDate <= $endDate) {
                    $dayName = $currentDate->format('l');
                    if (!in_array($dayName, $operatingDays)) {
                        $this->info("No schedule created for branch {$branch->branch_id} on {$currentDate->toDateString()} (non-operating day).");
                        $currentDate->addDay();
                        continue;
                    }

                    // Validate and set default times if null
                    $openingTimeRaw = $branch->opening_time;
                    $closingTimeRaw = $branch->closing_time;
                    $openingTime = trim($openingTimeRaw) ?: '09:00:00';
                    $closingTime = trim($closingTimeRaw) ?: '17:00:00';

                    try {
                        $startTime = Carbon::createFromFormat('H:i:s', $openingTime);
                        $endTime = Carbon::createFromFormat('H:i:s', $closingTime);
                        $startTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                        $endTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                    } catch (\Exception $e) {
                        throw new \Exception("Time parsing failed for branch {$branch->branch_id}: opening={$openingTime}, closing={$closingTime}, error={$e->getMessage()}");
                    }

                    if ($endTime <= $startTime) {
                        throw new \Exception("Invalid operating hours for branch {$branch->branch_id}: closing time must be after opening time.");
                    }

                    // Generate 1-hour slots
                    $currentSlotStart = $startTime->copy();
                    while ($currentSlotStart < $endTime) {
                        $currentSlotEnd = $currentSlotStart->copy()->addHour();

                        // Ensure the slot end doesn't exceed closing time
                        if ($currentSlotEnd > $endTime) {
                            break;
                        }

                        $exists = Schedule::where('branch_id', $branch->branch_id)
                            ->where('schedule_date', $currentDate->toDateString())
                            ->where('start_time', $currentSlotStart->toTimeString())
                            ->where('end_time', $currentSlotEnd->toTimeString())
                            ->exists();

                        if (!$exists) {
                            $schedule = Schedule::create([
                                'schedule_id' => \Illuminate\Support\Str::uuid()->toString(),
                                'branch_id' => $branch->branch_id,
                                'schedule_date' => $currentDate->toDateString(),
                                'start_time' => $currentSlotStart->toTimeString(),
                                'end_time' => $currentSlotEnd->toTimeString(),
                                'is_active' => true,
                            ]);

                            // Validate schedule creation
                            if (!$schedule || !$schedule->exists || !$schedule->schedule_id) {
                                throw new \Exception("Failed to create schedule for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");
                            }

                            $schedulesCreated++;
                            $this->info("Created schedule ID {$schedule->schedule_id} for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");

                            // Auto-assign dentists to the generated schedule
                            if (!empty($dentists)) {
                                foreach ($dentists as $dentistId) {
                                    // Check if this dentist already has a schedule at this date and time
                                    $alreadyAssigned = DB::table('dentist_schedule')
                                        ->join('schedules', 'dentist_schedule.schedule_id', '=', 'schedules.schedule_id')
                                        ->where('dentist_schedule.dentist_id', $dentistId)
                                        ->where('schedules.schedule_date', $currentDate->toDateString())
                                        ->where('schedules.start_time', $currentSlotStart->toTimeString())
                                        ->where('schedules.end_time', $currentSlotEnd->toTimeString())
                                        ->exists();

                                    if (!$alreadyAssigned) {
                                        DB::table('dentist_schedule')->insert([
                                            'dentist_id' => $dentistId,
                                            'schedule_id' => $schedule->schedule_id,
                                        ]);
                                    }
                                }
                                $this->info("Assigned " . count($dentists) . " dentists to schedule ID {$schedule->schedule_id} (no duplicate assignments).");
                            }
                        }

                        $currentSlotStart->addHour();
                    }

                    $currentDate->addDay();
                }
            }

            DB::commit();
            $this->info("Successfully created {$schedulesCreated} new schedules.");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to maintain schedules: ' . $e->getMessage());
            $this->error('Failed to maintain schedules: ' . $e->getMessage());
            return 1;
        }
    }
}
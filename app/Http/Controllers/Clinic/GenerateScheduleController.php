<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Branches;
use App\Models\Clinic\Schedule;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class GenerateScheduleController extends Controller
{
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|string|exists:branches,branch_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $branchId = $request->input('branch_id');
            $branches = $branchId
                ? Branches::where('branch_id', $branchId)->get()
                : Branches::all();

            if ($branches->isEmpty()) {
                return response()->json([
                    'message' => 'No branches found.',
                ], 404);
            }

            $startDate = Carbon::today();
            $endDate = $startDate->copy()->addDays(29); // Maintain 30-day coverage
            $schedulesCreated = 0;

            // Valid day names for validation
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            DB::beginTransaction();

            foreach ($branches as $branch) {
                // operating_days should be an array due to model casting from JSON
                $operatingDays = $branch->operating_days;

                // Log the raw operating_days value for debugging
                Log::info("Processing branch {$branch->branch_id}: operating_days raw value = " . json_encode($operatingDays));

                // Attempt to parse if operating_days is a string
                if (is_string($operatingDays)) {
                    try {
                        $parsedDays = json_decode($operatingDays, true);
                        if (is_array($parsedDays)) {
                            $operatingDays = $parsedDays;
                            Log::info("Branch {$branch->branch_id}: Successfully parsed operating_days string to array: " . json_encode($operatingDays));
                        } else {
                            Log::warning("Branch {$branch->branch_id}: operating_days is a string but not valid JSON: {$operatingDays}");
                            $operatingDays = [];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Branch {$branch->branch_id}: Failed to parse operating_days as JSON: {$operatingDays}, error: {$e->getMessage()}");
                        $operatingDays = [];
                    }
                }

                // Validate operating_days
                if (!is_array($operatingDays)) {
                    Log::warning("Branch {$branch->branch_id}: operating_days is not an array: " . gettype($operatingDays));
                    continue;
                }

                if (empty($operatingDays)) {
                    Log::warning("Branch {$branch->branch_id}: operating_days is empty.");
                    continue;
                }

                // Ensure operating_days contains valid day names
                $operatingDays = array_filter(array_map('ucfirst', $operatingDays), function ($day) use ($validDays) {
                    return in_array($day, $validDays);
                });

                if (empty($operatingDays)) {
                    Log::warning("Branch {$branch->branch_id}: No valid operating days after filtering: " . json_encode($branch->operating_days));
                    continue;
                }

                // Log valid operating days
                Log::info("Branch {$branch->branch_id}: Valid operating days: " . json_encode($operatingDays));

                // Find dentists assigned to this branch
                $dentists = User::where('user_type', 'Dentist')
                    ->join('user_branch', 'users.user_id', '=', 'user_branch.user_id')
                    ->where('user_branch.branch_id', $branch->branch_id)
                    ->pluck('users.user_id')
                    ->toArray();

                if (empty($dentists)) {
                    Log::info("No dentists found for branch {$branch->branch_id}. Schedules will be created without dentist assignments.");
                } else {
                    Log::info("Found " . count($dentists) . " dentists for branch {$branch->branch_id}.");
                }

                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $dayName = $currentDate->format('l');
                    if (!in_array($dayName, $operatingDays)) {
                        $currentDate->addDay();
                        continue;
                    }

                    // Validate and set default times if null
                    $openingTimeRaw = $branch->opening_time;
                    $closingTimeRaw = $branch->closing_time;
                    $openingTime = trim($openingTimeRaw) ?: '09:00';
                    $closingTime = trim($closingTimeRaw) ?: '17:00';

                    // Log time values for debugging
                    Log::info("Branch {$branch->branch_id} on {$currentDate->toDateString()}: opening_time = {$openingTime}, closing_time = {$closingTime}");

                    try {
                        // Try parsing as H:i first, then fallback to H:i:s
                        try {
                            $startTime = Carbon::createFromFormat('H:i', $openingTime);
                            $endTime = Carbon::createFromFormat('H:i', $closingTime);
                        } catch (\Exception $e) {
                            Log::info("Branch {$branch->branch_id}: Failed to parse time as H:i, attempting H:i:s. Error: {$e->getMessage()}");
                            $startTime = Carbon::createFromFormat('H:i:s', $openingTime);
                            $endTime = Carbon::createFromFormat('H:i:s', $closingTime);
                        }
                        $startTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                        $endTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                    } catch (\Exception $e) {
                        Log::error("Time parsing failed for branch {$branch->branch_id} on {$currentDate->toDateString()}: opening={$openingTime}, closing={$closingTime}, error={$e->getMessage()}");
                        throw new \Exception("Time parsing failed for branch {$branch->branch_id} on {$currentDate->toDateString()}: opening={$openingTime}, closing={$closingTime}, error={$e->getMessage()}");
                    }

                    if ($endTime <= $startTime) {
                        Log::error("Invalid operating hours for branch {$branch->branch_id} on {$currentDate->toDateString()}: closing time ({$closingTime}) must be after opening time ({$openingTime}).");
                        throw new \Exception("Invalid operating hours for branch {$branch->branch_id} on {$currentDate->toDateString()}: closing time must be after opening time.");
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
                                Log::error("Failed to create schedule for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");
                                throw new \Exception("Failed to create schedule for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");
                            }

                            $schedulesCreated++;
                            Log::info("Created schedule ID {$schedule->schedule_id} for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");

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
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        Log::info("Assigned dentist ID {$dentistId} to schedule ID {$schedule->schedule_id} for branch {$branch->branch_id}.");
                                    }
                                }
                                Log::info("Assigned " . count($dentists) . " dentists to schedule ID {$schedule->schedule_id} (no duplicate assignments).");
                            }
                        }

                        $currentSlotStart->addHour();
                    }

                    $currentDate->addDay();
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully created {$schedulesCreated} schedule slots for " . $branches->count() . " branch(es).",
                'details' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'branches_processed' => $branches->pluck('branch_id')->toArray(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to generate schedules: {$e->getMessage()}");

            return response()->json([
                'message' => 'Failed to generate schedules.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
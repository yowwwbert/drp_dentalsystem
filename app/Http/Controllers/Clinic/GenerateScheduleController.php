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
            $endDate = $startDate->copy()->addDays(29); // August 10 to September 8, 2025
            $schedulesCreated = 0;

            DB::beginTransaction();

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
                    Log::info("No dentists found for branch {$branch->branch_id}. Skipping schedule assignment.");
                }

                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $dayName = $currentDate->format('l');
                    if (!in_array($dayName, $operatingDays)) {
                        $currentDate->addDay();
                        continue;
                    }

                    // Validate and set default times if null
                    $openingTime = trim($branch->opening_time) ?: '09:00';
                    $closingTime = trim($branch->closing_time) ?: '17:00';

                    try {
                        $startTime = Carbon::createFromFormat('H:i:s', $openingTime);
                        $endTime = Carbon::createFromFormat('H:i:s', $closingTime);
                        $startTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                        $endTime->setDate($currentDate->year, $currentDate->month, $currentDate->day);
                    } catch (\Exception $e) {
                        throw new \Exception("Invalid time format for branch {$branch->branch_id}: opening={$openingTime}, closing={$closingTime}, error={$e->getMessage()}");
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
                            ->where('start_time', $currentSlotStart->toDateTimeString())
                            ->where('end_time', $currentSlotEnd->toDateTimeString())
                            ->exists();

                        if (!$exists) {
                            $schedule = Schedule::create([
                                'branch_id' => $branch->branch_id,
                                'schedule_date' => $currentDate->toDateString(),
                                'start_time' => $currentSlotStart->toDateTimeString(),
                                'end_time' => $currentSlotEnd->toDateTimeString(),
                                'is_active' => true,
                            ]);

                            // Validate schedule creation
                            if (!$schedule || !$schedule->exists || !$schedule->schedule_id) {
                                throw new \Exception("Failed to create schedule for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");
                            }

                            $schedulesCreated++;
                            Log::info("Created schedule ID {$schedule->schedule_id} for branch {$branch->branch_id} on {$currentDate->toDateString()} (from {$currentSlotStart->toTimeString()} to {$currentSlotEnd->toTimeString()}).");

                            // Auto-assign dentists to the generated schedule
                            if (!empty($dentists)) {
                                foreach ($dentists as $dentistId) {
                                    DB::table('dentist_schedule')->insert([
                                        'dentist_id' => $dentistId,
                                        'schedule_id' => $schedule->schedule_id,
                                    ]);
                                }
                                Log::info("Assigned " . count($dentists) . " dentists to schedule ID {$schedule->schedule_id}.");
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
                'error' => 'An error occurred. Please check logs for details.',
            ], 500);
        }
    }
}
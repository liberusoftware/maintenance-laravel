<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateAttendance;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateEngineerSkill;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateExpense;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateLaborRate;
use Liberu\Modules\Maintenance\LaborAndTime\Models\Attendance;
use Liberu\Modules\Maintenance\LaborAndTime\Models\EngineerSkill;
use Liberu\Modules\Maintenance\LaborAndTime\Models\Expense;
use Liberu\Modules\Maintenance\LaborAndTime\Models\LaborRate;

final class LaborRecordController extends Controller
{
    public function skills(Request $request, CreateEngineerSkill $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'user_id' => ['required', 'integer'],
                'skill' => ['required', 'string', 'max:160'],
                'level' => ['required', 'integer', 'between:1,5'],
                'certified_until' => ['nullable', 'date'],
                'metadata' => ['nullable', 'array'],
            ]);

            return response()->json(['data' => $this->skillResource($create->handle($teamId, $data))], 201);
        }

        return response()->json(['data' => EngineerSkill::query()->where('team_id', $teamId)->latest()->get()->map(fn (EngineerSkill $skill): array => $this->skillResource($skill))->values()]);
    }

    public function attendance(Request $request, CreateAttendance $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'user_id' => ['required', 'integer'],
                'attendance_date' => ['required', 'date'],
                'clocked_in_at' => ['nullable', 'date'],
                'clocked_out_at' => ['nullable', 'date', 'after:clocked_in_at'],
                'status' => ['nullable', 'string', 'in:present,absent,leave'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            return response()->json(['data' => $this->attendanceResource($create->handle($teamId, $data))], 201);
        }

        return response()->json(['data' => Attendance::query()->where('team_id', $teamId)->latest('attendance_date')->get()->map(fn (Attendance $attendance): array => $this->attendanceResource($attendance))->values()]);
    }

    public function rates(Request $request, CreateLaborRate $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'user_id' => ['nullable', 'integer'],
                'name' => ['required', 'string', 'max:160'],
                'hourly_rate' => ['required', 'numeric', 'min:0'],
                'currency' => ['nullable', 'string', 'size:3'],
                'effective_from' => ['required', 'date'],
                'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            ]);

            return response()->json(['data' => $this->rateResource($create->handle($teamId, $data))], 201);
        }

        return response()->json(['data' => LaborRate::query()->where('team_id', $teamId)->latest('effective_from')->get()->map(fn (LaborRate $rate): array => $this->rateResource($rate))->values()]);
    }

    public function expenses(Request $request, CreateExpense $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'user_id' => ['nullable', 'integer'],
                'work_order_id' => ['nullable', 'integer'],
                'description' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'min:0'],
                'currency' => ['nullable', 'string', 'size:3'],
                'metadata' => ['nullable', 'array'],
            ]);
            $data['user_id'] ??= $request->user()->getKey();

            return response()->json(['data' => $this->expenseResource($create->handle($teamId, $data))], 201);
        }

        return response()->json(['data' => Expense::query()->where('team_id', $teamId)->latest()->get()->map(fn (Expense $expense): array => $this->expenseResource($expense))->values()]);
    }

    private function teamId(Request $request): ?int
    {
        return $request->user()?->currentTeam?->getKey() === null ? null : (int) $request->user()->currentTeam->getKey();
    }

    private function skillResource(EngineerSkill $skill): array
    {
        return ['id' => (string) $skill->getKey(), 'type' => 'maintenance-engineer-skill', 'attributes' => ['user_id' => $skill->user_id, 'skill' => $skill->skill, 'level' => $skill->level, 'certified_until' => $skill->certified_until?->toDateString(), 'certified' => $skill->isCertified(), 'metadata' => $skill->metadata]];
    }

    private function attendanceResource(Attendance $attendance): array
    {
        return ['id' => (string) $attendance->getKey(), 'type' => 'maintenance-attendance', 'attributes' => ['user_id' => $attendance->user_id, 'attendance_date' => $attendance->attendance_date?->toDateString(), 'clocked_in_at' => $attendance->clocked_in_at?->toISOString(), 'clocked_out_at' => $attendance->clocked_out_at?->toISOString(), 'duration_minutes' => $attendance->durationMinutes(), 'status' => $attendance->status, 'notes' => $attendance->notes]];
    }

    private function rateResource(LaborRate $rate): array
    {
        return ['id' => (string) $rate->getKey(), 'type' => 'maintenance-labor-rate', 'attributes' => ['user_id' => $rate->user_id, 'name' => $rate->name, 'hourly_rate' => $rate->hourly_rate, 'currency' => $rate->currency, 'effective_from' => $rate->effective_from?->toDateString(), 'effective_until' => $rate->effective_until?->toDateString(), 'active' => $rate->isActive()]];
    }

    private function expenseResource(Expense $expense): array
    {
        return ['id' => (string) $expense->getKey(), 'type' => 'maintenance-expense', 'attributes' => ['user_id' => $expense->user_id, 'work_order_id' => $expense->work_order_id, 'description' => $expense->description, 'amount' => $expense->amount, 'currency' => $expense->currency, 'status' => $expense->status, 'metadata' => $expense->metadata]];
    }
}

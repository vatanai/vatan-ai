<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmAttendance;
use App\Models\Crm\CrmPersonnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CrmAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CrmAttendance::with('personnel')
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->filled('personnel_id')) {
            $query->where('personnel_id', $request->personnel_id);
        }
        if ($request->filled('month')) {
            // month مثل 1404/04
            $query->where('date', 'like', $request->month . '%');
        }

        return response()->json($query->get()->map(fn($r) => $this->formatRecord($r)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'personnel_id' => 'required|uuid',
            'date'         => 'required|string|max:20',
            'check_in'     => 'required|string|max:5',
            'check_out'    => 'nullable|string|max:5',
            'note'         => 'nullable|string|max:500',
        ]);

        $data['total_hours'] = CrmAttendance::calcHours($data['check_in'], $data['check_out'] ?? null);

        // اگر رکورد همان روز وجود داشت، آپدیت کن
        $record = CrmAttendance::updateOrCreate(
            ['personnel_id' => $data['personnel_id'], 'date' => $data['date']],
            $data
        );

        return response()->json($this->formatRecord($record->load('personnel')), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $record = CrmAttendance::findOrFail($id);

        $data = $request->validate([
            'personnel_id' => 'sometimes|uuid',
            'date'         => 'sometimes|string|max:20',
            'check_in'     => 'sometimes|string|max:5',
            'check_out'    => 'nullable|string|max:5',
            'note'         => 'nullable|string|max:500',
        ]);

        $checkIn  = $data['check_in']  ?? $record->check_in;
        $checkOut = array_key_exists('check_out', $data) ? $data['check_out'] : $record->check_out;
        $data['total_hours'] = CrmAttendance::calcHours($checkIn, $checkOut);

        $record->update($data);

        return response()->json($this->formatRecord($record->load('personnel')));
    }

    public function destroy(string $id): JsonResponse
    {
        CrmAttendance::findOrFail($id)->delete();
        return response()->json(['message' => 'رکورد حذف شد']);
    }

    // ─── Export Excel (CSV) ──────────────────────────────────

    public function export(Request $request): Response
    {
        $month = $request->input('month', '');
        $personnelId = $request->input('personnel_id', '');

        $query = CrmAttendance::with('personnel')->orderBy('date')->orderBy('check_in');

        if ($month) $query->where('date', 'like', $month . '%');
        if ($personnelId) $query->where('personnel_id', $personnelId);

        $records = $query->get();

        $rows = [['ردیف', 'نام پرسنل', 'تاریخ', 'ساعت ورود', 'ساعت خروج', 'مجموع کارکرد', 'یادداشت']];
        foreach ($records as $i => $r) {
            $rows[] = [
                $i + 1,
                $r->personnel?->name ?? '—',
                $r->date,
                $r->check_in ?? '—',
                $r->check_out ?? '—',
                $r->total_hours ? number_format($r->total_hours, 2) . ' ساعت' : '—',
                $r->note ?? '',
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="attendance_' . ($month ?: date('Y-m')) . '.csv"',
        ]);
    }

    // ─── Private ─────────────────────────────────────────────

    private function formatRecord(CrmAttendance $r): array
    {
        return [
            'id'           => $r->id,
            'personnelId'  => $r->personnel_id,
            'personnel'    => $r->relationLoaded('personnel') ? $r->personnel : null,
            'date'         => $r->date,
            'checkIn'      => $r->check_in,
            'checkOut'     => $r->check_out,
            'totalHours'   => $r->total_hours,
            'note'         => $r->note,
        ];
    }
}

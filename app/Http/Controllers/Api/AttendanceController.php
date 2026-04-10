<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:present,absent',
            'participation_level' => 'nullable|integer|min:1|max:4',
        ]);

        if (isset($validated['status'])) {
            $attendance->status = $validated['status'];
            // Clear participation if marked absent
            if ($validated['status'] === 'absent') {
                $attendance->participation_level = null;
            }
        }
        if (array_key_exists('participation_level', $validated)) {
            $attendance->participation_level = $validated['participation_level'];
        }

        $attendance->save();

        return response()->json(['data' => $attendance->fresh()]);
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();
        return response()->json(null, 204);
    }
}

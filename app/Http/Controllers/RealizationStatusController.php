<?php

namespace App\Http\Controllers;

use App\Models\Realization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RealizationStatusController extends Controller
{
    public function __invoke(Request $request, Realization $realization): JsonResponse
    {
        $this->authorize('update', $realization);

        $validated = $request->validate([
            'status_realisasi' => ['required', 'boolean'],
        ]);

        $realization->refresh();

        if ($validated['status_realisasi']) {
            if ((float) $realization->total_realization <= 0) {
                return response()->json([
                    'message' => 'Data realisasi belum lengkap. Input dan simpan realisasi terlebih dahulu.',
                ], 422);
            }
        }

        $realization->status_realisasi = (bool) $validated['status_realisasi'];
        $realization->save();

        return response()->json([
            'id' => $realization->id,
            'status_realisasi' => (bool) $realization->status_realisasi,
            'updated_by' => Auth::id(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ChapterReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterReportController extends Controller
{
    public function store(Request $request, Chapter $chapter): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:page_manquante,ordre_inverse,image_corrompue,mauvaise_traduction,autre'],
            'page_number' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = ChapterReport::create([
            'chapter_id' => $chapter->id,
            'manga_id' => $chapter->manga_id,
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'page_number' => $validated['page_number'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'en_attente',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Merci ! Votre signalement a été transmis à l\'équipe.',
            'report_id' => $report->id,
        ]);
    }
}

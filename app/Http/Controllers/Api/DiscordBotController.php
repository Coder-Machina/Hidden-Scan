<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\StaffContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiscordBotController extends Controller
{
    protected function authorizeBot(Request $request): bool
    {
        $secret = config('services.discord.bot_secret', env('DISCORD_BOT_SECRET', 'hiddenscan-secret-bot-key'));
        $headerToken = $request->header('X-Bot-Token') ?? $request->bearerToken();
        return $headerToken === $secret;
    }

    /**
     * Liste des mangas pour l'autocomplétion des slash commands (/fiche, /rush, etc.)
     */
    public function listMangas(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $mangas = Manga::query()
            ->select(['id', 'title', 'slug', 'discord_role_id', 'cover_image', 'is_external', 'team_name'])
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'db' => config('database.default'),
            'mangas' => $mangas,
        ]);
    }

    /**
     * Récupère les données d'une fiche de projet complète.
     */
    public function getFiche(string $slug, Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $manga = Manga::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int)$slug : null)
            ->first();

        if (!$manga) {
            return response()->json(['error' => 'Manga introuvable'], 404);
        }

        $latestFrChapter = $manga->chapters()
            ->published()
            ->orderBy('number', 'desc')
            ->value('number') ?? 0;

        $coverUrl = null;
        if ($manga->cover_image) {
            $coverUrl = str_starts_with($manga->cover_image, 'http')
                ? $manga->cover_image
                : url(Storage::url($manga->cover_image));
        }

        return response()->json([
            'success' => true,
            'manga' => [
                'id' => $manga->id,
                'title' => $manga->title,
                'slug' => $manga->slug,
                'cover_image' => $manga->cover_image,
                'cover_url' => $coverUrl,
                'drive_url' => $manga->drive_url,
                'va_url' => $manga->va_url,
                'va_chapter' => (float)$manga->va_chapter_number,
                'fr_chapter' => (float)$latestFrChapter,
                'is_rush' => (bool)$manga->is_rush,
                'discord_role_id' => $manga->discord_role_id,
                'staff' => [
                    'trad' => $manga->trad_user ?? 'Non assigné',
                    'check' => $manga->check_user ?? 'Non assigné',
                    'clean' => $manga->clean_user ?? 'Non assigné',
                    'edit' => $manga->edit_user ?? 'Non assigné',
                    'qc' => $manga->qc_user ?? 'Non assigné',
                ],
                'steps' => [
                    'trad' => (float)$manga->step_trad_chapter,
                    'check' => (float)$manga->step_check_chapter,
                    'clean' => (float)$manga->step_clean_chapter,
                    'edit' => (float)$manga->step_edit_chapter,
                    'qc' => (float)$manga->step_qc_chapter,
                ],
            ],
        ]);
    }

    /**
     * Associe un rôle Discord à un manga pour les notifications de sorties.
     */
    public function linkRole(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $slug = $request->input('slug');
        $roleId = $request->input('role_id');

        $manga = Manga::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int)$slug : null)
            ->first();

        if (!$manga) {
            return response()->json(['error' => 'Manga introuvable'], 404);
        }

        $manga->discord_role_id = $roleId;
        $manga->save();

        return response()->json([
            'success' => true,
            'message' => "Rôle Discord {$roleId} associé avec succès à {$manga->title}",
        ]);
    }

    /**
     * Enregistre l'avancement d'une étape (bouton Trad, Check, Clean, Edit ou QC).
     */
    public function recordStepDone(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $validated = $request->validate([
            'slug' => 'required|string',
            'step' => 'required|in:trad,check,clean,edit,qc',
            'discord_user_id' => 'nullable|string',
            'discord_user_name' => 'nullable|string',
            'chapter_number' => 'nullable|numeric',
        ]);

        $manga = Manga::where('slug', $validated['slug'])
            ->orWhere('id', is_numeric($validated['slug']) ? (int)$validated['slug'] : null)
            ->firstOrFail();

        $column = 'step_' . $validated['step'] . '_chapter';
        $currentVal = (float)$manga->{$column};
        $newNumber = isset($validated['chapter_number']) ? (float)$validated['chapter_number'] : $currentVal + 1;

        $manga->update([
            $column => $newNumber,
        ]);

        // Enregistre la contribution dans l'historique du staff
        StaffContribution::create([
            'manga_id' => $manga->id,
            'step' => $validated['step'],
            'chapter_number' => $newNumber,
            'discord_user_id' => $validated['discord_user_id'] ?? null,
            'discord_user_name' => $validated['discord_user_name'] ?? 'Staff',
        ]);

        // Détermine la prochaine étape et le staff à notifier selon le flux d'équipe :
        // - Trad -> Check
        // - Clean -> Edit
        // - Check -> Edit
        // - Edit -> Prêt pour publication (Notification des 2 admins)
        // - QC -> Prêt pour publication (Notification des 2 admins)
        $adminMentions = env('DISCORD_ADMIN_MENTIONS', '<@979449727047323668> <@1310979117172391940>');

        $nextStepInfo = match($validated['step']) {
            'trad' => ['next_step' => 'check', 'next_user' => $manga->check_user, 'label' => 'Check'],
            'clean' => ['next_step' => 'edit', 'next_user' => $manga->edit_user, 'label' => 'Édition (Edit)'],
            'check' => ['next_step' => 'edit', 'next_user' => $manga->edit_user, 'label' => 'Édition (Edit)'],
            'edit' => ['next_step' => 'publication', 'next_user' => $adminMentions, 'label' => 'Publication (Admins)'],
            'qc' => ['next_step' => 'publication', 'next_user' => $adminMentions, 'label' => 'Publication (Admins)'],
        };

        return response()->json([
            'success' => true,
            'step' => $validated['step'],
            'chapter_number' => $newNumber,
            'next_step' => $nextStepInfo,
            'manga' => $this->getFiche($manga->slug, $request)->getData(true)['manga'],
        ]);
    }

    /**
     * Enregistre ou met à jour la fiche complète d'un projet (liens Drive/VA, staff, avancement).
     */
    public function saveFiche(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $validated = $request->validate([
            'slug' => 'required|string',
            'title' => 'nullable|string',
            'drive_url' => 'nullable|string',
            'va_url' => 'nullable|string',
            'va_chapter_number' => 'nullable|numeric',
            'cover_image' => 'nullable|string',
            'is_rush' => 'nullable|boolean',
            'discord_role_id' => 'nullable|string',
            'trad_user' => 'nullable|string',
            'check_user' => 'nullable|string',
            'clean_user' => 'nullable|string',
            'edit_user' => 'nullable|string',
            'qc_user' => 'nullable|string',
            'step_trad_chapter' => 'nullable|numeric',
            'step_check_chapter' => 'nullable|numeric',
            'step_clean_chapter' => 'nullable|numeric',
            'step_edit_chapter' => 'nullable|numeric',
            'step_qc_chapter' => 'nullable|numeric',
        ]);

        $manga = Manga::where('slug', $validated['slug'])
            ->orWhere('title', $validated['title'] ?? '')
            ->first();

        if (!$manga) {
            $manga = Manga::create([
                'title' => $validated['title'] ?? ucfirst(str_replace('-', ' ', $validated['slug'])),
                'slug' => $validated['slug'],
                'synopsis' => 'Ajouté via Discord Bot',
                'type' => \App\Enums\MangaType::MANGA,
                'status' => \App\Enums\MangaStatus::EN_COURS,
            ]);
        }

        $fieldsToUpdate = array_filter($validated, fn ($val) => !is_null($val));
        unset($fieldsToUpdate['slug']);
        $manga->update($fieldsToUpdate);

        return response()->json([
            'success' => true,
            'message' => "Fiche « {$manga->title} » mise à jour avec succès",
            'manga' => $this->getFiche($manga->slug, $request)->getData(true)['manga'],
        ]);
    }

    /**
     * Active ou désactive le mode Rush sur un projet.
     */
    public function toggleRush(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $request->validate(['slug' => 'required|string']);

        $manga = Manga::where('slug', $request->slug)
            ->orWhere('id', is_numeric($request->slug) ? (int)$request->slug : null)
            ->firstOrFail();

        $manga->update([
            'is_rush' => !$manga->is_rush,
        ]);

        return response()->json([
            'success' => true,
            'is_rush' => $manga->is_rush,
            'manga' => $this->getFiche($manga->slug, $request)->getData(true)['manga'],
        ]);
    }

    /**
     * Statistiques de production du staff (classement et comptage des chapitres).
     */
    public function getStaffStats(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $days = (int)$request->get('days', 30);
        $since = now()->subDays($days);

        $statsByUser = StaffContribution::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(discord_user_name, discord_user_id) as member, step, count(*) as count')
            ->groupBy('member', 'step')
            ->get();

        $totals = StaffContribution::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(discord_user_name, discord_user_id) as member, count(*) as total_chapters')
            ->groupBy('member')
            ->orderByDesc('total_chapters')
            ->take(20)
            ->get();

        $stepTotals = StaffContribution::where('created_at', '>=', $since)
            ->selectRaw('step, count(*) as count')
            ->groupBy('step')
            ->pluck('count', 'step');

        return response()->json([
            'success' => true,
            'period_days' => $days,
            'leaderboard' => $totals,
            'by_step' => $stepTotals,
            'details' => $statsByUser,
        ]);
    }

    /**
     * Rappels Agenda : Projets en retard ou sans activité récente.
     */
    public function getAgendaReminders(Request $request): JsonResponse
    {
        if (!$this->authorizeBot($request)) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        // Projets en mode Rush ou dont l'écart entre la VA et la FR dépasse 3 chapitres
        $projectsToRemind = Manga::where('is_rush', true)
            ->orWhereRaw('va_chapter_number > 0 AND (va_chapter_number - step_trad_chapter) >= 3')
            ->get();

        $reminders = $projectsToRemind->map(function ($m) {
            $delay = max(0, (float)$m->va_chapter_number - (float)$m->step_trad_chapter);
            return [
                'manga' => $m->title,
                'slug' => $m->slug,
                'is_rush' => $m->is_rush,
                'va_chapter' => (float)$m->va_chapter_number,
                'trad_chapter' => (float)$m->step_trad_chapter,
                'chapters_behind' => $delay,
                'trad_user' => $m->trad_user,
                'check_user' => $m->check_user,
                'clean_user' => $m->clean_user,
                'edit_user' => $m->edit_user,
                'qc_user' => $m->qc_user,
            ];
        });

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
        ]);
    }
}

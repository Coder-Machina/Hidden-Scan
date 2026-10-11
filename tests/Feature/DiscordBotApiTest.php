<?php

namespace Tests\Feature;

use App\Models\Manga;
use App\Models\StaffContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordBotApiTest extends TestCase
{
    use RefreshDatabase;

    private string $botToken = 'hiddenscan-secret-bot-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.discord.bot_secret' => $this->botToken]);
    }

    public function test_bot_api_requires_authorization(): void
    {
        $response = $this->getJson('/api/bot/mangas');
        $response->assertStatus(401);

        $responseAuthorized = $this->withHeader('X-Bot-Token', $this->botToken)
            ->getJson('/api/bot/mangas');
        $responseAuthorized->assertStatus(200);
    }

    public function test_bot_api_can_get_fiche_and_update_steps(): void
    {
        $manga = Manga::create([
            'title' => 'The Executioner',
            'slug' => 'the-executioner',
            'synopsis' => 'Test synopsis',
            'type' => 'manhwa',
            'status' => 'en_cours',
            'drive_url' => 'https://drive.google.com/test',
            'va_url' => 'https://asurascans.com/test',
            'va_chapter_number' => 77,
            'trad_user' => '@Neksyos',
            'check_user' => '@Klate',
            'clean_user' => '@focah',
            'edit_user' => '@focah',
            'qc_user' => '@qc_member',
            'step_trad_chapter' => 76,
            'step_check_chapter' => 74,
            'step_clean_chapter' => 74,
            'step_edit_chapter' => 74,
            'step_qc_chapter' => 0,
        ]);

        // 1. Get Fiche
        $ficheRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->getJson("/api/bot/fiche/{$manga->slug}");

        $ficheRes->assertStatus(200)
            ->assertJsonPath('manga.title', 'The Executioner')
            ->assertJsonPath('manga.steps.trad', 76)
            ->assertJsonPath('manga.staff.trad', '@Neksyos');

        // 2. Click Trad button (Step done)
        $stepRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->postJson('/api/bot/step-done', [
                'slug' => $manga->slug,
                'step' => 'trad',
                'discord_user_id' => '123456789',
                'discord_user_name' => 'Neksyos',
            ]);

        $stepRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('chapter_number', 77)
            ->assertJsonPath('next_step.next_step', 'check')
            ->assertJsonPath('next_step.next_user', '@Klate');

        $this->assertEquals(77, $manga->fresh()->step_trad_chapter);

        // 3. Clean finishes -> pings Edit
        $cleanRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->postJson('/api/bot/step-done', [
                'slug' => $manga->slug,
                'step' => 'clean',
                'discord_user_name' => 'focah',
            ]);
        $cleanRes->assertStatus(200)
            ->assertJsonPath('next_step.next_step', 'edit')
            ->assertJsonPath('next_step.next_user', '@focah');

        // 4. Check finishes -> pings Edit
        $checkRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->postJson('/api/bot/step-done', [
                'slug' => $manga->slug,
                'step' => 'check',
                'discord_user_name' => 'Klate',
            ]);
        $checkRes->assertStatus(200)
            ->assertJsonPath('next_step.next_step', 'edit')
            ->assertJsonPath('next_step.next_user', '@focah');

        // 5. Verify StaffContribution logged
        $this->assertDatabaseHas('staff_contributions', [
            'manga_id' => $manga->id,
            'step' => 'trad',
            'chapter_number' => 77,
            'discord_user_name' => 'Neksyos',
        ]);

        // 6. Test saveFiche endpoint
        $saveRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->postJson('/api/bot/save-fiche', [
                'slug' => $manga->slug,
                'drive_url' => 'https://drive.google.com/updated',
                'va_url' => 'https://va-link.com/updated',
                'va_chapter_number' => 80,
                'trad_user' => '<@1163161538652291074>',
                'check_user' => '<@929297904852697138>',
            ]);

        $saveRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('manga.drive_url', 'https://drive.google.com/updated')
            ->assertJsonPath('manga.va_url', 'https://va-link.com/updated')
            ->assertJsonPath('manga.staff.trad', '<@1163161538652291074>');

        $this->assertEquals('https://drive.google.com/updated', $manga->fresh()->drive_url);
    }

    public function test_bot_api_can_toggle_rush_and_fetch_stats(): void
    {
        $manga = Manga::create([
            'title' => 'Solo Rush Manga',
            'slug' => 'solo-rush-manga',
            'synopsis' => 'Test',
            'type' => 'manga',
            'status' => 'en_cours',
            'is_rush' => false,
            'va_chapter_number' => 50,
            'step_trad_chapter' => 45,
        ]);

        // Toggle rush on
        $rushRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->postJson('/api/bot/toggle-rush', ['slug' => $manga->slug]);

        $rushRes->assertStatus(200)
            ->assertJsonPath('is_rush', true);

        $this->assertTrue($manga->fresh()->is_rush);

        // Check agenda reminders
        $agendaRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->getJson('/api/bot/agenda-reminders');

        $agendaRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNotEmpty($agendaRes->json('reminders'));

        // Check staff stats endpoint
        StaffContribution::create([
            'manga_id' => $manga->id,
            'step' => 'clean',
            'chapter_number' => 10,
            'discord_user_name' => 'SuperCleaner',
        ]);

        $statsRes = $this->withHeader('X-Bot-Token', $this->botToken)
            ->getJson('/api/bot/staff-stats?days=30');

        $statsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('by_step.clean', 1);
    }
}

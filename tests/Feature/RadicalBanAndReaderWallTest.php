<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RadicalBanAndReaderWallTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_is_radically_locked_out_and_logged_out(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => 'Comportement toxique répété',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(403);
        $response->assertViewIs('errors.banned');
        $response->assertSee('Accès Bloqué');
        $response->assertSee('Comportement toxique répété');
        $this->assertGuest();
    }

    public function test_temporary_ban_expires_automatically(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now()->subHours(5),
            'banned_until' => now()->subHour(), // Expired 1 hour ago
            'ban_reason' => 'Ban temporaire 4h',
        ]);

        $this->assertFalse($user->isBanned());

        $user->refresh();
        $this->assertFalse($user->is_banned);
        $this->assertNull($user->banned_until);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
    }

    public function test_banned_ip_blocks_site_access(): void
    {
        DB::table('banned_ips')->insert([
            'ip_address' => '198.51.100.42',
            'ip_hash' => sha1('198.51.100.42'),
            'ban_type' => 'all',
            'reason' => 'Attaque DDoS ou spam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])->get('/');

        $response->assertStatus(403);
        $response->assertViewIs('errors.banned');
        $response->assertSee('Votre adresse IP a été bannie');
        $response->assertSee('Attaque DDoS ou spam');
    }

    public function test_temporary_banned_ip_expires_automatically(): void
    {
        DB::table('banned_ips')->insert([
            'ip_address' => '198.51.100.43',
            'ip_hash' => sha1('198.51.100.43'),
            'ban_type' => 'all',
            'reason' => 'Ban IP 2h',
            'banned_until' => now()->subMinutes(10), // Expired
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.43'])->get('/');
        $response->assertOk();

        $this->assertDatabaseMissing('banned_ips', ['ip_address' => '198.51.100.43']);
    }

    public function test_staff_bypasses_ip_ban(): void
    {
        $admin = User::where('email', 'admin@hiddenscan.com')->first();
        if (! $admin) {
            $admin = User::factory()->create(['email' => 'admin_test_' . uniqid() . '@hiddenscan.com']);
            $admin->assignRole('admin');
        }

        DB::table('banned_ips')->insert([
            'ip_address' => '198.51.100.99',
            'ip_hash' => sha1('198.51.100.99'),
            'ban_type' => 'all',
            'reason' => 'Test IP ban',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
            ->get('/');

        $response->assertOk();
    }

    public function test_guest_reader_sees_login_wall_after_preview_limit(): void
    {
        $manga = Manga::create([
            'title' => 'Solo Leveling Test',
            'slug' => 'solo-leveling-test',
            'synopsis' => 'Test synopsis',
            'type' => 'manhwa',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Prologue',
            'slug' => 'prologue',
            'status' => \App\Enums\ChapterStatus::PUBLIE,
            'published_at' => now()->subDay(),
        ]);

        // Créer 6 pages
        for ($i = 1; $i <= 6; $i++) {
            ChapterPage::create([
                'chapter_id' => $chapter->id,
                'page_number' => $i,
                'image_path' => "chapters/{$chapter->id}/page_{$i}.jpg",
            ]);
        }

        // Visiteur non connecté
        $response = $this->get(route('chapter.show', [$manga->slug, $chapter->slug]));

        $response->assertOk();
        $response->assertSee('Aperçu Gratuit Terminé');
        $response->assertSee('Continuez votre lecture gratuitement');
        $response->assertSee('Débloquer la suite en 1 clic (Pass Gratuit)');
        $response->assertSee(route('login', ['redirect' => route('chapter.show', [$manga->slug, $chapter->slug])]));
    }

    public function test_authenticated_user_does_not_see_reader_login_wall(): void
    {
        $user = User::factory()->create();

        $manga = Manga::create([
            'title' => 'Solo Leveling Test 2',
            'slug' => 'solo-leveling-test-2',
            'synopsis' => 'Test synopsis',
            'type' => 'manhwa',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 2,
            'title' => 'Chapitre 2',
            'slug' => 'chapitre-2',
            'status' => \App\Enums\ChapterStatus::PUBLIE,
            'published_at' => now()->subDay(),
        ]);

        for ($i = 1; $i <= 6; $i++) {
            ChapterPage::create([
                'chapter_id' => $chapter->id,
                'page_number' => $i,
                'image_path' => "chapters/{$chapter->id}/page_{$i}.jpg",
            ]);
        }

        $response = $this->actingAs($user)->get(route('chapter.show', [$manga->slug, $chapter->slug]));

        $response->assertOk();
        $response->assertDontSee('Aperçu Gratuit Terminé');
        $response->assertDontSee('Continuez votre lecture gratuitement');
    }

    public function test_staff_can_view_private_public_profile(): void
    {
        $admin = User::where('email', 'admin@hiddenscan.com')->first();
        if (! $admin) {
            $admin = User::factory()->create(['email' => 'admin_test_' . uniqid() . '@hiddenscan.com']);
            $admin->assignRole('admin');
        }

        $privateUser = User::factory()->create([
            'name' => 'Lecteur Secret',
            'username' => 'lecteursecret',
            'profile_public' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('profile.public', ['username' => 'lecteursecret']));

        $response->assertOk();
        $response->assertSee('Lecteur Secret');
        $response->assertSee('@lecteursecret');
    }

    public function test_banned_user_cookie_blocks_subsequent_guest_requests(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'ban_reason' => 'Toxique',
        ]);

        // Premier accès : l'utilisateur est connecté et banni -> il reçoit un cookie hs_banned_uid et 403
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(403);
        $response->assertCookie('hs_banned_uid');

        // Deuxième accès : l'utilisateur n'est plus authentifié (guest), mais a le cookie -> il reste bloqué 403
        $this->assertGuest();
        $responseGuest = $this->withCookie('hs_banned_uid', (string) $user->id)->get('/');
        $responseGuest->assertStatus(403);
        $responseGuest->assertSee('Toxique');
    }

    public function test_admin_cannot_ban_self_or_founder_in_table(): void
    {
        $admin = User::where('email', 'admin@hiddenscan.com')->first();
        if (! $admin) {
            $admin = User::factory()->create(['email' => 'admin@hiddenscan.com']);
            $admin->assignRole('admin');
        }

        $otherAdmin = User::factory()->create(['email' => 'other_admin@test.com']);
        $otherAdmin->assignRole('admin');

        $founder = User::where('email', 'meliodasdsama006@gmail.com')->first();
        if (! $founder) {
            $founder = User::factory()->create(['email' => 'meliodasdsama006@gmail.com']);
            $founder->assignRole('admin');
        }

        $reader = User::factory()->create(['name' => 'Target Reader', 'email' => 'target@reader.com']);

        $test = \Livewire\Livewire::actingAs($otherAdmin)
            ->test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->set('activeTab', 'all');

        $action = $test->instance()->getTable()->getAction('ban');

        // Cannot ban self
        $this->assertFalse($action->record($otherAdmin)->isVisible());

        // Cannot ban founder
        $this->assertFalse($action->record($founder)->isVisible());
        $this->assertFalse($action->record($admin)->isVisible());

        // Can ban regular member / reader
        $this->assertTrue($action->record($reader)->isVisible());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_can_be_viewed_by_anyone(): void
    {
        $user = User::factory()->create([
            'name' => 'Shadow Monk',
            'username' => 'shadowmonk',
            'bio' => 'Grand maître des arts martiaux.',
            'profile_public' => true,
        ]);

        $response = $this->get(route('profile.public', ['username' => 'shadowmonk']));

        $response->assertOk();
        $response->assertSee('Shadow Monk');
        $response->assertSee('@shadowmonk');
        $response->assertSee('Grand maître des arts martiaux.');
        $response->assertSee('Mangas lus');
        $response->assertSee('Favoris');
    }

    public function test_public_profile_returns_404_for_nonexistent_user(): void
    {
        $response = $this->get('/user/doesnotexist');

        $response->assertNotFound();
    }

    public function test_private_profile_returns_404_for_third_party(): void
    {
        $user = User::factory()->create([
            'username' => 'secretuser',
            'profile_public' => false,
        ]);

        // Guest
        $response = $this->get(route('profile.public', ['username' => 'secretuser']));
        $response->assertNotFound();

        // Autre utilisateur
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->get(route('profile.public', ['username' => 'secretuser']));
        $response->assertNotFound();
    }

    public function test_private_profile_can_be_viewed_by_owner(): void
    {
        $user = User::factory()->create([
            'name' => 'Owner Name',
            'username' => 'owneruser',
            'profile_public' => false,
        ]);

        $response = $this->actingAs($user)->get(route('profile.public', ['username' => 'owneruser']));

        $response->assertOk();
        $response->assertSee('Owner Name');
        $response->assertSee('Ce profil est privé.');
    }

    public function test_public_profile_shows_favorites_and_recents(): void
    {
        $user = User::factory()->create([
            'username' => 'otakureader',
            'profile_public' => true,
        ]);

        $manga = Manga::create([
            'title' => 'Legend of the Northern Blade',
            'slug' => 'legend-of-the-northern-blade',
            'synopsis' => 'A great martial arts story',
            'type' => 'manhwa',
            'status' => 'en_cours',
        ]);

        $chapter = \App\Models\Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
        ]);

        Favorite::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
        ]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
            'chapter_id' => $chapter->id,
            'is_read' => true,
        ]);

        $response = $this->get(route('profile.public', ['username' => 'otakureader']));

        $response->assertOk();
        $response->assertSee('Legend of the Northern Blade');
        $response->assertSee('Favoris');
        $response->assertSee('Récemment lus');
    }

    public function test_public_profile_can_be_resolved_by_name_fallback_when_username_is_null(): void
    {
        $user = User::factory()->create([
            'name' => 'SoloMaster',
            'profile_public' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['username' => null]);
        $user->refresh();

        $this->assertNull($user->username);

        $response = $this->get(route('profile.public', ['username' => $user->name]));

        $response->assertOk();
        $response->assertSee('SoloMaster');
    }

    public function test_leaderboard_page_renders_user_links_for_cultivators(): void
    {
        $manga = Manga::create([
            'title' => 'Murim World',
            'slug' => 'murim-world',
            'synopsis' => 'Martial arts journey',
            'type' => 'manhwa',
            'status' => 'en_cours',
        ]);

        $users = collect();
        for ($i = 1; $i <= 4; $i++) {
            $u = User::factory()->create([
                'name' => "Cultivator {$i}",
                'username' => "cultivator{$i}",
                'profile_public' => true,
            ]);
            $chapter = \App\Models\Chapter::create([
                'manga_id' => $manga->id,
                'number' => $i,
                'title' => "Chapitre {$i}",
                'slug' => "chapitre-{$i}",
            ]);
            ReadingProgress::create([
                'user_id' => $u->id,
                'manga_id' => $manga->id,
                'chapter_id' => $chapter->id,
                'is_read' => true,
            ]);
            $users->push($u);
        }

        $response = $this->get(route('leaderboard'));
        $response->assertOk();
        $response->assertSee('user-link');
        foreach ($users as $user) {
            $response->assertSee(route('profile.public', $user->username));
        }
    }

    public function test_comment_section_renders_user_link(): void
    {
        $manga = Manga::create([
            'title' => 'Peerless Commenter',
            'slug' => 'peerless-commenter',
            'synopsis' => 'Story about comments',
            'type' => 'manga',
            'status' => 'en_cours',
        ]);

        $user = User::factory()->create([
            'name' => 'DragonSlayer',
            'username' => 'dragonslayer',
            'profile_public' => true,
        ]);

        \App\Models\Comment::create([
            'commentable_type' => Manga::class,
            'commentable_id' => $manga->id,
            'user_id' => $user->id,
            'pseudo' => 'DragonSlayer',
            'content' => 'Incroyable manga !',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'is_approved' => true,
        ]);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Public\CommentSection::class, [
                'type' => Manga::class,
                'id' => $manga->id,
            ])
            ->assertSee('user-link')
            ->assertSee(route('profile.public', 'dragonslayer'));
    }

    public function test_admin_users_table_public_profile_action_points_to_public_profile(): void
    {
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create([
            'email' => 'admin_profile_action@hiddenscan.com',
        ]);
        $admin->assignRole($adminRole);

        $readerWithUsername = User::factory()->create([
            'name' => 'Reader With User',
            'username' => 'reader_u',
        ]);

        $readerWithoutUsername = User::factory()->create([
            'name' => 'Reader No User',
        ]);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $readerWithoutUsername->id)->update(['username' => null]);
        $readerWithoutUsername->refresh();

        $this->assertNull($readerWithoutUsername->username);

        $test = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->set('activeTab', 'all')
            ->assertTableActionExists('public_profile');

        $actionWithUser = $test->instance()->getTable()->getAction('public_profile');
        $this->assertNotNull($actionWithUser);
        $this->assertTrue($actionWithUser->shouldOpenUrlInNewTab());

        $urlWithUser = $actionWithUser->record($readerWithUsername)->getUrl();
        $this->assertEquals(route('profile.public', 'reader_u'), $urlWithUser);

        $urlWithoutUser = $actionWithUser->record($readerWithoutUsername)->getUrl();
        $this->assertEquals(route('profile.public', 'Reader No User'), $urlWithoutUser);
    }

    public function test_public_profile_resolves_case_insensitively_and_with_url_decoding(): void
    {
        $user = User::factory()->create([
            'name' => 'Co-Admin Special',
            'username' => 'coadmin_special',
            'profile_public' => true,
        ]);

        // Exact username
        $this->get('/user/coadmin_special')->assertOk()->assertSee('Co-Admin Special');

        // Uppercase username
        $this->get('/user/COADMIN_SPECIAL')->assertOk()->assertSee('Co-Admin Special');

        // URL encoded Name with spaces and hyphen
        $this->get('/user/Co-Admin%20Special')->assertOk()->assertSee('Co-Admin Special');
    }

    public function test_public_profile_defaults_to_public(): void
    {
        $user = User::factory()->create([
            'name' => 'DefaultPublicUser',
            'username' => 'default_public_user',
        ]);

        $this->assertTrue((bool) $user->profile_public);

        $response = $this->get('/user/default_public_user');
        $response->assertOk();
        $response->assertSee('DefaultPublicUser');
    }

    public function test_banned_accounts_modal_renders_public_profile_link(): void
    {
        $bannedUser = User::factory()->create([
            'name' => 'Banned Member',
            'username' => 'banned_member',
            'is_banned' => true,
            'ban_reason' => 'Spam répété',
        ]);

        $rendered = view('filament.comments.banned-accounts-modal', [
            'bannedUsers' => collect([$bannedUser]),
            'bannedIps' => collect(),
        ])->render();

        $this->assertStringContainsString('Profil public', $rendered);
        $this->assertStringContainsString(route('profile.public', 'banned_member'), $rendered);
        $this->assertStringContainsString('target="_blank"', $rendered);
    }
}


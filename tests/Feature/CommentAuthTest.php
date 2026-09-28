<?php

namespace Tests\Feature;

use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Livewire\Public\CommentSection;
use App\Models\Comment;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Manga $manga;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'JeanDupont']);
        $this->manga = Manga::create([
            'title' => 'Test Manga',
            'slug' => 'test-manga',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
    }

    public function test_guest_cannot_see_comments_list(): void
    {
        Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => 'JeanDupont',
            'content' => 'TexteSecretCommentaire',
            'ip_hash' => 'hash123',
        ]);

        Livewire::test(CommentSection::class, [
            'type' => 'App\Models\Manga',
            'id' => $this->manga->id,
        ])
            ->assertSee('Rejoignez la discussion !')
            ->assertSee('Se connecter')
            ->assertDontSee('TexteSecretCommentaire')
            ->assertDontSee('Récents')
            ->assertDontSee('Populaires');
    }

    public function test_guest_cannot_submit_comment(): void
    {
        Livewire::test(CommentSection::class, [
            'type' => 'App\Models\Manga',
            'id' => $this->manga->id,
        ])
            ->set('content', 'Ceci est un commentaire invité.')
            ->call('submit')
            ->assertHasErrors(['content'])
            ->assertSee('Rejoignez la discussion !')
            ->assertSee('Se connecter');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_guest_cannot_like_or_dislike_comment(): void
    {
        $comment = Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => $this->user->name,
            'content' => 'Commentaire pour like',
            'ip_hash' => 'hash123',
            'likes_count' => 0,
            'dislikes_count' => 0,
        ]);

        Livewire::test(CommentSection::class, [
            'type' => 'App\Models\Manga',
            'id' => $this->manga->id,
        ])
            ->call('toggleLike', $comment->id)
            ->assertRedirect(route('login'));

        Livewire::test(CommentSection::class, [
            'type' => 'App\Models\Manga',
            'id' => $this->manga->id,
        ])
            ->call('toggleDislike', $comment->id)
            ->assertRedirect(route('login'));

        $this->assertEquals(0, $comment->fresh()->likes_count);
        $this->assertEquals(0, $comment->fresh()->dislikes_count);
    }

    public function test_authenticated_user_can_see_comments_and_like(): void
    {
        $comment = Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => 'JeanDupont',
            'content' => 'TexteVisiblePourMembres',
            'ip_hash' => 'hash123',
            'likes_count' => 0,
        ]);

        $viewer = User::factory()->create(['name' => 'LecteurConnecte']);

        Livewire::actingAs($viewer)
            ->test(CommentSection::class, [
                'type' => 'App\Models\Manga',
                'id' => $this->manga->id,
            ])
            ->assertSee('TexteVisiblePourMembres')
            ->assertSee('Récents')
            ->call('toggleLike', $comment->id)
            ->assertHasNoErrors();

        $this->assertEquals(1, $comment->fresh()->likes_count);
    }

    public function test_authenticated_user_can_submit_comment(): void
    {
        Livewire::actingAs($this->user)
            ->test(CommentSection::class, [
                'type' => 'App\Models\Manga',
                'id' => $this->manga->id,
            ])
            ->set('content', 'Super manga !')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Super manga !');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => 'JeanDupont',
            'content' => 'Super manga !',
        ]);
    }

    public function test_guest_cannot_submit_reply(): void
    {
        $comment = Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => $this->user->name,
            'content' => 'Commentaire initial',
            'ip_hash' => 'hash123',
        ]);

        Livewire::test(CommentSection::class, [
            'type' => 'App\Models\Manga',
            'id' => $this->manga->id,
        ])
            ->set('replyTo', $comment->id)
            ->set('replyContent', 'Tentative de réponse invité')
            ->call('submitReply')
            ->assertHasErrors(['replyContent']);

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_authenticated_user_can_submit_reply(): void
    {
        $comment = Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $this->user->id,
            'pseudo' => $this->user->name,
            'content' => 'Commentaire initial',
            'ip_hash' => 'hash123',
        ]);

        $replyUser = User::factory()->create(['name' => 'RepondeurPro']);

        Livewire::actingAs($replyUser)
            ->test(CommentSection::class, [
                'type' => 'App\Models\Manga',
                'id' => $this->manga->id,
            ])
            ->set('replyTo', $comment->id)
            ->set('replyContent', 'Je suis tout à fait d\'accord !')
            ->call('submitReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $this->manga->id,
            'user_id' => $replyUser->id,
            'pseudo' => 'RepondeurPro',
            'content' => 'Je suis tout à fait d\'accord !',
            'parent_id' => $comment->id,
        ]);
    }
}

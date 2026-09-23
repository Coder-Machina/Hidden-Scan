<?php

namespace App\Livewire\Public;

use App\Models\Comment;
use Livewire\Component;
use Illuminate\Support\Facades\Request;

class CommentSection extends Component
{
    public string $commentableType;
    public int $commentableId;

    public string $pseudo = '';
    public string $content = '';
    public ?int $replyTo = null;
    public string $replyPseudo = '';

    protected $rules = [
        'pseudo'  => 'required|min:2|max:30',
        'content' => 'required|min:3|max:1000',
    ];

    protected $badWords = ['spam', 'insulte', 'arnaque', 'lien-interdit', 'pub'];

    public function mount(string $type, int $id): void
    {
        $this->commentableType = $type;
        $this->commentableId   = $id;
    }

    public function submit(): void
    {
        $this->validate();

        $contentLower = strtolower($this->content);
        foreach ($this->badWords as $word) {
            if (str_contains($contentLower, $word)) {
                $this->addError('content', 'Votre message contient des mots non autorisés.');
                return;
            }
        }

        $ip = Request::ip();

        Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id'   => $this->commentableId,
            'pseudo'           => $this->pseudo ?: 'Anonyme',
            'content'          => $this->content,
            'ip_hash'          => hash('sha256', $ip . config('app.key')),
            'parent_id'        => $this->replyTo,
        ]);

        $this->reset('content', 'replyTo', 'replyPseudo');
        session()->flash('message', 'Commentaire publié.');
    }

    public function toggleLike(int $commentId): void
    {
        $comment = Comment::find($commentId);
        if (!$comment) return;

        $ip = Request::ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        
        $existing = \Illuminate\Support\Facades\DB::table('comment_likes')
            ->where('comment_id', $commentId)
            ->where('ip_hash', $ipHash)
            ->first();

        if ($existing) {
            \Illuminate\Support\Facades\DB::table('comment_likes')->where('id', $existing->id)->delete();
            $comment->decrement('likes_count');
        } else {
            \Illuminate\Support\Facades\DB::table('comment_likes')->insert([
                'comment_id' => $commentId,
                'ip_hash' => $ipHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $comment->increment('likes_count');
        }
    }

    public function report(int $commentId): void
    {
        $ip = Request::ip();
        
        \Illuminate\Support\Facades\DB::table('comment_reports')->insertOrIgnore([
            'comment_id' => $commentId,
            'ip_address' => hash('sha256', $ip . config('app.key')),
            'reason' => 'Signalement utilisateur',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        session()->flash('message', 'Commentaire signalé à la modération.');
    }

    public function replyTo(int $commentId, string $pseudo): void
    {
        $this->replyTo      = $commentId;
        $this->replyPseudo  = $pseudo;
    }

    public function cancelReply(): void
    {
        $this->reset('replyTo', 'replyPseudo');
    }

    public function render()
    {
        $comments = Comment::where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->whereNull('parent_id')
            ->where('is_hidden', false)
            ->with(['replies' => fn($q) => $q->where('is_hidden', false)])
            ->latest()
            ->get();

        return view('livewire.public.comment-section', compact('comments'));
    }
}
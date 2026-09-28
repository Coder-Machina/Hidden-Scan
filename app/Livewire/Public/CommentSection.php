<?php

namespace App\Livewire\Public;

use App\Models\Comment;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Request;

class CommentSection extends Component
{
    use WithPagination;

    public string $commentableType;
    public int $commentableId;

    public string $pseudo = '';
    public string $content = '';
    public string $attachedImage = '';
    public ?int $replyTo = null;
    public string $replyPseudo = '';
    public string $replyContent = '';
    public string $replyAttachedImage = '';
    
    // For editing
    public ?int $editingCommentId = null;
    public string $editingContent = '';

    protected function rules()
    {
        return [
            'content' => $this->attachedImage ? 'nullable|max:1000' : 'required|min:2|max:1000',
            'attachedImage' => 'nullable|url'
        ];
    }

    protected $badWords = ['spam', 'insulte', 'arnaque', 'lien-interdit', 'pub'];

    public function mount(string $type, int $id): void
    {
        $this->commentableType = $type;
        $this->commentableId   = $id;

        // Auto-set pseudo from authenticated user
        if (auth()->check()) {
            $this->pseudo = auth()->user()->name;
        }
    }

    public function submit(): void
    {
        // 0. Check authentication
        if (!auth()->check()) {
            $this->addError('content', 'Vous devez être connecté avec un compte pour publier un commentaire.');
            return;
        }

        // 1. Check if user is banned
        if (auth()->user()->isBanned() || auth()->user()->isCommentBanned()) {
            $this->addError('content', 'Votre compte a été restreint et ne peut plus poster de commentaires.');
            return;
        }

        // 2. Check if IP is banned
        $ip = Request::ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        if (\App\Models\BannedIp::where('ip_hash', $ipHash)->exists()) {
            $this->addError('content', 'Vous avez été banni de l\'espace commentaires.');
            return;
        }

        $this->pseudo = auth()->user()->name;
        $this->validate();

        $contentLower = strtolower($this->content);
        foreach ($this->badWords as $word) {
            if (str_contains($contentLower, $word)) {
                $this->addError('content', 'Votre message contient des mots non autorisés.');
                return;
            }
        }

        $finalContent = $this->content;
        if ($this->attachedImage) {
            $finalContent .= "\n[img]" . $this->attachedImage . "[/img]";
        }

        Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id'   => $this->commentableId,
            'user_id'          => auth()->id(),
            'pseudo'           => auth()->user()->name,
            'content'          => trim($finalContent),
            'ip_hash'          => $ipHash,
            'parent_id'        => null,
        ]);

        $this->reset('content', 'attachedImage');
        session()->flash('message', 'Commentaire publié avec succès.');
    }

    public function setReplyTo(int $commentId, string $pseudo): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        if ($this->replyTo === $commentId) {
            $this->cancelReply();
            return;
        }
        $this->replyTo = $commentId;
        $this->replyPseudo = $pseudo;
        $this->replyContent = '';
        $this->replyAttachedImage = '';
    }

    public function cancelReply(): void
    {
        $this->reset('replyTo', 'replyPseudo', 'replyContent', 'replyAttachedImage');
    }

    public function submitReply(): void
    {
        if (!$this->replyTo) return;

        // 0. Check authentication
        if (!auth()->check()) {
            $this->addError('replyContent', 'Vous devez être connecté avec un compte pour répondre à un commentaire.');
            return;
        }

        // 1. Check if user is banned
        if (auth()->user()->isBanned() || auth()->user()->isCommentBanned()) {
            $this->addError('replyContent', 'Votre compte a été restreint et ne peut plus poster de commentaires.');
            return;
        }

        // 2. Check if IP is banned
        $ip = Request::ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        if (\App\Models\BannedIp::where('ip_hash', $ipHash)->exists()) {
            $this->addError('replyContent', 'Vous avez été banni de l\'espace commentaires.');
            return;
        }

        $this->pseudo = auth()->user()->name;
        $this->validate([
            'replyContent' => $this->replyAttachedImage ? 'nullable|max:1000' : 'required|min:2|max:1000',
            'replyAttachedImage' => 'nullable|url',
        ]);

        $contentLower = strtolower($this->replyContent);
        foreach ($this->badWords as $word) {
            if (str_contains($contentLower, $word)) {
                $this->addError('replyContent', 'Votre message contient des mots non autorisés.');
                return;
            }
        }

        $finalContent = $this->replyContent;
        if ($this->replyAttachedImage) {
            $finalContent .= "\n[img]" . $this->replyAttachedImage . "[/img]";
        }

        Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id'   => $this->commentableId,
            'user_id'          => auth()->id(),
            'pseudo'           => auth()->user()->name,
            'content'          => trim($finalContent),
            'ip_hash'          => $ipHash,
            'parent_id'        => $this->replyTo,
        ]);

        $this->reset('replyTo', 'replyPseudo', 'replyContent', 'replyAttachedImage');
        session()->flash('message', 'Réponse publiée avec succès.');
    }

    public function toggleLike(int $commentId): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

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
            // Remove dislike if exists
            $existingDislike = \Illuminate\Support\Facades\DB::table('comment_dislikes')
                ->where('comment_id', $commentId)->where('ip_hash', $ipHash)->first();
            if ($existingDislike) {
                \Illuminate\Support\Facades\DB::table('comment_dislikes')->where('id', $existingDislike->id)->delete();
                $comment->decrement('dislikes_count');
            }

            \Illuminate\Support\Facades\DB::table('comment_likes')->insert([
                'comment_id' => $commentId,
                'ip_hash' => $ipHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $comment->increment('likes_count');
        }
    }

    public function toggleDislike(int $commentId): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $comment = Comment::find($commentId);
        if (!$comment) return;

        $ip = Request::ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        
        $existing = \Illuminate\Support\Facades\DB::table('comment_dislikes')
            ->where('comment_id', $commentId)
            ->where('ip_hash', $ipHash)
            ->first();

        if ($existing) {
            \Illuminate\Support\Facades\DB::table('comment_dislikes')->where('id', $existing->id)->delete();
            $comment->decrement('dislikes_count');
        } else {
            // Remove like if exists
            $existingLike = \Illuminate\Support\Facades\DB::table('comment_likes')
                ->where('comment_id', $commentId)->where('ip_hash', $ipHash)->first();
            if ($existingLike) {
                \Illuminate\Support\Facades\DB::table('comment_likes')->where('id', $existingLike->id)->delete();
                $comment->decrement('likes_count');
            }

            \Illuminate\Support\Facades\DB::table('comment_dislikes')->insert([
                'comment_id' => $commentId,
                'ip_hash' => $ipHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $comment->increment('dislikes_count');
        }
    }

    public function report(int $commentId): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

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

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::find($commentId);
        if (!$comment) return;

        $ipHash = hash('sha256', Request::ip() . config('app.key'));
        $isOwner = auth()->check() ? ($comment->user_id === auth()->id()) : ($comment->ip_hash === $ipHash && !$comment->user_id);
        
        if ($isOwner || (auth()->check() && auth()->user()->is_admin)) {
            $comment->delete();
            session()->flash('message', 'Commentaire supprimé.');
        }
    }

    public function startEdit(int $commentId): void
    {
        $comment = Comment::find($commentId);
        if (!$comment) return;

        $ipHash = hash('sha256', Request::ip() . config('app.key'));
        $isOwner = auth()->check() ? ($comment->user_id === auth()->id()) : ($comment->ip_hash === $ipHash && !$comment->user_id);
        
        if ($isOwner || (auth()->check() && auth()->user()->is_admin)) {
            $this->editingCommentId = $commentId;
            $this->editingContent = $comment->content;
        }
    }

    public function cancelEdit(): void
    {
        $this->reset('editingCommentId', 'editingContent');
    }

    public function updateComment(): void
    {
        if (!$this->editingCommentId) return;

        $this->validate([
            'editingContent' => 'required|min:2|max:1000'
        ]);

        $comment = Comment::find($this->editingCommentId);
        if (!$comment) return;

        $ipHash = hash('sha256', Request::ip() . config('app.key'));
        $isOwner = auth()->check() ? ($comment->user_id === auth()->id()) : ($comment->ip_hash === $ipHash && !$comment->user_id);
        
        if ($isOwner || (auth()->check() && auth()->user()->is_admin)) {
            $comment->update(['content' => trim($this->editingContent)]);
            $this->reset('editingCommentId', 'editingContent');
            session()->flash('message', 'Commentaire modifié.');
        }
    }

    public string $sortBy = 'recent';

    public function setSort(string $sort): void
    {
        $this->sortBy = $sort;
        $this->resetPage();
    }

    public function render()
    {
        $totalCount = Comment::where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->where('is_hidden', false)
            ->count();

        if (!auth()->check()) {
            return view('livewire.public.comment-section', [
                'comments' => null,
                'totalCount' => $totalCount,
                'isRestricted' => false,
                'userLikedCommentIds' => [],
                'userDislikedCommentIds' => [],
            ]);
        }

        $ip = Request::ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        $isIpBanned = \App\Models\BannedIp::where('ip_hash', $ipHash)->exists();
        $isUserBanned = auth()->check() && (auth()->user()->isBanned() || auth()->user()->isCommentBanned());
        $isRestricted = $isIpBanned || $isUserBanned;

        $query = Comment::where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->whereNull('parent_id')
            ->where('is_hidden', false)
            ->with(['replies' => fn($q) => $q->where('is_hidden', false)->orderBy('created_at', 'asc')]);

        if ($this->sortBy === 'popular') {
            $query->orderBy('likes_count', 'desc')->orderBy('created_at', 'desc');
        } elseif ($this->sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $userLikedCommentIds = \Illuminate\Support\Facades\DB::table('comment_likes')
            ->where('ip_hash', $ipHash)
            ->pluck('comment_id')
            ->toArray();

        $userDislikedCommentIds = \Illuminate\Support\Facades\DB::table('comment_dislikes')
            ->where('ip_hash', $ipHash)
            ->pluck('comment_id')
            ->toArray();

        $comments = $query->paginate(15);

        return view('livewire.public.comment-section', compact('comments', 'totalCount', 'isRestricted', 'userLikedCommentIds', 'userDislikedCommentIds'));
    }
}
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

    public function mount(string $type, int $id): void
    {
        $this->commentableType = $type;
        $this->commentableId   = $id;
    }

    public function submit(): void
    {
        $this->validate();

        $ip = Request::ip();

        Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id'   => $this->commentableId,
            'pseudo'           => $this->pseudo,
            'content'          => $this->content,
            'ip_hash'          => hash('sha256', $ip . config('app.key')),
            'parent_id'        => $this->replyTo,
        ]);

        $this->reset('content', 'replyTo', 'replyPseudo');
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
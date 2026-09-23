<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $fillable = [
        'comment_id',
        'ip_address',
        'reason',
        'is_resolved',
        'resolved_by',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

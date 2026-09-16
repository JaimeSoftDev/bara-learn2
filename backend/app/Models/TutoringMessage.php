<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutoringMessage extends Model
{
    protected $fillable = ['tutoring_thread_id', 'sender_id', 'body'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(TutoringThread::class, 'tutoring_thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

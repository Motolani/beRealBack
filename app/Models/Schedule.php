<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'user_id',
        'duration',
        'start_at',
        'ended_at',
        'contact_id',
        'contact_group_id',
        'status',
        'soft_delete',
        'message_type',
        'message_id',
        'custom_message',
        'auto_send',
    ];

    protected $hidden = [
        'message_type',
        'message_id',
        'custom_message',
        'auto_send',
    ];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

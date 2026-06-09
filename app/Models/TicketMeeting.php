<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMeeting extends Model
{
    protected $table = 'ticket_meetings';

    protected $fillable = [
        'ticket_id',
        'started_by',
        'started_by_name',
        'topic',
        'meeting_link',
        'started_at',
        'ended_at',
        'summary',
        'duration_minutes',
        'start_message_id',
        'end_message_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function durationMinutes(): int
    {
        $end = $this->ended_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }
}

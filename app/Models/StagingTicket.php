<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingTicket extends Model
{
    protected $table = 'staging_tickets';

    protected $fillable = [
        'customer_id',
        'end_customer_id',
        'description',
        'body',
        'cc_emails',
        'name',
        'no_hp',
        'module',
        'client',
        'ticket_priority',
        'ticket_type',
        'scale',
        'status',
        'rejection_reason',
        'channel',
        'email_thread_id',
        'email_message_id',
        'graph_message_id',
        'email_body_html',
        'has_attachments',
        'customer_thread_id',
        'submitted_by_email',
        'sender_name',
        'validated_by',
        'validated_at',
        'ticket_id',
        'attachment_names',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────

    public function scopeUnvalidated($query)
    {
        return $query->where('status', 'unvalidated');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ── Boolean helpers ──────────────────────────────────

    public function isUnvalidated(): bool
    {
        return $this->status === 'unvalidated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }

    // ── Relasi ────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    // ── Accessor ─────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'unvalidated' => 'Pending Validation',
            'approved'    => 'Approved',
            'rejected'    => 'Rejected',
            default       => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'unvalidated' => 'yellow',
            'approved'    => 'green',
            'rejected'    => 'red',
            default       => 'gray',
        };
    }
}

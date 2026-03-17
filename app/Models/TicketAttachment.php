<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $table = 'ticket_attachment';

    protected $fillable = [
        'ticket_id',
        'message_id',
        'uploaded_by_type',
        'uploaded_by_id',
        'attachment_type',
        'graph_message_id',
        'graph_attachment_id',
        'content_id',
        'is_inline',
        'file_name',
        'file_size',
        'mime_type',
        'file_path',
        'link_url',
        'link_title',
        'description',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'message_id');
    }

    public function uploader()
    {
        if ($this->uploaded_by_type === 'employee') {
            return $this->belongsTo(Employee::class, 'uploaded_by_id', 'employee_id');
        } elseif ($this->uploaded_by_type === 'customer') {
            return $this->belongsTo(Customer::class, 'uploaded_by_id', 'customer_id');
        }
        return null;
    }

    public function scopeByType($query, $type)
    {
        return $query->where('attachment_type', $type);
    }

    /**
     * URL untuk mengakses attachment.
     * - Jika ada graph_message_id + graph_attachment_id → proxy JARVIES /attachments/{id}
     * - Jika ada file_path (record lama) → /storage/{path}
     * - Fallback → link_url dari DB
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->graph_message_id && $this->graph_attachment_id) {
            return '/attachments/' . $this->id;
        }
        if ($this->file_path) {
            return '/storage/' . $this->file_path;
        }
        return $this->link_url ?: null;
    }
}

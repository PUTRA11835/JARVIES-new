<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthUser extends Model
{
    protected $table = 'auth_users';

    protected $fillable = [
        'email',
        'username',
        'phone',
        'password',
        'is_active',
        'employee_id',
        'customer_id',
        'contact_id',
        'preferences',
        'notification_sound_id',
        'last_login_at',
        'can_view_all_tickets',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active'            => 'boolean',
        'can_view_all_tickets' => 'boolean',
        'last_login_at'        => 'datetime',
        'preferences'          => 'array',
    ];

    public function notificationSound()
    {
        return $this->belongsTo(NotificationSound::class, 'notification_sound_id');
    }
}

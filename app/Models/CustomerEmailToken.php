<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmailToken extends Model
{
    protected $table = 'customer_email_tokens';

    protected $fillable = [
        'customer_id',
        'provider',
        'provider_email',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function isExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }
}

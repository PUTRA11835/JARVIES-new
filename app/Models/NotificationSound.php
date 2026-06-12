<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSound extends Model
{
    protected $table = 'notification_sounds';

    protected $fillable = ['name', 'filename', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function defaultId(): ?int
    {
        return static::where('is_default', true)->value('id');
    }
}

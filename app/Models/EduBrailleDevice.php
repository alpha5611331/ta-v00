<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EduBrailleDevice extends Model
{
    use HasFactory;

    protected $table = 'edubraille_devices';

    protected $fillable = [
        'device_id',
        'endpoint',
        'token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'original_filename',
    'storage_path',
    'file_type',
    'raw_text',
    'remediated_text',
    'char_count',
    'braille_sent_at',
])]
class Document extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'braille_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brailleDeliveries(): HasMany
    {
        return $this->hasMany(BrailleDelivery::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DocumentQuestion::class);
    }

    protected function previewText(): Attribute
    {
        return Attribute::get(
            fn () => Str::limit($this->remediated_text ?? 'Belum ada hasil remediasi.', 200)
        );
    }
}

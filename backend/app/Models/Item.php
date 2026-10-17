<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected $keyType = 'int';

    protected $fillable = [
        'title',
        'description',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}

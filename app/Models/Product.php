<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }

    /**
     * Daftar menu satu outlet untuk halaman customer.
     */
    public static function menuFor(string $outlet)
    {
        return static::where('outlet', $outlet)
            ->orderBy('name')
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;

class Restock extends Model
{
    use HasFactory;

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}

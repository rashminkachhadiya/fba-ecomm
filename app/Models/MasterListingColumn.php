<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterListingColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_name',
        'columns'
    ];

    protected $casts = [
        'columns' => 'array'
    ];
}

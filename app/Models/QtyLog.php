<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtyLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    const CREATED_AT = 'created_date';
}

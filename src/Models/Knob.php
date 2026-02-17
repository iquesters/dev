<?php

namespace Iquesters\Dev\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Knob extends Model
{
    use HasFactory;

    protected $table = 'knobs';

    protected $fillable = [
        'uid',
        'knob',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'knob' => 'array',
    ];
}
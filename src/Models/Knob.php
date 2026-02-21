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
        'version',
        'knob',
        'sha256',
        'status',
        'created_by',
        'updated_by',
    ];
}
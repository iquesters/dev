<?php

namespace Iquesters\Dev\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VectorResponse extends Model
{
    use HasFactory;

    protected $table = 'vector_responses';

    protected $fillable = [
        'uid',
        'integration_id',
        'job_uuid',
        'response',
        'version',
        'knob',
        'sha256',
        'started_at',
        'finished_at',
        'duration_seconds',
        'status',
        'created_by',
        'updated_by',
    ];
    
    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];
}
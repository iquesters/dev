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
        'operation_id',
        'message',
        'step_status',
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
        'operation_id' => 'integer',
        'step_status' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function integration()
    {
        return $this->belongsTo(\Iquesters\Integration\Models\Integration::class, 'integration_id');
    }

    public function getDecodedResponseAttribute(): mixed
    {
        return json_decode((string) $this->response, true);
    }
}

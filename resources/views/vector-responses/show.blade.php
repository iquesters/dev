@extends('userinterface::layouts.app')

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Vector Operation', $operationId]))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Track a vector v2 operation'))
@php
    $tabs = [
        [
            'route' => 'vectors.responses.index',
            'params' => [],
            'icon' => 'fa-fw fas fa-list-check',
            'label' => 'Vector Operations',
        ],
    ];

    $integration = $latest->integration;
    $isFailed = $latest->status === 'failed';
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h5 class="fs-6 mb-1">Operation {{ $operationId }}</h5>
        <div class="text-muted small">
            {{ $integration?->name ?? 'Unknown Integration' }}
            @if($integration?->uid)
                <span class="mx-1">|</span>
                <code>{{ $integration->uid }}</code>
            @endif
        </div>
    </div>
    <a href="{{ route('vectors.responses.index') }}" class="btn btn-sm btn-outline-dark">
        <i class="fas fa-arrow-left me-1"></i> Back To Operations
    </a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="text-muted small">Latest Message</div>
                <div>{{ $latest->message ?? '-' }}</div>
            </div>
            <x-userinterface::status :status="$latest->status" />
        </div>

        <div class="progress mb-2" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" style="height: 18px;">
            <div class="progress-bar {{ $isFailed ? 'bg-danger' : 'bg-success' }}" style="width: {{ $progress }}%;">
                {{ $progress }}%
            </div>
        </div>

        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted">Latest Step Status</div>
                <div><code>{{ $latest->step_status ?? '-' }}</code></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Row Count</div>
                <div>{{ $records->count() }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Started</div>
                <div>{{ $records->first()?->created_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($records->first()->created_at) : '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Latest Update</div>
                <div>{{ $latest->created_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($latest->created_at) : '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3">Progress Timeline</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Row ID</th>
                                <th>Step Status</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>{{ $record->id }}</td>
                                    <td><code>{{ $record->step_status ?? '-' }}</code></td>
                                    <td><x-userinterface::status :status="$record->status" /></td>
                                    <td>{{ $record->message ?? '-' }}</td>
                                    <td>{{ $record->created_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($record->created_at) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3">Latest Payload</h6>
                <pre class="bg-light border rounded p-3 small mb-0" style="white-space: pre-wrap; word-break: break-word;">{{ json_encode($latest->decoded_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection

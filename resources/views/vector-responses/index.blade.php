@extends('userinterface::layouts.app')

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Vector Operations']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Track vector v2 operations'))
@php
    $tabs = [
        [
            'route' => 'vectors.responses.index',
            'params' => [],
            'icon' => 'fa-fw fas fa-draw-polygon',
            'label' => 'Vector Responses Old',
        ],
    ];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fs-6 mb-1">Vector Operations</h5>
        </div>
    </div>

<div class="table-responsive">
    <table id="vectorResponsesTable" class="table table-bordered table-striped table-sm align-middle w-100">
        <thead>
            <tr>
                <th>Operation ID</th>
                <th>Integration Name</th>
                <th>Integration UID</th>
                <th>Latest Message</th>
                <th>Status</th>
                <th>Started At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operations as $operation)
                @php
                    $latest = $operation['latest'];
                    $integration = $latest->integration;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('vectors.responses.show', ['operationId' => $operation['operation_id']]) }}">
                            {{ $operation['operation_id'] }}
                        </a>
                    </td>
                    <td>{{ $integration?->name ?? '-' }}</td>
                    <td><code>{{ $integration?->uid ?? '-' }}</code></td>
                    <td>{{ $latest->message ?? '-' }}</td>
                    <td><x-userinterface::status :status="$latest->status" /></td>
                    <td>{{ $operation['first']?->created_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($operation['first']->created_at) : '-' }}</td>
                    <td>{{ $latest->created_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($latest->created_at) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#vectorResponsesTable').DataTable({
        order: [[3, 'desc']],
        responsive: true,
        pageLength: 25
    });
});
</script>
@endpush

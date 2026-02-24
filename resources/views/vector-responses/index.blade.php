@extends('userinterface::layouts.app')

@section('title', 'Vector Responses')

@section('content')
<h5 class="fs-6">Vector Responses</h5>
<div class="table-responsive">
    <table id="vectorResponsesTable" class="table table-bordered table-striped table-sm align-middle w-100">
        <thead>
            <tr>
                <th>ID</th>
                <th>Integration Name</th>
                <th>Response</th>
                <th>Duration Seconds</th>
                <th>Status</th>
                <th>Started At</th>
                <th>Finished At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vectorResponses as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $integrationNames[$row->integration_id] ?? '-' }}</td>
                    <td>
                        <pre class="mb-0" style="white-space: pre-wrap; word-break: break-word; max-width: 700px;">{{ $row->response }}</pre>
                    </td>
                    <td>{{ $row->duration_seconds ?? '-' }}</td>
                    <td><span class="badge badge-{{ $row->status }}">{{ $row->status }}</span></td>
                    <td>{{ $row->started_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($row->started_at) : '-' }}</td>
                    <td>{{ $row->finished_at ? \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($row->finished_at) : '-' }}</td>
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
        order: [[0, 'desc']],
        responsive: true
    });

});
</script>
@endpush

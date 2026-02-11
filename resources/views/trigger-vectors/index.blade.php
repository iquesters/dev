    @extends('userinterface::layouts.app')

    @section('title', 'WooCommerce Integrations')

    @section('content')
        <h5 class="fs-6">WooCommerce Integrations</h5>

        <table id="vectorTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Organisation(s)</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($integrations as $key => $integration)
                    <tr>
                        <td>{{ $key + 1 }}</td>

                        <td>
                            {{ $integration->name }}
                        </td>

                        <td>
                            @foreach($integration->organisations as $org)
                                <span class="badge bg-primary">
                                    {{ $org->name }}
                                </span>
                            @endforeach
                        </td>

                        <td>
                            {{ optional($integration->creator)->name ?? '-' }}
                        </td>

                        <td>
                            <span class="badge badge-{{ $integration->status }}">{{ $integration->status }}</span>
                        </td>

                        <td>
                            {{ \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($integration->created_at) }}
                        </td>
                        <td>
                            <form method="POST"
                                action="{{ route('vectors.trigger', $integration->uid) }}"
                                class="d-inline">

                                @csrf

                                <button type="submit"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="return confirm('Trigger vector sync for this integration?')">
                                    <i class="fas fa-play"></i> Trigger
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    @endsection

    @push('scripts')
    <script>
        $(document).ready(function () {
            $('#vectorTable').DataTable({
                responsive: true,
            });
        });
    </script>
    @endpush

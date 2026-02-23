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
            <th>Last Vector Sync</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        @foreach($integrations as $key => $integration)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $integration->name }}</td>
            <td>
                @foreach($integration->organisations as $org)
                    <span class="badge bg-primary">{{ $org->name }}</span>
                @endforeach
            </td>
            <td>{{ optional($integration->creator)->name ?? '-' }}</td>
            <td>
                <span class="badge badge-{{ $integration->status }}">{{ $integration->status }}</span>
            </td>
            <td>
                @if($integration->last_vector_sync)
                    {{ \Iquesters\Foundation\Helpers\DateTimeHelper::displayDateTime($integration->last_vector_sync) }}
                @else
                    -
                @endif
            </td>
            <td>
                <!-- Trigger Button -->
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#triggerModal"
                        data-uid="{{ $integration->uid }}"
                        data-name="{{ $integration->name }}">
                    <i class="fas fa-play"></i> Trigger
                </button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="triggerModal" tabindex="-1" aria-labelledby="triggerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="triggerForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fs-6" id="triggerModalLabel">Trigger Vector Sync</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="triggerConfirmationText">Are you sure you want to trigger vector sync?</p>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="forceCleanup" name="force_cleanup">
            <label class="form-check-label" for="forceCleanup">
              Force clean up
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-outline-primary">Trigger</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#vectorTable').DataTable({ responsive: true });

    // Populate modal dynamically
    $('#triggerModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const uid = button.data('uid');
        const name = button.data('name');

        const modal = $(this);
        modal.find('#triggerConfirmationText').text(`Are you sure you want to trigger vector sync for "${name}"?`);
        modal.find('#triggerForm').attr('action', `/vector/${uid}/trigger`);
        modal.find('#forceCleanup').prop('checked', false); // reset checkbox
    });
});
</script>
@endpush
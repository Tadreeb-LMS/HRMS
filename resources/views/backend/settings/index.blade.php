@extends('backend.layouts.app')

@section('title', 'HRMS Integration Configuration | ' . app_name())

@push('after-styles')
<style>
/* Gateway table */
.gateway-table td {
    vertical-align: middle;
}

.gateway-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gateway-icon {
    font-size: 28px;
    width: 40px;
    text-align: center;
}

.gateway-icon-hrms { color: #0C2451; }

/* Status badges */
.badge-status {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 12px;
    font-weight: 500;
}

.badge-enabled {
    background-color: #d4edda;
    color: #155724;
}

.badge-disabled {
    background-color: #e2e3e5;
    color: #6c757d;
}

/* Toggle button transition */
.toggle-gateway-btn {
    min-width: 100px;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-5">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-network-wired mr-2"></i>
                            HRMS Integration Configuration
                        </h4>
                    </div>
                </div>
                <hr/>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <p class="text-muted">Manage your HRMS integration providers. Enable, disable, and configure credentials for each enterprise HR system to synchronize employees and auto-assign courses.</p>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered gateway-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 30%;">Provider</th>
                                <th style="width: 15%;" class="text-center">Status</th>
                                <th style="width: 20%;" class="text-center">Credentials</th>
                                <th style="width: 35%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providers as $slug => $provider)
                            <tr id="gateway-row-{{ $slug }}">
                                <td>
                                    <div class="gateway-info">
                                        <i class="{{ $provider['icon'] }} gateway-icon gateway-icon-hrms"></i>
                                        <div>
                                            <strong>{{ $provider['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $provider['description'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-status {{ $provider['enabled'] ? 'badge-enabled' : 'badge-disabled' }}" id="status-badge-{{ $slug }}">
                                        {{ $provider['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    @if($provider['has_credentials'])
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Configured</span>
                                    @else
                                        <span class="text-muted"><i class="fas fa-times-circle"></i> Not set</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('admin.hrms.configure', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-cog mr-1"></i> Configure
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info sync-gateway-btn"
                                            data-slug="{{ $slug }}"
                                            id="sync-btn-{{ $slug }}"
                                            {{ $provider['enabled'] ? '' : 'disabled' }}>
                                        <i class="fas fa-sync-alt mr-1"></i> Sync
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm {{ $provider['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-gateway-btn"
                                            data-slug="{{ $slug }}"
                                            data-enabled="{{ $provider['enabled'] ? '1' : '0' }}"
                                            id="toggle-btn-{{ $slug }}">
                                        <i class="fas {{ $provider['enabled'] ? 'fa-toggle-off' : 'fa-toggle-on' }} mr-1"></i>
                                        {{ $provider['enabled'] ? 'Disable' : 'Enable' }}
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
$(document).ready(function() {

    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    $(document).on('click', '.toggle-gateway-btn', function() {
        var btn = $(this);
        var slug = btn.data('slug');
        var originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Working...');

        $.ajax({
            url: '{{ url('external-apps/hrms/toggle') }}/' + slug,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var enabled = response.enabled;

                    var statusBadge = $('#status-badge-' + slug);
                    statusBadge
                        .text(enabled ? 'Enabled' : 'Disabled')
                        .removeClass('badge-enabled badge-disabled')
                        .addClass(enabled ? 'badge-enabled' : 'badge-disabled');

                    btn.removeClass('btn-outline-success btn-outline-danger')
                       .addClass(enabled ? 'btn-outline-danger' : 'btn-outline-success')
                       .html('<i class="fas ' + (enabled ? 'fa-toggle-off' : 'fa-toggle-on') + ' mr-1"></i> ' + (enabled ? 'Disable' : 'Enable'))
                       .data('enabled', enabled ? '1' : '0');
                       
                    $('#sync-btn-' + slug).prop('disabled', !enabled);

                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'An error occurred.', 'error');
                    btn.html(originalHtml);
                }
            },
            error: function(xhr) {
                var message = 'An error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showNotification(message, 'error');
                btn.html(originalHtml);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.sync-gateway-btn', function() {
        var btn = $(this);
        var slug = btn.data('slug');
        var originalHtml = btn.html();

        if (btn.prop('disabled')) return;

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Syncing...');

        $.ajax({
            url: '{{ url('external-apps/hrms/sync') }}/' + slug,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'An error occurred.', 'error');
                }
            },
            error: function(xhr) {
                var message = 'An error occurred during sync.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showNotification(message, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    function showNotification(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alert = $(
            '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                '</button>' +
            '</div>'
        );
        $('.card-body hr').first().after(alert);
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
});
</script>
@endpush

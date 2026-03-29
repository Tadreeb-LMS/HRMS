@extends('backend.layouts.app')

@section('title', 'Configure ' . $provider['name'] . ' | ' . app_name())

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="card-title mb-0">
                            <i class="{{ $provider['icon'] }} mr-2"></i>
                            Configure {{ $provider['name'] }}
                        </h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="{{ route('admin.hrms.settings') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Providers
                        </a>
                    </div>
                </div>
                <hr/>

                @if(session('success') || request('oauth_success') == '1')
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') ?? 'Zoho authentication successful! Refresh token validated and saved permanently.' }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error') || request()->has('oauth_error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') ?? request('oauth_error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <p class="text-muted">{{ $provider['description'] }} Enter your authentication credentials below.</p>

                <form method="POST" action="{{ route('admin.hrms.store', ['slug' => $slug]) }}" class="form-horizontal mt-4">
                    @csrf
                    
                    @if($slug === 'zoho')
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">API Domain <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <select name="hrms_zoho_api_domain" class="form-control" required>
                                <option value="zoho.com" {{ ($settings['hrms_zoho_api_domain'] ?? '') == 'zoho.com' ? 'selected' : '' }}>zoho.com (US)</option>
                                <option value="zoho.eu" {{ ($settings['hrms_zoho_api_domain'] ?? '') == 'zoho.eu' ? 'selected' : '' }}>zoho.eu (Europe)</option>
                                <option value="zoho.in" {{ ($settings['hrms_zoho_api_domain'] ?? '') == 'zoho.in' ? 'selected' : '' }}>zoho.in (India)</option>
                                <option value="zoho.com.au" {{ ($settings['hrms_zoho_api_domain'] ?? '') == 'zoho.com.au' ? 'selected' : '' }}>zoho.com.au (Australia)</option>
                            </select>
                            <small class="form-text text-muted">Select the data center region where your Zoho account is hosted.</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">Client ID <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_zoho_client_id" class="form-control" value="{{ $settings['hrms_zoho_client_id'] ?? '' }}" placeholder="Enter Zoho Client ID" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">Client Secret <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="password" name="hrms_zoho_client_secret" class="form-control" value="{{ $settings['hrms_zoho_client_secret'] ?? '' }}" placeholder="Enter Zoho Client Secret" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">Organization ID</label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_zoho_organization_id" class="form-control" value="{{ $settings['hrms_zoho_organization_id'] ?? '' }}" placeholder="Optional: Enter Zoho Organization ID if required">
                        </div>
                    </div>

                    @if(!empty($settings['hrms_zoho_refresh_token']))
                        <div class="form-group row">
                            <label class="col-md-3 form-control-label">OAuth Status</label>
                            <div class="col-md-9">
                                <span class="text-success" style="font-weight: 600;"><i class="fas fa-check-circle"></i> Authenticated with Zoho</span>
                                <a href="{{ route('admin.hrms.zoho.redirect') }}" class="btn btn-sm btn-outline-warning ml-3">Re-Authenticate</a>
                            </div>
                        </div>
                    @else
                        @if(!empty($settings['hrms_zoho_client_id']) && !empty($settings['hrms_zoho_client_secret']))
                            <div class="form-group row">
                                <label class="col-md-3 form-control-label">OAuth Authentication</label>
                                <div class="col-md-9">
                                    <a href="{{ route('admin.hrms.zoho.redirect') }}" class="btn btn-info">
                                        <i class="fas fa-sign-in-alt"></i> Login with Zoho
                                    </a>
                                    <small class="form-text text-muted">You must authenticate to generate a refresh token before enabling.</small>
                                </div>
                            </div>
                        @else
                            <div class="form-group row">
                                <label class="col-md-3 form-control-label">OAuth Authentication</label>
                                <div class="col-md-9">
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Please save your Client ID and Secret first to authenticate.</span>
                                </div>
                            </div>
                        @endif
                    @endif

                    <input type="hidden" name="hrms_zoho_active" value="1">

                    @elseif($slug === 'sap')
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">API Key <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_sap_api_key" class="form-control" value="{{ $settings['hrms_sap_api_key'] ?? '' }}" placeholder="Enter SAP API Key" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">Company ID <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_sap_company_id" class="form-control" value="{{ $settings['hrms_sap_company_id'] ?? '' }}" placeholder="Enter SAP Company ID" required>
                        </div>
                    </div>
                    <input type="hidden" name="hrms_sap_active" value="1">

                    @elseif($slug === 'darwinbox')
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">API Token <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_darwinbox_api_token" class="form-control" value="{{ $settings['hrms_darwinbox_api_token'] ?? '' }}" placeholder="Enter Darwinbox Token" required>
                        </div>
                    </div>
                    <input type="hidden" name="hrms_darwinbox_active" value="1">

                    @elseif($slug === 'custom')
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label">Bearer Token <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="hrms_custom_bearer_token" class="form-control" value="{{ $settings['hrms_custom_bearer_token'] ?? '' }}" placeholder="Enter generic/custom Bearer Token" required>
                        </div>
                    </div>
                    <input type="hidden" name="hrms_custom_active" value="1">

                    @endif

                    <div class="form-group row">
                        <div class="col-md-9 offset-md-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i> Save Configuration
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

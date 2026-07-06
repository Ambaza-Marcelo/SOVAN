
@extends('backend.layouts.master')

@section('title')
@lang('messages.setting') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .setting-card {
        margin-bottom: 20px;
    }
    .setting-logo {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .info-value {
        color: #212529;
        margin-bottom: 15px;
    }
    .nav-tabs .nav-link {
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        font-weight: 600;
    }
    .detail-section {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.setting')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.settings_section_heading')</h4>
                    <p class="float-right mb-2">
                            <a class="btn btn-primary text-white" href="{{ route('admin.settings.create') }}">@lang('messages.new')</a>
                    </p>
                    <div class="clearfix"></div>
                        @include('backend.layouts.partials.messages')
                    
                               @foreach ($settings as $setting)
                    <div class="setting-card">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">{{ $setting->name }}</h5>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.settings.edit', $setting->id) }}">
                                                <i class="fa fa-edit"></i> @lang('messages.edit')
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.settings.destroy', $setting->id) }}"
                                            onclick="event.preventDefault(); if(confirm({{ json_encode(__('messages.setting_delete_confirm')) }})) { document.getElementById('delete-form-{{ $setting->id }}').submit(); }">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </a>
                                            <form id="delete-form-{{ $setting->id }}" action="{{ route('admin.settings.destroy', $setting->id) }}" method="POST" style="display: none;">
                                                @method('DELETE')
                                                @csrf
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#general-{{ $setting->id }}" role="tab">@lang('messages.general_information')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#fiscal-{{ $setting->id }}" role="tab">@lang('messages.fiscal_information')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#address-{{ $setting->id }}" role="tab">@lang('messages.settings_tab_address')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#contact-{{ $setting->id }}" role="tab">@lang('messages.tab_contact')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#bank-{{ $setting->id }}" role="tab">@lang('messages.bank_coordinates')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#other-{{ $setting->id }}" role="tab">@lang('messages.others_tab')</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#backup-{{ $setting->id }}" role="tab">@lang('messages.backups_tab')</a>
                                    </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content mt-3">
                                    <!-- General Information Tab -->
                                    <div class="tab-pane fade show active" id="general-{{ $setting->id }}" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4 text-center mb-3">
                                                @if($setting->logo)
                                                    <img class="setting-logo" src="{{ asset('storage/logo')}}/{{ $setting->logo }}" alt="@lang('messages.setting_logo_alt')">
                                                @else
                                                    <div class="setting-logo bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fa fa-image fa-3x text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-8">
                                                <div class="detail-section">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="info-label">@lang('messages.company_name_label')</div>
                                                            <div class="info-value">{{ $setting->name ?? __('messages.not_available_short') }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="info-label">@lang('messages.developer_label')</div>
                                                            <div class="info-value">{{ $setting->developpeur ?? __('messages.not_available_short') }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="info-label">@lang('messages.number_of_lines_label')</div>
                                                            <div class="info-value">{{ $setting->max_line ?? __('messages.not_available_short') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fiscal Information Tab -->
                                    <div class="tab-pane fade" id="fiscal-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.taxpayer_type_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->tp_type == 1) 
                                                            @lang('messages.taxpayer_type_individual')
                                                        @elseif($setting->tp_type == 2) 
                                                            @lang('messages.taxpayer_type_company')
                                                        @else 
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.nif_label')</div>
                                                    <div class="info-value">{{ $setting->nif ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.trade_register_rc_label')</div>
                                                    <div class="info-value">{{ $setting->rc ?? __('messages.not_available_short') }}</div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.vat_taxpayer_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->vat_taxpayer == 1) 
                                                            <span class="badge badge-success">@lang('messages.word_yes')</span> 
                                                        @else 
                                                            <span class="badge badge-secondary">@lang('messages.word_no')</span> 
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.consumption_taxpayer_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->ct_taxpayer == 1) 
                                                            <span class="badge badge-success">@lang('messages.word_yes')</span> 
                                                        @else 
                                                            <span class="badge badge-secondary">@lang('messages.word_no')</span> 
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.local_tax_taxpayer_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->tl_taxpayer == 1) 
                                                            <span class="badge badge-success">@lang('messages.word_yes')</span> 
                                                        @else 
                                                            <span class="badge badge-secondary">@lang('messages.word_no')</span> 
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.fiscal_center_label')</div>
                                                    <div class="info-value">{{ $setting->tp_fiscal_center ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.activity_sector_label')</div>
                                                    <div class="info-value">{{ $setting->tp_activity_sector ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.legal_form_label')</div>
                                                    <div class="info-value">{{ $setting->tp_legal_form ?? __('messages.not_available_short') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Tab -->
                                    <div class="tab-pane fade" id="address-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.province_label')</div>
                                                    <div class="info-value">{{ $setting->province ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.commune_label')</div>
                                                    <div class="info-value">{{ $setting->commune ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.zone_label')</div>
                                                    <div class="info-value">{{ $setting->zone ?? __('messages.not_available_short') }}</div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.quartier_label')</div>
                                                    <div class="info-value">{{ $setting->quartier ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.rue_label')</div>
                                                    <div class="info-value">{{ $setting->rue ?? __('messages.not_available_short') }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.postal_code_label')</div>
                                                    <div class="info-value">{{ $setting->postal_number ?? __('messages.not_available_short') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Tab -->
                                    <div class="tab-pane fade" id="contact-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.telephone_1_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->telephone1)
                                                            <a href="tel:{{ $setting->telephone1 }}">{{ $setting->telephone1 }}</a>
                                                        @else
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.telephone_2_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->telephone2)
                                                            <a href="tel:{{ $setting->telephone2 }}">{{ $setting->telephone2 }}</a>
                                                        @else
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">@lang('messages.email_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->email)
                                                            <a href="mailto:{{ $setting->email }}">{{ $setting->email }}</a>
                                                        @else
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Details Tab -->
                                    <div class="tab-pane fade" id="bank-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            @php
                                                $bankNames = $setting->bank_name ?? [];
                                                $accountNumbers = $setting->account_number ?? [];
                                                $bankCount = max(count($bankNames), count($accountNumbers), 1);
                                            @endphp
                                            @if($bankCount > 0 && (!empty($bankNames[0]) || !empty($accountNumbers[0])))
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>@lang('messages.bank_name_column')</th>
                                                                <th>@lang('messages.account_number_column')</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @for($i = 0; $i < $bankCount; $i++)
                                                                @if(!empty($bankNames[$i] ?? '') || !empty($accountNumbers[$i] ?? ''))
                                                                <tr>
                                                                    <td>{{ $bankNames[$i] ?? '—' }}</td>
                                                                    <td>{{ $accountNumbers[$i] ?? '—' }}</td>
                                                                </tr>
                                                                @endif
                                                            @endfor
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted mb-0">@lang('messages.no_bank_details')</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Other Tab -->
                                    <div class="tab-pane fade" id="other-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-label">@lang('messages.created_at_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->created_at)
                                                            {{ \Carbon\Carbon::parse($setting->created_at)->format('d/m/Y H:i') }}
                                                        @else
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-label">@lang('messages.updated_at_label')</div>
                                                    <div class="info-value">
                                                        @if($setting->updated_at)
                                                            {{ \Carbon\Carbon::parse($setting->updated_at)->format('d/m/Y H:i') }}
                                                        @else
                                                            @lang('messages.not_available_short')
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Backup Tab -->
                                    <div class="tab-pane fade" id="backup-{{ $setting->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5 class="mb-3">@lang('messages.database_backup_title')</h5>
                                                    <p class="text-muted mb-4">
                                                        @lang('messages.database_backup_intro')
                                                    </p>
                                                    <div class="alert alert-info">
                                                        <i class="fa fa-info-circle"></i> 
                                                        <strong>@lang('messages.note_label'):</strong> @lang('messages.database_backup_note')
                                                    </div>
                                                    <form action="{{ route('admin.settings.backup-database') }}" method="POST" id="backup-form-{{ $setting->id }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-lg">
                                                            <i class="fa fa-download"></i> @lang('messages.download_database_backup')
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if(count($settings) == 0)
                        <div class="alert alert-info text-center">
                            <h5>@lang('messages.no_settings_found')</h5>
                            <p>{{ __('messages.no_settings_hint', ['new' => __('messages.new')]) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
     <script>
    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };
     </script>
@endsection

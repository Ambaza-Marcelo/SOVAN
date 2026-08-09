
@extends('backend.layouts.master')

@section('title')
@lang('companies') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .company-card {
        margin-bottom: 20px;
    }
    .company-logo {
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
                <h4 class="page-title pull-left">@lang('companies')</h4>
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
                    <h4 class="header-title float-left">Companies</h4>
                    <p class="float-right mb-2">
                        <a class="btn btn-primary text-white" href="{{ route('admin.hr-companies.create') }}">@lang('messages.new')</a>
                    </p>
                    <div class="clearfix"></div>
                    @include('backend.layouts.partials.messages')
                    
                    @foreach ($companies as $company)
                    <div class="company-card">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">{{ $company->name ?? 'N/A' }}</h5>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-success btn-sm text-white" href="{{ route('admin.hr-companies.edit', $company->id) }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('setting.edit'))
                                            <a class="btn btn-danger btn-sm text-white" href="{{ route('admin.hr-companies.destroy', $company->id) }}"
                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this item? This action is irreversible.')) { document.getElementById('delete-form-{{ $company->id }}').submit(); }">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                            <form id="delete-form-{{ $company->id }}" action="{{ route('admin.hr-companies.destroy', $company->id) }}" method="POST" style="display: none;">
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
                                        <a class="nav-link active" data-toggle="tab" href="#general-{{ $company->id }}" role="tab">Informations Générales</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#fiscal-{{ $company->id }}" role="tab">Informations Fiscales</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#address-{{ $company->id }}" role="tab">Adresse</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#contact-{{ $company->id }}" role="tab">Contact</a>
                                    </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content mt-3">
                                    <!-- General Information Tab -->
                                    <div class="tab-pane fade show active" id="general-{{ $company->id }}" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4 text-center mb-3">
                                                @if($company->logo)
                                                    <img class="company-logo" src="{{ asset('storage/logo')}}/{{ $company->logo }}" alt="Logo">
                                                @else
                                                    <div class="company-logo bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fa fa-image fa-3x text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-8">
                                                <div class="detail-section">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="info-label">Nom de l'entreprise</div>
                                                            <div class="info-value">{{ $company->name ?? 'N/A' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="info-label">Développeur</div>
                                                            <div class="info-value">{{ $company->developpeur ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fiscal Information Tab -->
                                    <div class="tab-pane fade" id="fiscal-{{ $company->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">NIF</div>
                                                    <div class="info-value">{{ $company->nif ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Registre Commerce (RC)</div>
                                                    <div class="info-value">{{ $company->rc ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Code</div>
                                                    <div class="info-value">{{ $company->code ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Tab -->
                                    <div class="tab-pane fade" id="address-{{ $company->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">Commune</div>
                                                    <div class="info-value">{{ $company->commune ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Zone</div>
                                                    <div class="info-value">{{ $company->zone ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Quartier</div>
                                                    <div class="info-value">{{ $company->quartier ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="info-label">Rue</div>
                                                    <div class="info-value">{{ $company->rue ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Tab -->
                                    <div class="tab-pane fade" id="contact-{{ $company->id }}" role="tabpanel">
                                        <div class="detail-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-label">Téléphone 1</div>
                                                    <div class="info-value">
                                                        @if($company->telephone1)
                                                            <a href="tel:{{ $company->telephone1 }}">{{ $company->telephone1 }}</a>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Téléphone 2</div>
                                                    <div class="info-value">
                                                        @if($company->telephone2)
                                                            <a href="tel:{{ $company->telephone2 }}">{{ $company->telephone2 }}</a>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-label">Email</div>
                                                    <div class="info-value">
                                                        @if($company->email)
                                                            <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if(count($companies) == 0)
                        <div class="alert alert-info text-center">
                            <h5>Aucune entreprise trouvée</h5>
                            <p>Cliquez sur "Nouveau" pour créer une nouvelle entreprise.</p>
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
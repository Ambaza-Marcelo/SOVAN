@extends('backend.layouts.master')

@section('title')
@lang('Modifier Congé Payé') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier Congé Payé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-conge-payes.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier Congé Payé')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Modifier Congé Payé</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.hr-conge-payes.update', $conge_paye->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label for="session">Session *</label>
                            <input type="number" class="form-control" name="session" id="session" value="{{ old('session', $conge_paye->session) }}" required min="2023">
                        </div>
                        <div class="form-group">
                            <label for="nbre_jours">Nombre de jours *</label>
                            <input type="number" class="form-control" name="nbre_jours" id="nbre_jours" value="{{ old('nbre_jours', $conge_paye->nbre_jours) }}" required min="1">
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@extends('backend.layouts.master')

@section('title')
@lang('messages.Nouveau Réglage') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Nouveau Reglage')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-reglages.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Nouveau Réglage')</span></li>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Nouveau Réglage')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-reglages.store') }}" method="POST">
                        @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Plafond Impôt *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="prafond_impot" placeholder="@lang('messages.Entrer Plafond Impôt')" required min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Plafond Cotisation *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="prafond_cotisation" placeholder="@lang('messages.Entrer Plafond Cotisation')" required min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Nbre Jrs Ouvrable *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="nbre_jours_ouvrables" placeholder="@lang('messages.Entrer Nbre Jrs Ouvrable')" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Nbre Jrs Anticipation Congé *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="jour_anticipation_conge" placeholder="@lang('messages.Entrer Nbre Jrs Anticipation Congé')" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Nbre Jrs Congé Par mois *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="jour_conge_par_mois" placeholder="@lang('messages.Entrer Nbre Jrs Congé Par mois')" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Min Jrs Congé Payé par Mois') <span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="min_jour_conge_paye" placeholder="@lang('messages.Entrer Min Jrs Congé Payé par Mois')" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="">@lang('messages.Max Jrs Congé Payé Par Mois *')<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="max_jour_conge_paye" placeholder="@lang('messages.Max Jrs Congé Payé Par Mois')" required min="0">
                                    </div>
                                </div>
                            </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.Enregistrer')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection
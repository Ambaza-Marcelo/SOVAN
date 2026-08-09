
@extends('backend.layouts.master')

@section('title')
@lang('Modifier le congé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier le congé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-take-conges.index',$take_conge->company_id) }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier le congé')</span></li>
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
                    <h4 class="header-title">Modifier le congé</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conges.update',$take_conge->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="employe_id">@lang('Employé')</label>
                                <select class="form-control" name="employe_id" id="employe_id">
                                    <option disabled>Merci de choisir</option>
                                    @foreach($employes as $employe)
                                    <option value="{{ $employe->id }}" @selected($take_conge->employe_id == $employe->id)>{{ $employe->firstname }} {{ $employe->lastname }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="stagiaire_id">@lang('Stagiaire')</label>
                                <select class="form-control" name="stagiaire_id" id="stagiaire_id">
                                    <option value="">--</option>
                                    @foreach($stagiaires as $stagiaire)
                                    <option value="{{ $stagiaire->id }}" @selected($take_conge->stagiaire_id == $stagiaire->id)>{{ $stagiaire->firstname }} {{ $stagiaire->lastname }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="form-group">
                            <label for="type_conge_id">@lang('Type Congé')</label>
                            <select class="form-control" name="type_conge_id" id="type_conge_id">
                                <option disabled>Merci de choisir</option>
                                @foreach($type_conges as $type_conge)
                                <option value="{{ $type_conge->id }}" @selected($take_conge->type_conge_id == $type_conge->id)>{{ $type_conge->libelle}}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="etat">@lang('Etat')</label>
                                <input type="text" class="form-control" id="etat" name="etat" value="{{ $take_conge->etat }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('Date de départ')</label>
                                <input type="datetime-local" class="form-control" id="date_heure_debut" name="date_heure_debut" value="{{ \Carbon\Carbon::parse($take_conge->date_heure_debut)->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('Date de retour')</label>
                                <input type="datetime-local" class="form-control" id="date_heure_fin" name="date_heure_fin" value="{{ \Carbon\Carbon::parse($take_conge->date_heure_fin)->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_pris">@lang('Nombre de jours pris')</label>
                                <input type="number" class="form-control" id="nbre_jours_conge_pris" name="nbre_jours_conge_pris" value="{{ $take_conge->nbre_jours_conge_pris }}" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_heures_conge_pris">@lang('Nombre d\'heures prises')</label>
                                <input type="number" class="form-control" id="nbre_heures_conge_pris" name="nbre_heures_conge_pris" value="{{ $take_conge->nbre_heures_conge_pris }}" step="any" min="0">
                            </div>
                        </div>
                    </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
@endsection

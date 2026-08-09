
@extends('backend.layouts.master')

@section('title')
@lang('Nouveau journal congé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Nouveau journal congé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-journal-conges.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.new')</span></li>
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
                    <h4 class="header-title">Nouveau journal congé</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.hr-journal-conges.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="employe_id">Employé *:</label>
                                    <select name="employe_id" id="employe_id" class="form-control" required>
                                        <option disabled selected value="">Merci de choisir</option>
                                        @foreach ($employes as $employe)
                                        <option value="{{ $employe->id }}">{{ $employe->firstname }} {{ $employe->lastname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type_conge_id">Type Congé *:</label>
                                    <select name="type_conge_id" id="type_conge_id" class="form-control" required>
                                        <option disabled selected value="">Merci de choisir</option>
                                        @foreach ($type_conges as $type_conge)
                                        <option value="{{ $type_conge->id }}">{{ $type_conge->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_heure_debut">Date début *:</label>
                                    <input type="datetime-local" name="date_heure_debut" class="form-control" id="date_heure_debut" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_heure_fin">Date fin *:</label>
                                    <input type="datetime-local" name="date_heure_fin" class="form-control" id="date_heure_fin" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nbre_jours_conge_pris">Nbre jours congé pris:</label>
                                    <input type="number" step="any" min="0" name="nbre_jours_conge_pris" class="form-control" id="nbre_jours_conge_pris">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nbre_heures_conge_pris">Nbre heures congé pris:</label>
                                    <input type="number" step="any" min="0" name="nbre_heures_conge_pris" class="form-control" id="nbre_heures_conge_pris">
                                </div>
                            </div>
                        </div>
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.save')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection

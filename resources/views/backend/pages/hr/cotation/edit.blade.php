
@extends('backend.layouts.master')

@section('title')
@lang('Modifier la cotation') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Modifier la cotation')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-cotations.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier la cotation')</span></li>
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
                    <h4 class="header-title">Modifier la cotation</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.hr-cotations.update',$cotation->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="employe_id">Employé *:</label>
                                    <select name="employe_id" id="employe_id" class="form-control" required>
                                        <option disabled value="">Merci de choisir</option>
                                        @foreach ($employes as $employe)
                                        <option value="{{ $employe->id }}" @selected($cotation->employe_id == $employe->id)>{{ $employe->firstname }} {{ $employe->lastname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="somme_note_obtenu">Note obtenue *:</label>
                                    <input type="number" step="any" min="0" name="somme_note_obtenu" class="form-control" id="somme_note_obtenu" value="{{ $cotation->somme_note_obtenu }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="somme_note_total">Note totale *:</label>
                                    <input type="number" step="any" min="0" name="somme_note_total" class="form-control" id="somme_note_total" value="{{ $cotation->somme_note_total }}" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection

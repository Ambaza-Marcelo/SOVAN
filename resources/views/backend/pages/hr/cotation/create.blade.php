
@extends('backend.layouts.master')

@section('title')
@lang('messages.setting_create') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.setting_create')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.settings.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.setting_create')</span></li>
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
                    <h4 class="header-title">@lang('messages.Create setting')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.settings.store') }}" method="POST"  enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="name">@lang('messages.Nom Entreprise')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="name" placeholder="@lang('messages.Entrer Nom ')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="nif">@lang('messages.NIF')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="nif" placeholder="@lang('messages.Entrer NIF')" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="rc">@lang('messages.Registre Commerce')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="rc" placeholder="@lang('messages.Entrer RC')" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="commune">@lang('messages.Commune')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="commune" placeholder="@lang('messages.Entrer Commune ')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="zone">@lang('messages.Zone')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="zone" placeholder="@lang('messages.Entrer Zone')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="quartier">@lang('messages.Quartier')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="quartier" placeholder="@lang('messages.Entrer Quartier')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="avenue">@lang('messages.Avenue')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="avenue" placeholder="@lang('messages.Entrer Avenue ')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="rue">@lang('messages.Rue')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="rue" placeholder="@lang('messages.Entrer Rue')" required minlength="2" maxlength="255">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="telephone1">@lang('messages.Telephone 1')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="telephone1" placeholder="@lang('messages.Entrer telephone 1')" required minlength="6" maxlength="15">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="telephone2">@lang('messages.Telephone 2')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="telephone2" placeholder="@lang('messages.Entrer Telephone 2 ')" required minlength="6" maxlength="15">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="email">@lang('messages.Email')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="email" placeholder="@lang('messages.Entrer Email')" required minlength="5" maxlength="255">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="logo">@lang('messages.Logo')<span class="text-danger"></span></label>
                                        <input type="file" class="form-control" name="logo" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="developpeur">@lang('messages.Developpeur')<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="developpeur" placeholder="@lang('messages.Entrer Developpeur')">
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
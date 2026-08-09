
@extends('backend.layouts.master')

@section('title')
@lang('Modifier le groupe indemnité') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier le groupe indemnité')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-groupe-indemnites.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier le groupe indemnité')</span></li>
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
                    <h4 class="header-title">Modifier le groupe indemnité</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-groupe-indemnites.update',$groupe_indemnite->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="designation" class="col-form-label">Designation *:</label>
                                        <input type="text" name="designation" class="form-control" id="designation" value="{{ $groupe_indemnite->designation }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="classe_inferieure" class="col-form-label">Classe Inférieure *:</label>
                                        <input type="text" name="classe_inferieure" class="form-control" id="classe_inferieure" value="{{ $groupe_indemnite->classe_inferieure }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="classe_superieure" class="col-form-label">Classe Supérieure *:</label>
                                        <input type="text" name="classe_superieure" class="form-control" id="classe_superieure" value="{{ $groupe_indemnite->classe_superieure }}">
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

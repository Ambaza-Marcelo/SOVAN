
@extends('backend.layouts.master')

@section('title')
@lang('Modifier l\'impôt') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier l\'impôt')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-impots.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier l\'impôt')</span></li>
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
                    <h4 class="header-title">Modifier l'impôt</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-impots.update',$impot->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="groupe_impot_id">Groupe Impôt *:</label>
                                    <select name="groupe_impot_id" id="groupe_impot_id" class="form-control" required>
                                        <option disabled value="">merci de choisir</option>
                                        @foreach ($groupe_impots as $groupe_impot)
                                        <option value="{{ $groupe_impot->id }}" @selected($impot->groupe_impot_id == $groupe_impot->id)>{{ $groupe_impot->designation }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pourcentage_impot">Pourcentage Impôt *:</label>
                                    <input type="number" name="pourcentage_impot" class="form-control" id="pourcentage_impot" value="{{ $impot->pourcentage_impot }}" step="any" min="0" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
        
</div>
@endsection

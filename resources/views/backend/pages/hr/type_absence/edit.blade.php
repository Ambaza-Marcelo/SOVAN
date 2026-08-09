@extends('backend.layouts.master')

@section('title')
@lang('Modifier Motif Absence') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier Motif Absence')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-reglages.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier Motif Absence')</span></li>
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
                    <h4 class="header-title">Modifier Motif Absence</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.hr-type-absences.update', $type_absence->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label for="name">Désignation *</label>
                            <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $type_absence->name) }}" required maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <input type="text" class="form-control" name="type" id="type" value="{{ old('type', $type_absence->type) }}" maxlength="255">
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

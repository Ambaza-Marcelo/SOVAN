@extends('backend.layouts.master')

@section('title')
@lang('Modifier Type Congé') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier Type Congé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-type-conges.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier Type Congé')</span></li>
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
                    <h4 class="header-title">Modifier Type Congé</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.hr-type-conges.update', $type_conge->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label for="libelle">Désignation *</label>
                            <input type="text" class="form-control" name="libelle" id="libelle" value="{{ old('libelle', $type_conge->libelle) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea class="form-control" name="description" id="description" required minlength="10" maxlength="500">{{ old('description', $type_conge->description) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.update')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

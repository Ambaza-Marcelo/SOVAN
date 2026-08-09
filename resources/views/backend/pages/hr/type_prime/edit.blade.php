
@extends('backend.layouts.master')

@section('title')
@lang('Modifier le type prime') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier le type prime')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-type-primes.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier le type prime')</span></li>
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
                    <h4 class="header-title">Modifier le type prime</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-type-primes.update',$type_prime->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="col-form-label">Designation *:</label>
                                        <input type="text" name="name" class="form-control" id="name" value="{{ $type_prime->name }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type" class="col-form-label">Type *:</label>
                                        <select name="type" id="type" class="form-control">
                                            <option value="imposable" @selected($type_prime->type == 'imposable')>Imposable</option>
                                            <option value="non_imposable" @selected($type_prime->type == 'non_imposable')>Non Imposable</option>
                                        </select>
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

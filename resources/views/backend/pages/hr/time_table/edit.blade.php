
@extends('backend.layouts.master')

@section('title')
    @lang('messages.schedule_edit_title') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <style>
        .shift-option {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            min-width: 100px;
        }
        .shift-option:hover {
            border-color: #007bff;
        }
        .shift-option.selected {
            border-color: #007bff;
            background-color: #007bff;
            color: white;
        }
        .shift-option input[type="radio"] {
            display: none;
        }
        .shift-a-badge {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .shift-b-badge {
            background-color: #2196F3;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .shift-off-badge {
            background-color: #9E9E9E;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .day-column {
            padding: 15px;
            border: 1px solid #ddd;
            margin: 5px 0;
            border-radius: 5px;
        }
    </style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.schedule_edit_title')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-time-tables.index') }}">@lang('messages.Horaires')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.schedule_edit_title')</h4>

                    <form method="GET" action="{{ route('admin.hr-time-tables.edit', $employe->id) }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="week">@lang('messages.schedule_week_label'):</label>
                                    <input type="week" name="week" id="week" class="form-control" value="{{ $selectedWeek }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-primary">@lang('messages.schedule_load')</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.hr-time-tables.update', $employe->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                        <input type="hidden" name="week" value="{{ $selectedWeek }}">

                        <div class="alert alert-info">
                            <strong>@lang('messages.schedule_employee_label'):</strong> {{ $employe->lastname }} {{ $employe->firstname }}<br>
                            <strong>@lang('messages.schedule_department_label'):</strong> {{ $employe->departement->name ?? 'N/A' }}
                        </div>

                        <div class="row">
                            @foreach($weekDates as $index => $date)
                                @php
                                    $dateStr = $date->format('Y-m-d');
                                    $existingShift = $existingTimeTables->get($dateStr);
                                    $defaultShift = $existingShift ? $existingShift->shift_name : '';
                                @endphp
                                <div class="col-md-6">
                                    <div class="day-column">
                                        <h5>
                                            {{ $date->format('d/m/Y') }}<br>
                                            <small>{{ $date->locale('fr')->dayName }}</small>
                                        </h5>
                                        
                                        <div class="form-group">
                                            <label class="shift-option {{ $defaultShift == 'A' ? 'selected' : '' }}">
                                                <input type="radio" name="schedules[{{ $index }}]" value="A" {{ $defaultShift == 'A' ? 'checked' : '' }}>
                                                <span class="shift-a-badge">@lang('messages.SHIFT A')<br><small>@lang('messages.7h30-16h00')</small></span>
                                            </label>
                                            
                                            <label class="shift-option {{ $defaultShift == 'B' ? 'selected' : '' }}">
                                                <input type="radio" name="schedules[{{ $index }}]" value="B" {{ $defaultShift == 'B' ? 'checked' : '' }}>
                                                <span class="shift-b-badge">@lang('messages.SHIFT B')<br><small>@lang('messages.15h00-Close')</small></span>
                                            </label>
                                            
                                            <label class="shift-option {{ $defaultShift == 'OFF' || !$defaultShift ? 'selected' : '' }}">
                                                <input type="radio" name="schedules[{{ $index }}]" value="OFF" {{ $defaultShift == 'OFF' || !$defaultShift ? 'checked' : '' }}>
                                                <span class="shift-off-badge">@lang('messages.OFF')</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> @lang('messages.schedule_save_changes')
                            </button>
                            <a href="{{ route('admin.hr-time-tables.index', ['week' => $selectedWeek, 'departement_id' => $employe->departement_id]) }}" class="btn btn-secondary">
                                <i class="fa fa-times"></i> @lang('messages.schedule_cancel')
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    @include('backend.pages.hr.time_table.partials.scripts')
@endsection


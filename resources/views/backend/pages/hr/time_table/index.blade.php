
@extends('backend.layouts.master')

@section('title')
@lang('Horaire du personnel') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .shift-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .shift-a {
            background-color: #4CAF50;
            color: white;
        }
        .shift-b {
            background-color: #2196F3;
            color: white;
        }
        .shift-off {
            background-color: #9E9E9E;
            color: white;
        }
        .time-table-container {
            overflow-x: auto;
        }
        .time-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .time-table th,
        .time-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .time-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .time-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Horaire du personnel')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
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
                    <h4 class="header-title float-left">Horaires hebdomadaires</h4>
                    <p class="float-right mb-2">
                        @if (Auth::guard('admin')->user()->can('hr_departement.create'))
                            <a href="{{ route('admin.hr-time-tables.create') }}" class="btn btn-success">
                                <i class="fa fa-plus-square" title="Créer un horaire" aria-hidden="false"></i> Nouvel horaire
                            </a>
                        @endif
                    </p>
                    <div class="clearfix"></div>

                    <!-- Filtres -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="GET" action="{{ route('admin.hr-time-tables.index') }}">
                                <div class="form-group">
                                    <label for="week">Semaine:</label>
                                    <input type="week" name="week" id="week" class="form-control" value="{{ $selectedWeek }}" required>
                                </div>
                        </div>
                        <div class="col-md-4">
                                <div class="form-group">
                                    <label for="departement_id">Département:</label>
                                    <select name="departement_id" id="departement_id" class="form-control">
                                        <option value="">Tous les départements</option>
                                        @foreach($departements as $departement)
                                            <option value="{{ $departement->id }}" {{ $selectedDepartement == $departement->id ? 'selected' : '' }}>
                                                {{ $departement->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                        </div>
                        <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-primary">Filtrer</button>
                                    @if($selectedDepartement)
                                        <a href="{{ route('admin.hr-time-tables.print-departement', ['week' => $selectedWeek, 'departement_id' => $selectedDepartement]) }}" 
                                           target="_blank" class="btn btn-info">
                                            <i class="fa fa-print"></i> Imprimer
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    @if($selectedDepartement)
                        <div class="time-table-container">
                            <table class="time-table">
                                <thead>
                                    <tr>
                                        <th>NOMS</th>
                                        @foreach($weekDates as $date)
                                            <th>
                                                {{ $date->format('d/m') }}<br>
                                                <small>{{ $date->locale('fr')->dayName }}</small>
                                            </th>
                                        @endforeach
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $employesGrouped = $timeTables->keys()->map(function($employeId) use ($timeTables) {
                                            return \App\Models\HrEmploye::find($employeId);
                                        })->filter()->groupBy('departement_id');
                                        
                                        $currentDepartementEmployes = \App\Models\HrEmploye::where('departement_id', $selectedDepartement)
                                            ->orderBy('firstname')
                                            ->orderBy('lastname')
                                            ->get();
                                    @endphp
                                    
                                    @foreach($currentDepartementEmployes as $employe)
                                        <tr>
                                            <td><strong>{{ strtoupper($employe->lastname . ' ' . $employe->firstname) }}</strong></td>
                                            @foreach($weekDates as $date)
                                                @php
                                                    $dateStr = $date->format('Y-m-d');
                                                    $timeTable = $timeTables->get($employe->id, collect())->get($dateStr);
                                                @endphp
                                                <td>
                                                    @if($timeTable)
                                                        @if($timeTable->shift_name == 'A')
                                                            <span class="shift-badge shift-a">A<br><small>7h30-16h00</small></span>
                                                        @elseif($timeTable->shift_name == 'B')
                                                            <span class="shift-badge shift-b">B<br><small>15h00-Close</small></span>
                                                        @else
                                                            <span class="shift-badge shift-off">{{ $timeTable->shift_name }}</span>
                                                        @endif
                                                    @else
                                                        <span class="shift-badge shift-off">OFF</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td>
                                                @if (Auth::guard('admin')->user()->can('hr_departement.edit'))
                                                    <a href="{{ route('admin.hr-time-tables.edit', ['id' => $employe->id, 'week' => $selectedWeek]) }}" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('admin.hr-time-tables.print-employe', ['id' => $employe->id, 'week' => $selectedWeek]) }}" 
                                                       target="_blank" class="btn btn-info btn-sm">
                                                        <i class="fa fa-print"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <p>Veuillez sélectionner un département pour afficher les horaires.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     
     @include('backend.pages.hr.time_table.partials.scripts')
@endsection


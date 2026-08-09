
@extends('backend.layouts.master')

@section('title')
@lang('First In Last Out') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
@endsection

@section('admin-content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">First In Last Out</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>Premier entrée / Dernier entrée / Total heures</span></li>
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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h4 class="header-title mb-0">Rapport des présences : Premier entrée, Dernier entrée et Total heures par jour</h4>
                            <p class="text-muted mb-0">Combien d'heures l'employé travaille par jour (données Biotime).</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.hr-first-in-last-out.export-excel', request()->query()) }}" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </a>
                        </div>
                    </div>

                    @include('backend.layouts.partials.messages')

                    @if(isset($error))
                        <div class="alert alert-danger">
                            <strong>Erreur:</strong> {{ $error }}
                            <hr>
                            <small>
                                <strong>Vérifications:</strong><br>
                                1. Variables dans <code>.env</code>: <code>GUZZLE_BIOTIME_URL</code>, <code>BIOTIME_USERNAME</code>, <code>BIOTIME_PASSWORD</code><br>
                                2. Serveur Biotime accessible depuis ce serveur<br>
                                3. Logs: <code>storage/logs/laravel.log</code>
                            </small>
                        </div>
                    @endif

                    <div class="filter-section">
                        <form id="filterForm" method="GET" action="{{ route('admin.hr-first-in-last-out.index') }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Département</label>
                                    <select name="department_ids[]" id="departmentFilter" class="form-control" multiple>
                                        @if(isset($departments) && is_array($departments))
                                            @php $selectedDepts = request()->get('department_ids', []); @endphp
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept['id'] }}" {{ in_array($dept['id'], (array)$selectedDepts) ? 'selected' : '' }}>
                                                    {{ $dept['dept_name'] }} ({{ $dept['dept_code'] ?? '' }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Employé</label>
                                    <select name="employee_ids[]" id="employeeFilter" class="form-control" multiple>
                                        @if(isset($employees) && is_array($employees))
                                            @php $selectedEmps = request()->get('employee_ids', []); @endphp
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp['id'] }}" {{ in_array($emp['id'], (array)$selectedEmps) ? 'selected' : '' }}>
                                                    {{ $emp['emp_code'] ?? '' }} - {{ $emp['first_name'] ?? '' }} {{ $emp['last_name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Date Début</label>
                                    <input type="date" name="start_date" id="startDate" class="form-control" value="{{ $startDate ?? date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-md-2">
                                    <label>Date Fin</label>
                                    <input type="date" name="end_date" id="endDate" class="form-control" value="{{ $endDate ?? date('Y-m-d') }}">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Rechercher
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="data-tables">
                        <table id="attendanceTable" class="text-center table table-striped table-bordered">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th>NO.</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Date</th>
                                    <th>Weekday</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Total Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($attendanceData) && is_array($attendanceData) && count($attendanceData) > 0)
                                    @foreach($attendanceData as $record)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $record['first_name'] ?? 'N/A' }}</td>
                                            <td>{{ $record['last_name'] ?? 'N/A' }}</td>
                                            <td>{{ $record['dept_name'] ?? 'N/A' }}</td>
                                            <td>{{ $record['position'] ?? 'N/A' }}</td>
                                            <td>
                                                @if(isset($record['date']) && $record['date'] !== 'N/A')
                                                    @php
                                                        try {
                                                            $dateObj = \Carbon\Carbon::parse($record['date']);
                                                            echo $dateObj->format('d/m/Y');
                                                        } catch (\Exception $e) {
                                                            echo $record['date'];
                                                        }
                                                    @endphp
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $record['weekday'] ?? 'N/A' }}</td>
                                            <td>{{ $record['check_in'] ?? 'N/A' }}</td>
                                            <td>{{ $record['check_out'] ?? 'N/A' }}</td>
                                            <td>{{ $record['total_time'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center">Aucune donnée. Utilisez les filtres et cliquez sur Rechercher.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#departmentFilter').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionnez un ou plusieurs départements',
                allowClear: true,
                language: { noResults: function() { return "Aucun résultat trouvé"; } }
            });
            $('#employeeFilter').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionnez un ou plusieurs employés',
                allowClear: true,
                language: { noResults: function() { return "Aucun résultat trouvé"; } }
            });

            @if(!isset($departments) || empty($departments))
            loadDepartments();
            @endif

            $('#departmentFilter').on('change', function() {
                loadEmployees($(this).val());
            });

            if ($('#attendanceTable').length && $('#attendanceTable tbody tr').length > 0 && !$('#attendanceTable tbody tr td').first().attr('colspan')) {
                $('#attendanceTable').DataTable({
                    responsive: true,
                    pageLength: 20,
                    order: [[5, 'desc']],
                    language: { url: '//cdn.datatables.net/plug-ins/1.10.18/i18n/French.json' }
                });
            }
        });

        function loadDepartments() {
            $.ajax({
                url: '{{ route("admin.hr-absences.get-departments") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.data && response.data.length > 0) {
                        $('#departmentFilter').empty();
                        $.each(response.data, function(i, dept) {
                            $('#departmentFilter').append($('<option></option>').attr('value', dept.id).text((dept.dept_name || '') + ' (' + (dept.dept_code || '') + ')'));
                        });
                        $('#departmentFilter').trigger('change');
                    }
                },
                error: function(xhr, status, error) { console.error('Erreur départements:', error); }
            });
        }

        function loadEmployees(departmentIds) {
            $('#employeeFilter').empty().append('<option value="">Chargement...</option>');
            var params = {};
            if (departmentIds && departmentIds.length > 0) params.department_id = departmentIds[0];
            $.ajax({
                url: '{{ route("admin.hr-absences.get-employees") }}',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    $('#employeeFilter').empty();
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, emp) {
                            var name = ((emp.first_name || '') + ' ' + (emp.last_name || '')).trim() || 'Sans nom';
                            $('#employeeFilter').append($('<option></option>').attr('value', emp.id).text((emp.emp_code || '') + ' - ' + name));
                        });
                    } else {
                        $('#employeeFilter').append('<option value="">Aucun employé trouvé</option>');
                    }
                    $('#employeeFilter').trigger('change');
                },
                error: function() {
                    $('#employeeFilter').empty().append('<option value="">Erreur de chargement</option>');
                }
            });
        }
    </script>
@endsection

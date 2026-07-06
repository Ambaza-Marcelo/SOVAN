
@extends('backend.layouts.master')

@section('title')
@lang('messages.Rapport de Présence') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-tag {
            display: inline-block;
            background: #027789;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            margin: 5px;
            font-size: 12px;
        }
        .filter-tag .close {
            margin-left: 5px;
            cursor: pointer;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Rapport de Présence')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.Rapport de Présence')</span></li>
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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="header-title mb-0">@lang('messages.attendance_report') - @lang('messages.first_in_last_out')</h4>
                        <div>
                            <a href="{{ route('admin.hr-absences.export-excel', request()->query()) }}" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel-o"></i> @lang('messages.export_excel')
                            </a>
                        </div>
                    </div>
                    
                    @include('backend.layouts.partials.messages')
                    
                    @if(isset($error))
                        <div class="alert alert-danger">
                            <strong>@lang('messages.Erreur:')</strong> {{ $error }}
                            <hr>
                            <small>
                                <strong>@lang('messages.Vérifications à faire:')</strong><br>
                                1. Vérifiez que les variables suivantes sont définies dans votre fichier <code>@lang('messages..env')</code>:<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<code>@lang('messages.GUZZLE_BIOTIME_URL=http://192.168.88.2:8080')</code><br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<code>@lang('messages.BIOTIME_USERNAME=Marcellin')</code><br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<code>@lang('messages.BIOTIME_PASSWORD=marcelo@2001')</code><br>
                                2. Vérifiez que le serveur Biotime est accessible depuis votre serveur Laravel<br>
                                3. Vérifiez les identifiants de connexion<br>
                                4. Consultez les logs Laravel: <code>@lang('messages.storage/logs/laravel.log')</code>
                            </small>
                        </div>
                    @endif

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form id="filterForm" method="GET" action="{{ route('admin.hr-absences.index') }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>@lang('messages.Département')</label>
                                    <select name="department_ids[]" id="departmentFilter" class="form-control" multiple>
                                        @if(isset($departments) && is_array($departments))
                                            @php
                                                $selectedDepts = request()->get('department_ids', []);
                                            @endphp
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept['id'] }}" 
                                                    {{ in_array($dept['id'], (array)$selectedDepts) ? 'selected' : '' }}>
                                                    {{ $dept['dept_name'] }} ({{ $dept['dept_code'] }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>@lang('messages.Employé')</label>
                                    <select name="employee_ids[]" id="employeeFilter" class="form-control" multiple>
                                        @if(isset($employees) && is_array($employees))
                                            @php
                                                $selectedEmps = request()->get('employee_ids', []);
                                            @endphp
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp['id'] }}" 
                                                    {{ in_array($emp['id'], (array)$selectedEmps) ? 'selected' : '' }}>
                                                    {{ $emp['emp_code'] }} - {{ $emp['first_name'] ?? '' }} {{ $emp['last_name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>@lang('messages.Date Début')</label>
                                    <input type="date" name="start_date" id="startDate" class="form-control" value="{{ $startDate ?? date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-md-2">
                                    <label>@lang('messages.Date Fin')</label>
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
                                    <th>@lang('messages.NO.')</th>
                                    <th>@lang('messages.First Name')</th>
                                    <th>@lang('messages.Last Name')</th>
                                    <th>@lang('messages.Department')</th>
                                    <th>@lang('messages.Position')</th>
                                    <th>@lang('messages.Date')</th>
                                    <th>@lang('messages.Weekday')</th>
                                    <th>@lang('messages.Check In')</th>
                                    <th>@lang('messages.Check Out')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($attendanceData) && is_array($attendanceData) && count($attendanceData) > 0)
                                    @foreach($attendanceData as $record)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
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
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center">@lang('messages.Aucune donnée de présence disponible')</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
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
     
     <!-- Select2 JS -->
     <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
     
     <script>
         $(document).ready(function() {
             // Initialize Select2 for departments
             $('#departmentFilter').select2({
                 theme: 'bootstrap4',
                 placeholder: 'Sélectionnez un ou plusieurs départements',
                 allowClear: true,
                 language: {
                     noResults: function() {
                         return "Aucun résultat trouvé";
                     }
                 }
             });

             // Initialize Select2 for employees
             $('#employeeFilter').select2({
                 theme: 'bootstrap4',
                 placeholder: 'Sélectionnez un ou plusieurs employés',
                 allowClear: true,
                 language: {
                     noResults: function() {
                         return "Aucun résultat trouvé";
                     }
                 }
             });

             // Load departments from API if not already loaded
             @if(!isset($departments) || empty($departments))
             loadDepartments();
             @endif

             // When department selection changes, load employees for selected departments
             $('#departmentFilter').on('change', function() {
                 var selectedDepts = $(this).val();
                 loadEmployees(selectedDepts);
             });

             // Initialize DataTable
             if ($('#attendanceTable').length) {
                 $('#attendanceTable').DataTable({
                     responsive: true,
                     pageLength: 20,
                     order: [[5, 'desc']], // Sort by date descending
                     language: {
                         url: '//cdn.datatables.net/plug-ins/1.10.18/i18n/French.json'
                     }
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
                         $.each(response.data, function(index, dept) {
                             $('#departmentFilter').append(
                                 $('<option></option>')
                                     .attr('value', dept.id)
                                     .text(dept.dept_name + ' (' + dept.dept_code + ')')
                             );
                         });
                         // Trigger change to update Select2
                         $('#departmentFilter').trigger('change');
                     }
                 },
                 error: function(xhr, status, error) {
                     console.error('Erreur lors du chargement des départements:', error);
                 }
             });
         }

         function loadEmployees(departmentIds) {
             // Clear current employees
             $('#employeeFilter').empty();
             $('#employeeFilter').append('<option value="">@lang('messages.Chargement...')</option>');

             var params = {};
             if (departmentIds && departmentIds.length > 0) {
                 // Load employees for first selected department (or all if none selected)
                 params.department_id = departmentIds[0];
             }

             $.ajax({
                 url: '{{ route("admin.hr-absences.get-employees") }}',
                 type: 'GET',
                 data: params,
                 dataType: 'json',
                 success: function(response) {
                     $('#employeeFilter').empty();
                     
                     if (response.data && response.data.length > 0) {
                         $.each(response.data, function(index, emp) {
                             var empName = (emp.first_name || '') + ' ' + (emp.last_name || '');
                             empName = empName.trim() || 'Sans nom';
                             $('#employeeFilter').append(
                                 $('<option></option>')
                                     .attr('value', emp.id)
                                     .text(emp.emp_code + ' - ' + empName)
                             );
                         });
                     } else {
                         $('#employeeFilter').append('<option value="">@lang('messages.Aucun employé trouvé')</option>');
                     }
                     
                     // Trigger change to update Select2
                     $('#employeeFilter').trigger('change');
                 },
                 error: function(xhr, status, error) {
                     console.error('Erreur lors du chargement des employés:', error);
                     $('#employeeFilter').empty();
                     $('#employeeFilter').append('<option value="">@lang('messages.Erreur de chargement')</option>');
                 }
             });
         }
     </script>
@endsection

@extends('backend.layouts.master')

@section('title')
@lang('messages.Nouvelle Fiche de Paie') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Nouvelle Fiche de Paie')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.Nouvelle Fiche de Paie')</span></li>
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
                    <h4 class="header-title">@lang('messages.Nouvelle Fiche de Paie')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-paiements.store') }}" method="POST">
                        @csrf
                    <input type="hidden" name="company_id" value="{{ $company_id }}">
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="form-group" style="position: relative;">
                            <label for="employe_search">@lang('messages.Employé')</label>
                            <input type="text" class="form-control" id="employe_search" placeholder="{{ __('messages.hr_loan_search_employee_placeholder') }}" autocomplete="off" required>
                            <input type="hidden" name="employe_id" id="employe_id" required>
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_debut">@lang('messages.DU :')</label>
                                <input type="datetime-local" class="form-control" name="date_debut" value="{{ $journal_paie->date_debut }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_fin">@lang('messages.AU :')</label>
                                <input type="datetime-local" class="form-control" name="date_fin" value="{{ $journal_paie->date_fin }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                           <div class="form-group" id="matricule_no">

                            </div>
                        </div> 
                        <div class="col-md-6">
                           <div class="form-group" id="cni">

                            </div>
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                           <div class="form-group" id="somme_salaire_base">

                            </div>
                        </div> 
                        <div class="col-md-6">
                           <div class="form-group" id="numero_compte">

                            </div>
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                           <div class="form-group" id="indemnite_deplacement">

                            </div>
                        </div> 
                        <div class="col-md-4">
                           <div class="form-group" id="indemnite_logement">

                            </div>
                        </div>
                        <div class="col-md-4">
                           <div class="form-group" id="prime_fonction">

                            </div>
                        </div>  
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                           <div class="form-group" id="code_departement">

                            </div>
                        </div> 
                        <div class="col-md-4">
                           <div class="form-group" id="code_service">

                            </div>
                        </div> 
                        <div class="col-md-4">
                           <div class="form-group" id="code_banque">

                            </div>
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="retenue_pret">@lang('messages.Retenue Prêt')</label>
                                <input type="number" class="form-control" name="retenue_pret" placeholder="{{ __('messages.hr_retenue_pret_placeholder') }}" step="any" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="autre_retenue">@lang('messages.Autres Retenues')</label>
                                <input type="number" class="form-control" placeholder="{{ __('messages.Saisir Autre Retenue ') }}" name="autre_retenue" step="any" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="soins_medicaux">@lang('messages.Soins Medicaux')</label>
                                <input type="number" class="form-control" name="soins_medicaux" placeholder="{{ __('messages.Saisir Soins Medicaux') }}" step="any" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="allocation_familiale">@lang('messages.Allocations Familiales')</label>
                                <input type="number" class="form-control" placeholder="{{ __('messages.Saisir Allocations Familiales') }}" step="any" name="allocation_familiale" min="0" required>
                            </div>
                        </div>
                    </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    $(document).ready(function() {
        attachAjaxAutocomplete({
            $input: $('#employe_search'),
            $hidden: $('#employe_id'),
            url: '{{ route("admin.autocomplete.employees") }}',
            minChars: 1,
            onSelect: function(item) {
                fetchEmployeeData(item.id);
            }
        });

        // Function to fetch all employee data via AJAX
        function fetchEmployeeData(idEmploye) {
            // Fetch matricule_no
            $("#matricule_no").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#matricule_no').html('<label>@lang('messages.Matricule No')</label>');
                    $.each(result.data, function (key, value) {
                        $("#matricule_no").append('<input type="text" name="matricule_no" class="form-control" readonly value="' + value.matricule_no + '">');
                    });
                }
            });

            // Fetch CNI
            $("#cni").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#cni').html('<label>@lang('messages.CNI')</label>');
                    $.each(result.data, function (key, value) {
                        $("#cni").append('<input type="text" name="cni" class="form-control" readonly value="' + value.cni + '">');
                    });
                }
            });

            // Fetch code_departement
            $("#code_departement").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#code_departement').html('<label>@lang('messages.Departement')</label>');
                    $.each(result.data, function (key, value) {
                        $("#code_departement").append('<input type="text" name="code_departement" class="form-control" readonly value="' + value.code_departement + '">');
                    });
                }
            });

            // Fetch code_service
            $("#code_service").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#code_service').html('<label>@lang('messages.Code Service')</label>');
                    $.each(result.data, function (key, value) {
                        $("#code_service").append('<input type="text" name="code_service" class="form-control" readonly value="' + value.code_service + '">');
                    });
                }
            });

            // Fetch code_banque
            $("#code_banque").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#code_banque').html('<label>@lang('messages.Code Banque')</label>');
                    $.each(result.data, function (key, value) {
                        $("#code_banque").append('<input type="text" name="code_banque" class="form-control" readonly value="' + value.code_banque + '">');
                    });
                }
            });

            // Fetch somme_salaire_base
            $("#somme_salaire_base").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#somme_salaire_base').html('<label>@lang('messages.Salaire Base')</label>');
                    $.each(result.data, function (key, value) {
                        $("#somme_salaire_base").append('<input type="number" class="form-control" name="somme_salaire_base" min="0" readonly value="' + value.somme_salaire_base + '">');
                    });
                }
            });

            // Fetch numero_compte
            $("#numero_compte").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#numero_compte').html('<label>@lang('messages.Numero Compte')</label>');
                    $.each(result.data, function (key, value) {
                        $("#numero_compte").append('<input type="text" name="numero_compte" class="form-control" min="0" readonly value="' + value.numero_compte + '">');
                    });
                }
            });

            // Fetch indemnite_deplacement
            $("#indemnite_deplacement").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#indemnite_deplacement').html('<label>@lang('messages.Indemnite de deplacement')</label>');
                    $.each(result.data, function (key, value) {
                        $("#indemnite_deplacement").append('<input type="number" name="indemnite_deplacement" class="form-control" min="0" readonly value="' + value.indemnite_deplacement + '">');
                    });
                }
            });

            // Fetch indemnite_logement
            $("#indemnite_logement").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#indemnite_logement').html('<label>@lang('messages.Indemnite de logement')</label>');
                    $.each(result.data, function (key, value) {
                        $("#indemnite_logement").append('<input type="number" name="indemnite_logement" class="form-control" min="0" readonly value="' + value.indemnite_logement + '">');
                    });
                }
            });

            // Fetch prime_fonction
            $("#prime_fonction").html('');
            $.ajax({
                url: "{{route('admin.hr-paiements.fetch')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    $('#prime_fonction').html('<label>@lang('messages.Prime de fonction')</label>');
                    $.each(result.data, function (key, value) {
                        $("#prime_fonction").append('<input type="number" name="prime_fonction" class="form-control" min="0" readonly value="' + value.prime_fonction + '">');
                    });
                }
            });

            // Fetch loan data and auto-fill retenue_pret
            fetchLoanData(idEmploye);
        }

        // Function to fetch loan data and auto-fill retenue_pret
        function fetchLoanData(idEmploye) {
            var date_debut = $('input[name="date_debut"]').val();
            
            if (!date_debut) {
                $('input[name="retenue_pret"]').val(0);
                return;
            }

            $.ajax({
                url: "{{route('admin.hr-paiements.fetch-loan')}}",
                type: "POST",
                data: {
                    employe_id: idEmploye,
                    date_debut: date_debut,
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (result) {
                    if (result.retenue_pret !== undefined) {
                        $('input[name="retenue_pret"]').val(result.retenue_pret || 0);
                    } else {
                        $('input[name="retenue_pret"]').val(0);
                    }
                },
                error: function() {
                    $('input[name="retenue_pret"]').val(0);
                }
            });
        }

        // Auto-fill retenue_pret when date_debut changes
        $('input[name="date_debut"]').on('change', function() {
            var employe_id = $('#employe_id').val();
            if (employe_id) {
                fetchLoanData(employe_id);
            }
        });
    });


</script>

<style>
#employe_autocomplete_list {
    background-color: white;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    margin-top: 2px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#employe_autocomplete_list .list-group-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

#employe_autocomplete_list .list-group-item:last-child {
    border-bottom: none;
}
</style>
@endsection

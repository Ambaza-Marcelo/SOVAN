@extends('backend.layouts.master')

@section('title')
@lang('Create Loan') - @lang('messages.admin_panel')
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Create Loan')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-loans.index') }}">@lang('Loans List')</a></li>
                    <li><span>@lang('Create')</span></li>
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
                    <h4 class="header-title">Nouvelle Demande d'Avance sur Salaire</h4>
                    <p class="text-muted">Un employé ne peut pas prendre une nouvelle avance tant qu'il a une dette en attente ou partiellement payée.</p>
                    @include('backend.layouts.partials.messages')
                    <form action="{{ route('admin.hr-loans.store') }}" method="POST" id="loanForm">
                        @csrf
                        <div id="alert-employe-dette" class="alert alert-danger d-none" role="alert">
                            <strong>Dette en cours :</strong> cet employé a une avance en attente ou partiellement payée. Il doit solder toutes les tranches avant de pouvoir demander une nouvelle avance.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date" class="col-form-label">Date <strong style="color: red;">*</strong></label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" style="position: relative;">
                                    <label for="employe_search">@lang('Employé')</label>
                                    <input type="text" class="form-control" id="employe_search" placeholder="Rechercher un employé..." autocomplete="off" required>
                                    <input type="hidden" name="employe_id" id="employe_id" required>
                                    <ul id="employe_autocomplete_list" class="list-group" style="position: absolute; z-index: 1000; max-height: 200px; overflow-y: auto; display: none; width: 100%;"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_loan" class="col-form-label">Montant du Prêt <strong style="color: red;">*</strong></label>
                                    <input type="number" name="amount_loan" class="form-control" id="amount_loan" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nbre_tranche" class="col-form-label">Nombre de Tranches <strong style="color: red;">*</strong></label>
                                    <input type="number" name="nbre_tranche" class="form-control" id="nbre_tranche" min="1" max="3" value="3" required>
                                    <small class="form-text text-muted">Maximum 3 mois</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_1m" class="col-form-label">Montant 1er Mois</label>
                                    <input type="number" name="amount_to_pay_1m" class="form-control" id="amount_to_pay_1m" step="0.01" min="0" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_2m" class="col-form-label">Montant 2ème Mois</label>
                                    <input type="number" name="amount_to_pay_2m" class="form-control" id="amount_to_pay_2m" step="0.01" min="0" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_to_pay_3m" class="col-form-label">Montant 3ème Mois</label>
                                    <input type="number" name="amount_to_pay_3m" class="form-control" id="amount_to_pay_3m" step="0.01" min="0" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="col-form-label">Description / Raison du Prêt</label>
                                    <textarea name="description" class="form-control" id="description" rows="3" placeholder="Raison de la demande d'avance...">je dois faire face à une dépense imprévue pour </textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                            <a href="{{ route('admin.hr-loans.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Employee autocomplete data (employés avec dette en cours : pas de nouvelle avance possible)
    var employeIdsWithOutstandingLoan = @json($employeIdsWithOutstandingLoan ?? []);
    var employes = [
        @foreach ($employes as $employe)
        {
            id: {{ $employe->id }},
            display: {!! json_encode($employe->matricule_no . ' - ' . $employe->firstname . ' ' . $employe->lastname) !!},
            name: {!! json_encode($employe->firstname . ' ' . $employe->lastname) !!},
            matricule: {!! json_encode($employe->matricule_no) !!},
            hasOutstandingLoan: employeIdsWithOutstandingLoan.indexOf({{ $employe->id }}) !== -1
        },
        @endforeach
    ];

    // Employee autocomplete functionality
    $('#employe_search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $list = $('#employe_autocomplete_list');
        var $parent = $(this).closest('.form-group');
        
        if (searchTerm.length === 0) {
            $list.hide().empty();
            $('#employe_id').val('');
            $('#alert-employe-dette').addClass('d-none');
            $('#loanForm button[type="submit"]').prop('disabled', false);
            return;
        }
        
        var filtered = employes.filter(function(employe) {
            var fullName = (employe.name || '').toLowerCase();
            var matricule = (employe.matricule || '').toLowerCase();
            var display = (employe.display || '').toLowerCase();
            
            return fullName.includes(searchTerm) ||
                   matricule.includes(searchTerm) ||
                   display.includes(searchTerm) ||
                   fullName.split(' ').some(function(word) {
                       return word.startsWith(searchTerm);
                   });
        });
        
        $list.empty();
        
        if (filtered.length > 0) {
            filtered.forEach(function(employe) {
                var label = employe.display;
                if (employe.hasOutstandingLoan) {
                    label += ' <span class="badge badge-warning">Dette en cours - pas de nouvelle avance</span>';
                }
                var $item = $('<li class="list-group-item" style="cursor: pointer; padding: 8px 12px;"></li>')
                    .html('<strong>' + label + '</strong>')
                    .data('employe', employe)
                    .on('click', function() {
                        var selected = $(this).data('employe');
                        if (selected.hasOutstandingLoan) {
                            $('#alert-employe-dette').removeClass('d-none');
                            $('#employe_id').val('');
                            $('#loanForm button[type="submit"]').prop('disabled', true);
                        } else {
                            $('#alert-employe-dette').addClass('d-none');
                            $('#employe_id').val(selected.id);
                            $('#loanForm button[type="submit"]').prop('disabled', false);
                        }
                        $('#employe_search').val(selected.display);
                        $list.hide().empty();
                    })
                    .on('mouseenter', function() {
                        $(this).css('background-color', '#f8f9fa');
                    })
                    .on('mouseleave', function() {
                        $(this).css('background-color', 'white');
                    });
                if (employe.hasOutstandingLoan) {
                    $item.css('opacity', '0.9');
                }
                $list.append($item);
            });
            
            $list.show();
        } else {
            $list.hide();
        }
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#employe_search, #employe_autocomplete_list').length) {
            $('#employe_autocomplete_list').hide();
        }
    });

    // Auto-calculate amounts based on nbre_tranche and amount_loan
    function calculateAmounts() {
        var amountLoan = parseFloat($('#amount_loan').val()) || 0;
        var nbreTranche = parseInt($('#nbre_tranche').val()) || 3;
        
        if (amountLoan > 0 && nbreTranche > 0) {
            var amountPerMonth = amountLoan / nbreTranche;
            
            if (nbreTranche == 3) {
                $('#amount_to_pay_1m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_2m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_3m').val(amountPerMonth.toFixed(2));
            } else if (nbreTranche == 2) {
                $('#amount_to_pay_1m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_2m').val(amountPerMonth.toFixed(2));
                $('#amount_to_pay_3m').val(0);
            } else { // nbreTranche == 1
                $('#amount_to_pay_1m').val(amountLoan.toFixed(2));
                $('#amount_to_pay_2m').val(0);
                $('#amount_to_pay_3m').val(0);
            }
        } else {
            $('#amount_to_pay_1m').val(0);
            $('#amount_to_pay_2m').val(0);
            $('#amount_to_pay_3m').val(0);
        }
    }

    $('#amount_loan, #nbre_tranche').on('input change', function() {
        calculateAmounts();
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

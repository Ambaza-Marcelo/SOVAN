@extends('backend.layouts.master')

@section('title')
@lang('View Loan') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .info-value {
        color: #212529;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 15px;
        border-left: 3px solid #007bff;
    }
    .nav-tabs .nav-link.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        padding: 20px;
        background-color: white;
    }
    .status-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 4px;
        font-weight: 500;
    }
    .status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .status-validated {
        background-color: #17a2b8;
        color: white;
    }
    .status-confirmed {
        background-color: #28a745;
        color: white;
    }
    .status-approved {
        background-color: #6c757d;
        color: white;
    }
    .payment-status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .payment-status-first {
        background-color: #17a2b8;
        color: white;
    }
    .payment-status-second {
        background-color: #007bff;
        color: white;
    }
    .payment-status-completed {
        background-color: #28a745;
        color: white;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('View Loan')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-loans.index') }}">@lang('Loans List')</a></li>
                    <li><span>@lang('View')</span></li>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Détails de la Demande d'Avance sur Salaire</h4>
                        <div>
                            @if ($loan->status == 3 && Auth::guard('admin')->user()->can('account_cash_ouflow_request.create'))
                                <a href="{{ route('admin.cash-out-flow-request.loan-create', $loan->id) }}" class="btn btn-primary">
                                    <i class="fa fa-money-bill"></i> Demande de sortie de fonds
                                </a>
                            @endif
                            @if ($loan->status != 4 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                <a href="{{ route('admin.hr-loans.edit', $loan->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> @lang('messages.edit')
                                </a>
                            @endif
                            <a href="{{ route('admin.hr-loans.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> @lang('Back to List')
                            </a>
                        </div>
                    </div>
                    <p class="text-muted small">Rappel : un employé ne peut pas prendre une nouvelle avance tant qu'il a une dette en attente ou partiellement payée (toutes les tranches doivent être soldées).</p>

                    <!-- Status and Actions -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">Statut:</label>
                                <div class="info-value">
                                    @if($loan->status == 0)
                                        <span class="status-badge status-pending">En Attente</span>
                                    @elseif($loan->status == 1)
                                        <span class="status-badge status-validated">Validé</span>
                                    @elseif($loan->status == 2)
                                        <span class="status-badge status-confirmed">Confirmé</span>
                                    @elseif($loan->status == 3)
                                        <span class="status-badge status-approved">Approuvé</span>
                                    @elseif($loan->status == 4)
                                        <span class="status-badge status-approved">Approuvé et Sorti</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">État de Paiement:</label>
                                <div class="info-value">
                                    @php
                                        $flag1 = $loan->flag1 ?? '0';
                                        $flag2 = $loan->flag2 ?? '0';
                                        // Convert to string for comparison (they are stored as strings)
                                        $flag1 = (string)$flag1;
                                        $flag2 = (string)$flag2;
                                    @endphp
                                    @if($flag1 == '0' && $flag2 == '0')
                                        <span class="status-badge payment-status-pending">En attente de paiement</span>
                                    @elseif($flag1 == '1' && $flag2 == '0')
                                        <span class="status-badge payment-status-first">Le premier mois est déjà payé</span>
                                    @elseif($flag1 == '1' && $flag2 == '1')
                                        <span class="status-badge payment-status-second">Le second mois est déjà payé</span>
                                    @elseif($flag1 == '1' && $flag2 == '2')
                                        <span class="status-badge payment-status-completed">L'avance sur salaire est totalement payée</span>
                                    @else
                                        <span class="status-badge payment-status-pending">En attente de paiement</span>
                                    @endif
                                </div>
                                @if (Auth::guard('admin')->user()->can('hr_loan.edit') && !$loan->isFullyPaid())
                                    <div class="mt-2">
                                        {{-- Boutons pour marquer les tranches comme payées --}}
                                        @if($loan->amount_to_pay_1m > 0 && $flag1 === '0')
                                            <form action="{{ route('admin.hr-loans.mark-first-tranche', $loan->id) }}" method="POST" style="display:inline-block; margin-bottom: 5px;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info btn-sm">1ère tranche payée</button>
                                            </form>
                                        @endif

                                        @if($loan->amount_to_pay_2m > 0 && $flag1 === '1' && $flag2 === '0')
                                            <form action="{{ route('admin.hr-loans.mark-second-tranche', $loan->id) }}" method="POST" style="display:inline-block; margin-bottom: 5px;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm">2ème tranche payée</button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.hr-loans.mark-all-tranches', $loan->id) }}" method="POST" style="display:inline-block; margin-bottom: 5px;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm">Toutes les tranches payées</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="info-label">Actions:</label>
                                <div class="info-value">
                                    @if($loan->status == 0 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.validate', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-info btn-sm">Valider</button>
                                        </form>
                                    @endif
                                    @if($loan->status == 1 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.confirm', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Confirmer</button>
                                        </form>
                                    @endif
                                    @if($loan->status == 2 && Auth::guard('admin')->user()->can('hr_loan.edit'))
                                        <form action="{{ route('admin.hr-loans.approve', $loan->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">Approuver</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                            <div>
                                <a href="{{ route('admin.hr-loans.printDocument',$loan->id) }}" class="btnprn"><img src="{{ asset('img/ISSh.gif') }}" width="60" title="Télécharger d'abord le document et puis imprimer"></a> 
                            </div>
                            <br>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="employee-tab" data-toggle="tab" href="#employee" role="tab">
                                <i class="fa fa-user"></i> Informations Employé
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="loan-tab" data-toggle="tab" href="#loan" role="tab">
                                <i class="fa fa-money-bill"></i> Détails du Prêt
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!-- Employee Information Tab -->
                        <div class="tab-pane fade show active" id="employee" role="tabpanel">
                            @if($loan->employe)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Nom et Prénom:</label>
                                        <div class="info-value">{{ $loan->employe->firstname }} {{ $loan->employe->lastname }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Numéro Matricule:</label>
                                        <div class="info-value">{{ $loan->employe->matricule_no }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Téléphone:</label>
                                        <div class="info-value">{{ $loan->employe->phone_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Email:</label>
                                        <div class="info-value">{{ $loan->employe->mail }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">CNI:</label>
                                        <div class="info-value">{{ $loan->employe->cni }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date de Naissance:</label>
                                        <div class="info-value">
                                            @if($loan->employe->birthdate)
                                                {{ \Carbon\Carbon::parse($loan->employe->birthdate)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Département:</label>
                                        <div class="info-value">
                                            @if($loan->employe->departement)
                                                {{ $loan->employe->departement->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Service:</label>
                                        <div class="info-value">
                                            @if($loan->employe->service)
                                                {{ $loan->employe->service->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Fonction:</label>
                                        <div class="info-value">
                                            @if($loan->employe->fonction)
                                                {{ $loan->employe->fonction->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Grade:</label>
                                        <div class="info-value">
                                            @if($loan->employe->grade)
                                                {{ $loan->employe->grade->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Salaire de Base:</label>
                                        <div class="info-value">{{ number_format($loan->employe->somme_salaire_base, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Résidence Actuelle:</label>
                                        <div class="info-value">
                                            {{ $loan->employe->quartier_residence_actuel }}, 
                                            {{ $loan->employe->zone_residence_actuel }}, 
                                            {{ $loan->employe->commune_residence_actuel }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">Aucune information employé disponible.</div>
                            @endif
                        </div>

                        <!-- Loan Details Tab -->
                        <div class="tab-pane fade" id="loan" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date de la Demande:</label>
                                        <div class="info-value">
                                            @if($loan->date)
                                                {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Montant du Prêt:</label>
                                        <div class="info-value">{{ number_format($loan->amount_loan, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Nombre de Tranches:</label>
                                        <div class="info-value">{{ $loan->nbre_tranche }} mois</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Statut:</label>
                                        <div class="info-value">
                                            @if($loan->status == 0)
                                                <span class="status-badge status-pending">En Attente</span>
                                            @elseif($loan->status == 1)
                                                <span class="status-badge status-validated">Validé</span>
                                            @elseif($loan->status == 2)
                                                <span class="status-badge status-confirmed">Confirmé</span>
                                            @elseif($loan->status == 3)
                                                <span class="status-badge status-approved">Approuvé</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">État de Paiement:</label>
                                        <div class="info-value">
                                            @php
                                                $flag1 = $loan->flag1 ?? '0';
                                                $flag2 = $loan->flag2 ?? '0';
                                                // Convert to string for comparison (they are stored as strings)
                                                $flag1 = (string)$flag1;
                                                $flag2 = (string)$flag2;
                                            @endphp
                                            @if($flag1 == '0' && $flag2 == '0')
                                                <span class="status-badge payment-status-pending">En attente de paiement</span>
                                            @elseif($flag1 == '1' && $flag2 == '0')
                                                <span class="status-badge payment-status-first">Le premier mois est déjà payé</span>
                                            @elseif($flag1 == '1' && $flag2 == '1')
                                                <span class="status-badge payment-status-second">Le second mois est déjà payé</span>
                                            @elseif($flag1 == '1' && $flag2 == '2')
                                                <span class="status-badge payment-status-completed">L'avance sur salaire est totalement payée</span>
                                            @else
                                                <span class="status-badge payment-status-pending">En attente de paiement</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Montant 1er Mois:</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_1m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Montant 2ème Mois:</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_2m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Montant 3ème Mois:</label>
                                        <div class="info-value">{{ number_format($loan->amount_to_pay_3m, 2, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">Description / Raison:</label>
                                        <div class="info-value">{{ $loan->description ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Créé par:</label>
                                        <div class="info-value">{{ $loan->created_by ?: 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Validé par:</label>
                                        <div class="info-value">{{ $loan->validated_by ?: 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="info-label">Confirmé par:</label>
                                        <div class="info-value">{{ $loan->confirmed_by ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">Approuvé par:</label>
                                        <div class="info-value">{{ $loan->approuved_by ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date de Création:</label>
                                        <div class="info-value">{{ $loan->created_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Dernière Mise à Jour:</label>
                                        <div class="info-value">{{ $loan->updated_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


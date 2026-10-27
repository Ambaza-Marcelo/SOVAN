@extends('backend.layouts.master')

@section('title')
Rectification des sorties SAU — @lang('messages.admin_panel')
@endsection

@section('admin-content')
@php
    $fmt = function ($n) {
        return number_format((float) $n, 2, ',', ' ') . ' BIF';
    };
    $accountLabel = function ($id) use ($accountLabels) {
        return $accountLabels[$id] ?? ('#' . $id);
    };
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Rectification des sorties SAU</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>Rectification sorties SAU</span></li>
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
        <div class="col-12 mt-4">
            @include('backend.layouts.partials.messages')

            <div class="card mb-3">
                <div class="card-body">
                    <p class="mb-2">
                        Les sorties directes approuvées (SAU ou ancien SN) dont le débit a été imputé sur un compte
                        d'ajustement global sont réimputées <strong>en place</strong> vers le compte de la fiche de chaque article.
                        Aucune extourne ni nouvelle pièce : le montant total et le crédit stock restent inchangés.
                    </p>
                    <p class="small text-muted mb-3">
                        Comptes globaux ciblés :
                        @forelse($genericAccountIds as $id)
                            <span class="badge badge-secondary">{{ $accountLabel($id) }}</span>
                        @empty
                            <span class="badge badge-warning">aucun compte d'ajustement paramétré</span>
                        @endforelse
                    </p>
                    <form method="GET" action="{{ route('admin.accounting-sau-rectification.index') }}" class="form-inline">
                        <label class="mr-2">N° de bon</label>
                        <input type="text" name="document_no" class="form-control mr-3" value="{{ $documentNo }}" placeholder="Tous les bons">
                        <button type="submit" class="btn btn-secondary">Analyser</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">
                        Pièces détectées
                        <span class="badge badge-primary">{{ count($plans) }}</span>
                        — prêtes : <span class="badge badge-success">{{ $readyCount }}</span>
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Module</th>
                                    <th>Bon</th>
                                    <th>Date</th>
                                    <th>Compte global actuel</th>
                                    <th>Montant</th>
                                    <th>Nouvelle imputation</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    @foreach($plan['lines'] as $line)
                                        <tr class="{{ $plan['status'] === 'ready' ? '' : 'table-warning' }}">
                                            <td>{{ $plan['module_label'] }}</td>
                                            <td>{{ $plan['document_no'] }}</td>
                                            <td>{{ $line['date'] }}</td>
                                            <td>{{ $accountLabel($line['generic_account_id']) }}</td>
                                            <td class="text-right">{{ $fmt($line['amount']) }}</td>
                                            <td>
                                                @foreach($line['allocations'] as $allocation)
                                                    <div>
                                                        <strong>{{ $accountLabel($allocation['account_id']) }}</strong> :
                                                        {{ $fmt($allocation['amount']) }}
                                                        <span class="small text-muted">({{ implode(', ', $allocation['labels']) }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($plan['status'] === 'ready')
                                                    <span class="badge badge-success">Prête</span>
                                                @elseif($plan['status'] === 'locked')
                                                    <span class="badge badge-dark">Période verrouillée</span>
                                                @else
                                                    <span class="badge badge-danger">Bloquée</span>
                                                @endif
                                                @foreach($plan['reasons'] as $reason)
                                                    <div class="small text-danger">{{ $reason }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune écriture à rectifier.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">
                        Une pièce « bloquée » contient au moins un article sans compte par défaut : elle n'est pas modifiée
                        tant que la fiche n'est pas complétée.
                    </p>
                </div>
            </div>

            @if($canApply && $readyCount > 0)
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Appliquer la rectification</h4>
                    <form method="POST" action="{{ route('admin.accounting-sau-rectification.apply') }}"
                          onsubmit="return confirm('Confirmer la réimputation de {{ $readyCount }} pièce(s) ?');">
                        @csrf
                        <input type="hidden" name="document_no" value="{{ $documentNo }}">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="confirm" name="confirm" value="1" required>
                            <label class="custom-control-label" for="confirm">
                                Je confirme la réimputation en place des lignes listées (montants inchangés, grand livre recalculé).
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Rectifier {{ $readyCount }} pièce(s)</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

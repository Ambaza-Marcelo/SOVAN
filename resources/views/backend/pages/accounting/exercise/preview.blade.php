@extends('backend.layouts.master')

@section('title')
Prévisualisation clôture {{ $fiscalYear }} — @lang('messages.admin_panel')
@endsection

@section('admin-content')
@php
    $fmt = function ($n) {
        return number_format((float) $n, 0, ',', ' ') . ' BIF';
    };
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Clôture — amortissements {{ $fiscalYear }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-exercises.index') }}">Clôture</a></li>
                    <li><span>Prévisualisation</span></li>
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
                    <form method="GET" action="{{ route('admin.accounting-exercises.preview') }}" class="form-inline">
                        <label class="mr-2">Exercice</label>
                        <input type="number" name="fiscal_year" class="form-control mr-3" value="{{ $fiscalYear }}" min="2000" max="2100">
                        <label class="mr-2">Date de passage</label>
                        <input type="date" name="posting_date" class="form-control mr-3" value="{{ $postingDate->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-secondary">Actualiser</button>
                    </form>
                    @if($exercise && $exercise->isLocked())
                        <div class="alert alert-dark mt-3 mb-0">
                            Exercice verrouillé — aucune écriture possible.
                            <a href="{{ route('admin.accounting-exercises.show', $exercise->id) }}">Voir le détail</a>
                        </div>
                    @elseif($exercise && $exercise->hasDepreciationDone() && $pending->count() === 0)
                        <div class="alert alert-success mt-3 mb-0">
                            Amortissements déjà passés ({{ $exercise->document_no }}).
                            <a href="{{ route('admin.accounting-exercises.preview-result', ['fiscal_year' => $fiscalYear]) }}">Passer à l'étape résultat</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">
                        Dotations à passer
                        <span class="badge badge-primary">{{ $pending->count() }}</span>
                        — Total : <strong>{{ $fmt($totalPending) }}</strong>
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Article</th>
                                    <th>Code</th>
                                    <th>Débit (charge)</th>
                                    <th>Crédit (amort.)</th>
                                    <th>Montant</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($candidates as $row)
                                    <tr class="{{ !empty($row['already_posted']) ? 'table-success' : '' }}">
                                        <td>{{ $row['material_name'] }}</td>
                                        <td>{{ $row['material_code'] }}</td>
                                        <td>{{ $row['debit_account_label'] ?? $row['debit_account_id'] }}</td>
                                        <td>{{ $row['credit_account_label'] ?? $row['credit_account_id'] }}</td>
                                        <td>{{ $fmt($row['amount']) }}</td>
                                        <td>
                                            @if(!empty($row['already_posted']))
                                                <span class="badge badge-success">Déjà posté</span>
                                            @else
                                                <span class="badge badge-warning">À passer</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Aucune dotation pour cet exercice.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">
                        Écriture générée : Débit compte de charge (ex. 6811) / Crédit compte d'amortissement (ex. 2805),
                        selon le paramétrage de chaque article Material amortissable.
                    </p>
                </div>
            </div>

            @if($pending->count() > 0 && !($exercise && $exercise->isLocked()) && !($exercise && $exercise->hasResultDone()))
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Confirmer la clôture des amortissements</h4>
                    <form method="POST" action="{{ route('admin.accounting-exercises.close') }}"
                          onsubmit="return confirm('Confirmer le passage comptable de {{ $pending->count() }} dotation(s) pour {{ $fmt($totalPending) }} ?');">
                        @csrf
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <input type="hidden" name="posting_date" value="{{ $postingDate->format('Y-m-d') }}">
                        <div class="form-group">
                            <label for="notes">Notes (optionnel)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="confirm" name="confirm" value="1" required>
                            <label class="custom-control-label" for="confirm">
                                Je confirme le passage atomique des écritures 681 → 28 pour l'exercice {{ $fiscalYear }}.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Exécuter la clôture des amortissements</button>
                        <a href="{{ route('admin.accounting-exercises.index') }}" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('backend.layouts.master')

@section('title')
Clôture résultat {{ $fiscalYear }} — @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">Étape 2 — Clôture résultat {{ $fiscalYear }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-exercises.index') }}">Clôture</a></li>
                    <li><span>Résultat</span></li>
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

            @if($settingsError)
                <div class="alert alert-danger">{{ $settingsError }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.accounting-exercises.preview-result') }}" class="form-inline">
                        <label class="mr-2">Exercice</label>
                        <input type="number" name="fiscal_year" class="form-control mr-2" value="{{ $fiscalYear }}" style="width:100px">
                        <label class="mr-2">Date écriture</label>
                        <input type="date" name="posting_date" class="form-control mr-2" value="{{ $postingDate->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-secondary">Actualiser</button>
                    </form>
                    <hr>
                    <div class="row">
                        <div class="col-md-3"><strong>Charges (6)</strong><div>{{ $fmt($preview['charges_total'] ?? 0) }}</div></div>
                        <div class="col-md-3"><strong>Produits (7)</strong><div>{{ $fmt($preview['products_total'] ?? 0) }}</div></div>
                        <div class="col-md-3">
                            <strong>Résultat net</strong>
                            <div>{{ $fmt($preview['net'] ?? 0) }}
                                — {{ ($preview['is_profit'] ?? true) ? 'bénéfice' : 'perte' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <strong>Compte résultat</strong>
                            <div>
                                @if(!empty($preview['result_account']))
                                    {{ $preview['result_account']->account_number }} — {{ $preview['result_account']->account_name }}
                                @else
                                    Non paramétré
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">Comptes à soldes (classe 6 et 7)</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Type</th>
                                    <th>Compte</th>
                                    <th>Libellé</th>
                                    <th>Solde actuel</th>
                                    <th>Montant clôture</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($preview['lines'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['side'] === 'charge' ? 'Charge' : 'Produit' }}</td>
                                        <td>{{ $row['account_number'] }}</td>
                                        <td>{{ $row['account_name'] }}</td>
                                        <td>{{ $fmt($row['balance']) }}</td>
                                        <td>{{ $fmt($row['amount']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center">Aucun solde à clôturer.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">
                        Chaque compte est contrepassé vers le compte de résultat (12). Les classes 6/7 doivent ensuite être à zéro.
                    </p>
                </div>
            </div>

            @if($settingsOk && ($preview['lines'] ?? collect())->count() > 0 && !($exercise && $exercise->hasResultDone()) && !($exercise && $exercise->isLocked()))
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Confirmer la clôture du résultat</h4>
                    <form method="POST" action="{{ route('admin.accounting-exercises.close-result') }}"
                          onsubmit="return confirm('Clôturer les charges et produits vers le compte de résultat ?');">
                        @csrf
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <input type="hidden" name="posting_date" value="{{ $postingDate->format('Y-m-d') }}">
                        <div class="form-group">
                            <label>Notes (optionnel)</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="confirm" id="confirm" value="1" required>
                            <label class="form-check-label" for="confirm">
                                Je confirme la clôture des soldes de classes 6 et 7 vers le compte de résultat.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Exécuter l'étape 2</button>
                        <a href="{{ route('admin.accounting-exercises.index') }}" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

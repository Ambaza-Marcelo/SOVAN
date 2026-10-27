@extends('backend.layouts.master')

@section('title')
Report à nouveau {{ $fiscalYear }} — @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">Étape 3 — Report à nouveau {{ $fiscalYear }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-exercises.index') }}">Clôture</a></li>
                    <li><span>RAN</span></li>
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
                    <form method="GET" action="{{ route('admin.accounting-exercises.preview-ran') }}" class="form-inline mb-3">
                        <label class="mr-2">Exercice</label>
                        <input type="number" name="fiscal_year" class="form-control mr-2" value="{{ $fiscalYear }}" style="width:100px">
                        <label class="mr-2">Date écriture</label>
                        <input type="date" name="posting_date" class="form-control mr-2" value="{{ $postingDate->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-secondary">Actualiser</button>
                    </form>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">Compte de résultat (12)</div>
                            <strong>
                                @if($preview['result_account'])
                                    {{ $preview['result_account']->account_number }} — {{ $preview['result_account']->account_name }}
                                @else
                                    —
                                @endif
                            </strong>
                            <div>Solde : {{ $fmt($preview['result_balance'] ?? 0) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Compte RAN</div>
                            <strong>
                                @if($preview['ran_account'])
                                    {{ $preview['ran_account']->account_number }} — {{ $preview['ran_account']->account_name }}
                                @else
                                    —
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Montant à reporter</div>
                            <div style="font-size:1.4rem;font-weight:700;">{{ $fmt($preview['amount'] ?? 0) }}</div>
                            <div>{{ ($preview['is_profit'] ?? true) ? 'Bénéfice → crédit RAN' : 'Perte → débit RAN perte' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$settingsError && !($exercise && $exercise->hasRanDone()) && !($exercise && $exercise->isLocked()) && ($exercise && $exercise->hasResultDone()))
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Confirmer le report à nouveau</h4>
                    <form method="POST" action="{{ route('admin.accounting-exercises.close-ran') }}"
                          onsubmit="return confirm('Reporter le résultat net vers le compte de report à nouveau ?');">
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
                                Je confirme le transfert du résultat vers le report à nouveau.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Exécuter l'étape 3</button>
                        <a href="{{ route('admin.accounting-exercises.index') }}" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
            @elseif(!($exercise && $exercise->hasResultDone()))
                <div class="alert alert-info">
                    Effectuez d'abord l'étape 2 (clôture du résultat).
                    <a href="{{ route('admin.accounting-exercises.preview-result', ['fiscal_year' => $fiscalYear]) }}">Y aller</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

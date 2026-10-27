@extends('backend.layouts.master')

@section('title')
Clôture comptable — @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    :root {
        --me-ink: #1e1d24;
        --me-slate: #3f424c;
        --me-charcoal: #2a2930;
        --me-amber: #eba91b;
        --me-amber-hot: #f0b429;
        --me-sand: #f7f5f0;
        --me-muted: #8a8b96;
    }

    .closing-step-card {
        border-radius: 8px;
        color: #fff;
        min-height: 150px;
        border-top: 3px solid var(--me-amber);
        box-shadow: 0 4px 14px rgba(30, 29, 36, 0.18);
    }
    .closing-step-card.step-1 { background: linear-gradient(145deg, #000 0%, var(--me-ink) 70%, #2a2832 100%); }
    .closing-step-card.step-2 { background: linear-gradient(145deg, var(--me-ink) 0%, var(--me-charcoal) 55%, #3a3842 100%); }
    .closing-step-card.step-3 { background: linear-gradient(145deg, #2a2418 0%, #3d3424 55%, #4a4030 100%); }
    .closing-step-card.step-4 { background: linear-gradient(145deg, #0a0a0c 0%, #151518 70%, var(--me-slate) 100%); }

    .closing-step-card .step-label {
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--me-amber);
        font-weight: 600;
        opacity: 0.95;
    }
    .closing-step-card .step-amount {
        font-size: 1.2rem;
        font-weight: 700;
        color: #fff;
    }
    .closing-step-card .step-meta { color: rgba(255,255,255,0.78); }

    .closing-btn-gold {
        background: var(--me-amber) !important;
        border-color: var(--me-amber) !important;
        color: #1e1d24 !important;
        font-weight: 600;
    }
    .closing-btn-gold:hover {
        background: var(--me-amber-hot) !important;
        border-color: var(--me-amber-hot) !important;
        color: #000 !important;
    }
    .closing-btn-outline-gold {
        background: transparent !important;
        border: 1px solid var(--me-amber) !important;
        color: var(--me-amber) !important;
        font-weight: 600;
    }
    .closing-btn-outline-gold:hover {
        background: var(--me-amber) !important;
        color: #1e1d24 !important;
    }

    .closing-history thead {
        background: var(--me-ink) !important;
        color: var(--me-amber) !important;
    }
    .closing-history thead th {
        border-color: #2e2d36 !important;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .closing-badge-wait {
        background: #4a4b55 !important;
        color: #eee !important;
    }
    .closing-badge-done {
        background: var(--me-amber) !important;
        color: #1e1d24 !important;
    }
</style>
@endsection

@section('admin-content')
@php
    $fmt = function ($n) {
        return number_format((float) $n, 0, ',', ' ') . ' BIF';
    };
    $ex = $exercise ?? null;
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Clôture comptable</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>Clôture</span></li>
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

            @if(!empty($settingsError))
                <div class="alert alert-warning">
                    {{ $settingsError }}
                    <a href="{{ route('admin.accounting-settings.index') }}">Paramètres comptes</a>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title mb-0">Exercice actif {{ $activeYear }}
                            @if($ex)
                                <span class="badge {{ $ex->statusBadgeClass() }}">{{ $ex->statusLabel() }}</span>
                            @endif
                        </h4>
                        @can('account_exercise.create')
                            <a href="{{ route('admin.accounting-exercises.create') }}" class="btn btn-sm closing-btn-gold">Créer un exercice</a>
                        @endcan
                    </div>

                    <div class="row mb-4">
                        {{-- Étape 1 --}}
                        <div class="col-md-3 mb-3">
                            <div class="p-3 h-100 closing-step-card step-1">
                                <div class="step-label mb-1">1. Amortissements</div>
                                <div class="step-amount">{{ $fmt($pendingTotal) }}</div>
                                <div class="small step-meta mb-2">{{ $pendingCount }} ligne(s) en attente</div>
                                @if($ex && $ex->isLocked())
                                    <span class="badge badge-light">Verrouillé</span>
                                @elseif($pendingCount > 0)
                                    <a href="{{ route('admin.accounting-exercises.preview', ['fiscal_year' => $activeYear]) }}"
                                       class="btn btn-sm closing-btn-gold">Prévisualiser</a>
                                @elseif($ex && $ex->hasDepreciationDone())
                                    <span class="badge closing-badge-done">Fait</span>
                                @else
                                    <a href="{{ route('admin.accounting-exercises.preview', ['fiscal_year' => $activeYear]) }}"
                                       class="btn btn-sm closing-btn-outline-gold">Vérifier</a>
                                @endif
                            </div>
                        </div>

                        {{-- Étape 2 --}}
                        <div class="col-md-3 mb-3">
                            <div class="p-3 h-100 closing-step-card step-2">
                                <div class="step-label mb-1">2. Résultat (6/7 → 12)</div>
                                @if($resultPreview)
                                    <div class="step-amount">{{ $fmt($resultPreview['net'] ?? 0) }}</div>
                                    <div class="small step-meta mb-2">
                                        {{ ($resultPreview['is_profit'] ?? true) ? 'Bénéfice estimé' : 'Perte estimée' }}
                                        — {{ ($resultPreview['lines'] ?? collect())->count() }} compte(s)
                                    </div>
                                @else
                                    <div class="small step-meta mb-2">Paramétrez les comptes 12 / 120</div>
                                @endif
                                @if($ex && $ex->isLocked())
                                    <span class="badge badge-light">Verrouillé</span>
                                @elseif($ex && $ex->hasResultDone())
                                    <span class="badge closing-badge-done">Fait</span>
                                @else
                                    <a href="{{ route('admin.accounting-exercises.preview-result', ['fiscal_year' => $activeYear]) }}"
                                       class="btn btn-sm closing-btn-gold">Prévisualiser</a>
                                @endif
                            </div>
                        </div>

                        {{-- Étape 3 --}}
                        <div class="col-md-3 mb-3">
                            <div class="p-3 h-100 closing-step-card step-3">
                                <div class="step-label mb-1">3. Report à nouveau</div>
                                @if($ex && $ex->hasRanDone())
                                    <div class="step-amount">{{ $fmt($ex->ran_total) }}</div>
                                    <div class="small step-meta mb-2">12 → 120/129</div>
                                    <span class="badge closing-badge-done">Fait</span>
                                @elseif($ex && $ex->hasResultDone())
                                    <div class="small step-meta mb-2">Transfert du résultat net</div>
                                    <a href="{{ route('admin.accounting-exercises.preview-ran', ['fiscal_year' => $activeYear]) }}"
                                       class="btn btn-sm closing-btn-gold">Prévisualiser</a>
                                @else
                                    <div class="small step-meta mb-2">Après l'étape résultat</div>
                                    <span class="badge closing-badge-wait">En attente</span>
                                @endif
                            </div>
                        </div>

                        {{-- Étape 4 --}}
                        <div class="col-md-3 mb-3">
                            <div class="p-3 h-100 closing-step-card step-4">
                                <div class="step-label mb-1">4. Verrouillage</div>
                                @if($ex && $ex->isLocked())
                                    <div class="small step-meta mb-2">Plus d'écritures sur {{ $activeYear }}</div>
                                    <span class="badge badge-light">Verrouillé</span>
                                    @if($ex->next_fiscal_year)
                                        <div class="small mt-1 step-meta">Nouvel exercice : {{ $ex->next_fiscal_year }}</div>
                                    @endif
                                @elseif($ex && $ex->hasRanDone())
                                    <div class="small step-meta mb-2">Bloque les écritures de l'exercice</div>
                                    <form method="POST" action="{{ route('admin.accounting-exercises.lock') }}"
                                          onsubmit="return confirm('Verrouiller définitivement l\'exercice {{ $activeYear }} ?');">
                                        @csrf
                                        <input type="hidden" name="fiscal_year" value="{{ $activeYear }}">
                                        <input type="hidden" name="confirm" value="1">
                                        <label class="small d-block mb-2 step-meta">
                                            <input type="checkbox" name="advance_year" value="1" checked>
                                            Passer à {{ $activeYear + 1 }}
                                        </label>
                                        <button type="submit" class="btn btn-sm closing-btn-gold">Verrouiller</button>
                                    </form>
                                @else
                                    <div class="small step-meta mb-2">Après le report à nouveau</div>
                                    <span class="badge closing-badge-wait">En attente</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <p class="text-muted small mb-0">
                        Ordre recommandé : amortissements → clôture charges/produits → report à nouveau → verrouillage.
                        Les écritures sur un exercice verrouillé sont refusées.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Historique des exercices</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm closing-history">
                            <thead>
                                <tr>
                                    <th>Exercice</th>
                                    <th>Statut</th>
                                    <th>Amort.</th>
                                    <th>Résultat</th>
                                    <th>RAN</th>
                                    <th>Verrouillé</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($exercises as $row)
                                    <tr>
                                        <td><strong>{{ $row->fiscal_year }}</strong></td>
                                        <td><span class="badge {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                                        <td>{{ $fmt($row->depreciation_total) }}</td>
                                        <td>
                                            @if($row->hasResultDone())
                                                {{ $fmt($row->result_total) }}
                                                <span class="small">({{ $row->result_is_profit ? 'bénéf.' : 'perte' }})</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $row->hasRanDone() ? $fmt($row->ran_total) : '—' }}</td>
                                        <td>{{ $row->locked_at ? $row->locked_at->format('d/m/Y H:i') : '—' }}</td>
                                        <td>
                                            <a href="{{ route('admin.accounting-exercises.show', $row->id) }}" class="btn btn-sm closing-btn-gold">Détail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune clôture enregistrée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

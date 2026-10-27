@extends('backend.layouts.master')

@section('title')
Exercice {{ $exercise->fiscal_year }} — @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .me-btn-gold { background:#eba91b; border-color:#eba91b; color:#1e1d24; font-weight:600; }
    .me-btn-gold:hover { background:#f0b429; border-color:#f0b429; color:#000; }
    .me-btn-dark { background:#1e1d24; border-color:#1e1d24; color:#eba91b; font-weight:600; }
    .me-btn-dark:hover { background:#2a2930; border-color:#2a2930; color:#f0b429; }
</style>
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
                <h4 class="page-title pull-left">Exercice {{ $exercise->fiscal_year }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-exercises.index') }}">Clôture</a></li>
                    <li><span>{{ $exercise->fiscal_year }}</span></li>
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
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-muted small">Statut</div>
                            <span class="badge {{ $exercise->statusBadgeClass() }}">{{ $exercise->statusLabel() }}</span>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Amortissements</div>
                            <strong>{{ $fmt($exercise->depreciation_total) }}</strong>
                            <div class="small">{{ $exercise->document_no ?: '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Résultat</div>
                            <strong>
                                @if($exercise->hasResultDone())
                                    {{ $fmt($exercise->result_total) }}
                                    ({{ $exercise->result_is_profit ? 'bénéfice' : 'perte' }})
                                @else
                                    —
                                @endif
                            </strong>
                            <div class="small">{{ $exercise->result_document_no ?: '' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">RAN / Verrouillage</div>
                            <strong>{{ $exercise->hasRanDone() ? $fmt($exercise->ran_total) : '—' }}</strong>
                            <div class="small">
                                @if($exercise->isLocked())
                                    Verrouillé le {{ optional($exercise->locked_at)->format('d/m/Y H:i') }}
                                    @if($exercise->next_fiscal_year)
                                        → {{ $exercise->next_fiscal_year }}
                                    @endif
                                @else
                                    Non verrouillé
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($exercise->notes)
                        <hr>
                        <div class="small"><strong>Notes :</strong> {{ $exercise->notes }}</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">Suite du processus</h4>
                    <div class="btn-group flex-wrap">
                        @if(!$exercise->isLocked() && $pending->count() > 0)
                            <a href="{{ route('admin.accounting-exercises.preview', ['fiscal_year' => $fiscalYear]) }}" class="btn me-btn-gold">1. Amortissements</a>
                        @endif
                        @if(!$exercise->isLocked() && !$exercise->hasResultDone())
                            <a href="{{ route('admin.accounting-exercises.preview-result', ['fiscal_year' => $fiscalYear]) }}" class="btn me-btn-gold">2. Résultat</a>
                        @endif
                        @if(!$exercise->isLocked() && $exercise->hasResultDone() && !$exercise->hasRanDone())
                            <a href="{{ route('admin.accounting-exercises.preview-ran', ['fiscal_year' => $fiscalYear]) }}" class="btn me-btn-gold">3. Report à nouveau</a>
                        @endif
                        @if(!$exercise->isLocked() && $exercise->hasRanDone())
                            <form method="POST" action="{{ route('admin.accounting-exercises.lock') }}" class="d-inline"
                                  onsubmit="return confirm('Verrouiller l\'exercice {{ $fiscalYear }} ?');">
                                @csrf
                                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                                <input type="hidden" name="confirm" value="1">
                                <input type="hidden" name="advance_year" value="1">
                                <button type="submit" class="btn me-btn-dark">4. Verrouiller</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">Dotations comptabilisées</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Article</th>
                                    <th>Exercice</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($postings as $p)
                                    <tr>
                                        <td>{{ $p->id }}</td>
                                        <td>{{ optional($p->material)->name }} ({{ optional($p->material)->code }})</td>
                                        <td>{{ $p->fiscal_year }}</td>
                                        <td>{{ $fmt($p->amount) }}</td>
                                        <td>{{ $p->posting_date ? $p->posting_date->format('d/m/Y') : '—' }}</td>
                                        <td>
                                            @if($p->status === 'posted')
                                                <span class="badge badge-success">Posté</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $p->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->posted_by ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center">Aucune écriture d'amortissement.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="header-title">Écritures de clôture (résultat / RAN)</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Étape</th>
                                    <th>Pièce</th>
                                    <th>Compte</th>
                                    <th>Libellé</th>
                                    <th>Débit</th>
                                    <th>Crédit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($details as $d)
                                    <tr>
                                        <td>{{ $d->step }}</td>
                                        <td>{{ $d->document_no }}</td>
                                        <td>
                                            @if($d->account)
                                                {{ $d->account->account_number }} — {{ $d->account->account_name }}
                                            @else
                                                #{{ $d->account_id }}
                                            @endif
                                        </td>
                                        <td>{{ $d->libelle }}</td>
                                        <td>{{ $fmt($d->debit) }}</td>
                                        <td>{{ $fmt($d->credit) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center">Pas encore d'écritures résultat/RAN.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($pending->count() > 0)
            <div class="alert alert-warning">
                {{ $pending->count() }} dotation(s) restent à passer pour {{ $fiscalYear }}.
                <a href="{{ route('admin.accounting-exercises.preview', ['fiscal_year' => $fiscalYear]) }}">Prévisualiser</a>
            </div>
            @endif

            <a href="{{ route('admin.accounting-exercises.index') }}" class="btn btn-secondary">Retour</a>
        </div>
    </div>
</div>
@endsection

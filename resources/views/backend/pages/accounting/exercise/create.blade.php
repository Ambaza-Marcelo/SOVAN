@extends('backend.layouts.master')

@section('title')
Nouvel exercice - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .account-autocomplete-wrap { position: relative; }
    .account-results-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #ced4da; border-top: none;
        max-height: 220px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .account-results-dropdown .account-item {
        padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;
    }
    .account-results-dropdown .account-item:hover { background: #f8f9fa; }
    .account-results-dropdown .account-item:last-child { border-bottom: none; }
</style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Nouvel exercice</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-exercises.index') }}">Exercices</a></li>
                    <li><span>Créer</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Créer un exercice fiscal</h4>
                    @include('backend.layouts.partials.messages')
                    @php
                        $profitId = old('profit_account_id', optional($setting)->profit_account_id);
                        $lossId = old('loss_account_id', optional($setting)->loss_account_id);
                        $retainedId = old('retained_earnings_account_id', optional($setting)->retained_earnings_account_id);
                        $profitAcc = $profitId ? $accounts->firstWhere('id', (int) $profitId) : null;
                        $lossAcc = $lossId ? $accounts->firstWhere('id', (int) $lossId) : null;
                        $retainedAcc = $retainedId ? $accounts->firstWhere('id', (int) $retainedId) : null;
                        $profitLabel = $profitAcc ? ($profitAcc->account_number . ' - ' . $profitAcc->account_name) : old('profit_account_search', '');
                        $lossLabel = $lossAcc ? ($lossAcc->account_number . ' - ' . $lossAcc->account_name) : old('loss_account_search', '');
                        $retainedLabel = $retainedAcc ? ($retainedAcc->account_number . ' - ' . $retainedAcc->account_name) : old('retained_earnings_account_search', '');
                    @endphp
                    <form action="{{ route('admin.accounting-exercises.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Année fiscale *</label>
                                    <input type="text" name="fiscal_year" class="form-control" value="{{ old('fiscal_year', date('Y')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', 'Exercice ' . date('Y')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date début *</label>
                                    <input type="date" name="date_start" class="form-control" value="{{ old('date_start', date('Y') . '-01-01') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date fin *</label>
                                    <input type="date" name="date_end" class="form-control" value="{{ old('date_end', date('Y') . '-12-31') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mt-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_current" name="is_current" value="1" {{ old('is_current', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_current">Exercice courant</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h5>Comptes de clôture (optionnel ici, sinon via Paramètres)</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="profit_account_search">Compte résultat bénéficiaire</label>
                                    <input type="text" class="form-control" id="profit_account_search" name="profit_account_search" placeholder="@lang('messages.search_account_placeholder')" autocomplete="off" value="{{ $profitLabel }}">
                                    <input type="hidden" name="profit_account_id" id="profit_account_id" value="{{ $profitId }}">
                                    <div id="profit_account_results" class="account-results-dropdown" style="display:none;"></div>
                                    <small class="text-muted">Tapez un numéro ou un nom de compte…</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="loss_account_search">Compte résultat déficitaire</label>
                                    <input type="text" class="form-control" id="loss_account_search" name="loss_account_search" placeholder="@lang('messages.search_account_placeholder')" autocomplete="off" value="{{ $lossLabel }}">
                                    <input type="hidden" name="loss_account_id" id="loss_account_id" value="{{ $lossId }}">
                                    <div id="loss_account_results" class="account-results-dropdown" style="display:none;"></div>
                                    <small class="text-muted">Tapez un numéro ou un nom de compte…</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group account-autocomplete-wrap">
                                    <label for="retained_earnings_account_search">Report à nouveau (optionnel)</label>
                                    <input type="text" class="form-control" id="retained_earnings_account_search" name="retained_earnings_account_search" placeholder="@lang('messages.search_account_placeholder')" autocomplete="off" value="{{ $retainedLabel }}">
                                    <input type="hidden" name="retained_earnings_account_id" id="retained_earnings_account_id" value="{{ $retainedId }}">
                                    <div id="retained_earnings_account_results" class="account-results-dropdown" style="display:none;"></div>
                                    <small class="text-muted">Tapez un numéro ou un nom de compte…</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.accounting-exercises.index') }}" class="btn btn-secondary">Retour</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
(function ($) {
    var accountSearchUrl = '{{ route("admin.accounting-accounts.search") }}';
    var noAccountLabel = @json(__('messages.Aucun compte trouvé'));

    function bindAccountSearch(inputId, hiddenId, resultsId) {
        var timer;
        $('#' + inputId).on('input', function () {
            var q = $(this).val().trim();
            $('#' + hiddenId).val('');
            clearTimeout(timer);
            if (q.length < 1) {
                $('#' + resultsId).hide().empty();
                return;
            }
            timer = setTimeout(function () {
                $.get(accountSearchUrl, { q: q }, function (data) {
                    var html = '';
                    var esc = function (s) {
                        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    };
                    (data.results || []).forEach(function (r) {
                        var raw = (r.text || '').replace(/"/g, '&quot;');
                        html += '<div class="account-item" data-id="' + r.id + '" data-text="' + raw + '">' + esc(r.text) + '</div>';
                    });
                    $('#' + resultsId).html(html || '<div class="account-item text-muted">' + noAccountLabel + '</div>').show();
                });
            }, 200);
        }).on('blur', function () {
            setTimeout(function () { $('#' + resultsId).hide(); }, 200);
        });

        $(document).on('click', '#' + resultsId + ' .account-item[data-id]', function () {
            $('#' + hiddenId).val($(this).data('id'));
            $('#' + inputId).val($(this).data('text'));
            $('#' + resultsId).hide().empty();
        });
    }

    bindAccountSearch('profit_account_search', 'profit_account_id', 'profit_account_results');
    bindAccountSearch('loss_account_search', 'loss_account_id', 'loss_account_results');
    bindAccountSearch('retained_earnings_account_search', 'retained_earnings_account_id', 'retained_earnings_account_results');
})(jQuery);
</script>
@endsection

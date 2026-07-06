@extends('backend.layouts.master')

@section('title')
Nouveau Taux de Change - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Nouveau Taux de Change')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.exchange-rates.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Nouveau Taux de Change')</span></li>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Nouveau Taux de Change')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.exchange-rates.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="origin_currency_id">@lang('messages.Devise d\'Origine') <strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="origin_currency_id" id="origin_currency_id" required>
                                        <option value="" disabled selected>@lang('messages.Merci de choisir')</option>
                                        @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" class="form-control">{{ $currency->currency_name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">@lang('messages.Exemple: BIF')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destination_currency_id">@lang('messages.Devise de Destination') <strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="destination_currency_id" id="destination_currency_id" required>
                                        <option value="" disabled selected>@lang('messages.Merci de choisir')</option>
                                        @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" class="form-control">{{ $currency->currency_name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">@lang('messages.Exemple: USD')</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rate">@lang('messages.Taux de Change') <strong style="color: red;">*</strong></label>
                                    <input type="number" class="form-control" id="rate" name="rate" step="0.00000001" min="0" placeholder="Ex: 2900" required>
                                    <small class="form-text text-muted">@lang('messages.Exemple: 1 BIF = 2900 USD, entrez 2900')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">@lang('messages.Date') <strong style="color: red;">*</strong></label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">@lang('messages.Description')</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description optionnelle"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                                    <label class="form-check-label" for="active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <strong>@lang('messages.Note:')</strong> Le taux de change représente combien d'unités de la devise de destination équivalent à 1 unité de la devise d'origine.
                                    <br>Exemple: Si 1 BIF = 2900 USD, entrez 2900 dans le champ "Taux de Change".
                                </div>
                            </div>
                        </div>
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary mt-4 pr-4 pl-4">@lang('messages.save')</button>
                        <a href="{{ route('admin.exchange-rates.index') }}" class="btn btn-secondary mt-4 pr-4 pl-4">@lang('messages.cancel')</a>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const originSelect = document.getElementById('origin_currency_id');
        const destinationSelect = document.getElementById('destination_currency_id');
        
        function validateCurrencySelection() {
            if (originSelect.value && destinationSelect.value && originSelect.value === destinationSelect.value) {
                alert('La devise d\'origine et la devise de destination ne peuvent pas être identiques !');
                destinationSelect.value = '';
                return false;
            }
            return true;
        }
        
        originSelect.addEventListener('change', validateCurrencySelection);
        destinationSelect.addEventListener('change', validateCurrencySelection);
        
        // Validation avant soumission
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!validateCurrencySelection()) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endsection


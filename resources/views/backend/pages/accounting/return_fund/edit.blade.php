
@extends('backend.layouts.master')

@section('title')
@lang('messages.Modifier un retour de fonds') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Modifier un retour de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-return-fund.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Modifier un retour de fonds')</span></li>
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
                    <h4 class="header-title">@lang('messages.Modifier un retour de fonds')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.accounting-return-fund.update', $return_fund->id) }}" method="post" id="dynamic_form">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ old('date', \Carbon\Carbon::parse($return_fund->date)->format('Y-m-d\TH:i')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employe_id">@lang('messages.Employé')</label>
                                <input type="text" class="form-control" value="{{ $return_fund->employe ? $return_fund->employe->firstname . ' ' . $return_fund->employe->lastname : 'N/A' }}" readonly>
                                <small class="text-muted">@lang('messages.Non modifiable')</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="account_id">@lang('messages.Account')</label>
                                <input type="text" class="form-control" value="{{ $return_fund->account ? $return_fund->account->account_number . ' - ' . $return_fund->account->account_name : 'N/A' }}" readonly>
                                <small class="text-muted">@lang('messages.Non modifiable')</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_amount_picked" class="col-form-label">@lang('messages.Montant Sorti')<strong style="color: red;">*</strong></label>
                                <input type="number" name="total_amount_picked" class="form-control" id="total_amount_picked" placeholder="@lang('messages.Montant sorti')" step="0.01" min="0" value="{{ old('total_amount_picked', $return_fund->total_amount_picked) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_amount_returned" class="col-form-label">@lang('messages.Montant Retourné')<strong style="color: red;">*</strong></label>
                                <input type="number" name="total_amount_returned" class="form-control" id="total_amount_returned" placeholder="@lang('messages.Entrer le montant retourné')" step="0.01" min="0" max="{{ $return_fund->total_amount_picked }}" value="{{ old('total_amount_returned', $return_fund->total_amount_returned) }}" required>
                                <small class="text-danger" id="amount_error" style="display: none;">Le montant retourné ne peut pas être supérieur au montant sorti ({{ number_format($return_fund->total_amount_picked, 2) }})</small>
                            </div>
                        </div>
                    </div>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')" rows="3">{{ old('description', $return_fund->description) }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.save')</button>
                            <a href="{{ route('admin.accounting-return-fund.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var $amountPicked = $('#total_amount_picked');
            var $amountReturned = $('#total_amount_returned');
            var $amountError = $('#amount_error');
            
            function validateAmount() {
                var totalAmountPicked = parseFloat($amountPicked.val()) || 0;
                var amountReturned = parseFloat($amountReturned.val()) || 0;
                
                // Mettre à jour l'attribut max
                $amountReturned.attr('max', totalAmountPicked);
                
                // Mettre à jour le message d'erreur
                $amountError.text('Le montant retourné ne peut pas être supérieur au montant sorti (' + totalAmountPicked.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')');
                
                if (amountReturned > totalAmountPicked) {
                    $amountError.show();
                    $amountReturned.addClass('is-invalid');
                } else {
                    $amountError.hide();
                    $amountReturned.removeClass('is-invalid');
                }
            }
            
            $amountPicked.on('input', function() {
                validateAmount();
            });
            
            $amountReturned.on('input', function() {
                validateAmount();
            });
            
            $('#dynamic_form').on('submit', function(e) {
                var totalAmountPicked = parseFloat($amountPicked.val()) || 0;
                var amountReturned = parseFloat($amountReturned.val()) || 0;
                
                if (amountReturned > totalAmountPicked) {
                    e.preventDefault();
                    alert('Le montant retourné ne peut pas être supérieur au montant sorti (' + totalAmountPicked.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')');
                    return false;
                }
            });
        });
</script>
@endsection
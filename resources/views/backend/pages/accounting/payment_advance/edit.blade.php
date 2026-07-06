
@extends('backend.layouts.master')

@section('title')
@lang('messages.edit advance payment') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .account-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .account-suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.2s;
    }
    
    .account-suggestion-item:hover {
        background-color: #f8f9fa;
    }
    
    .account-suggestion-item:last-child {
        border-bottom: none;
    }
    
    .account-suggestion-title {
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    
    .account-suggestion-desc {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
    
    .account-suggestion-item.highlighted {
        background-color: #007bff;
        color: white;
    }
    
    .account-suggestion-item.highlighted .account-suggestion-title,
    .account-suggestion-item.highlighted .account-suggestion-desc {
        color: white;
    }
    
    .account-input-wrapper {
        position: relative;
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.edit advance payment')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.accounting-advance-payment.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit advance payment')</span></li>
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
                    <h4 class="header-title">@lang('messages.Edit advance payment')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.accounting-advance-payment.update', $advance->id) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date" class="col-form-label">@lang('messages.Date') <strong style="color: red;">*</strong></label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $advance->date) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_type" class="col-form-label">@lang('messages.Payment Type') <strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="payment_type" id="payment_type" required>
                                        <option value="">@lang('messages.Please select')</option>
                                        <option value="INTERNAL" {{ old('payment_type', $advance->payment_type) == 'INTERNAL' ? 'selected' : '' }}>INTERNAL</option>
                                        <option value="EXTERNAL" {{ old('payment_type', $advance->payment_type) == 'EXTERNAL' ? 'selected' : '' }}>EXTERNAL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="client_search" class="col-form-label">@lang('messages.Client') <strong style="color: red;">*</strong></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               class="form-control account-input" 
                                               id="client_search" 
                                               placeholder="@lang('messages.Tapez pour rechercher un client...')" 
                                               autocomplete="off" 
                                               value="{{ old('client_display', optional($advance->client)->customer_name . (optional($advance->client)->telephone ? ' - ' . optional($advance->client)->telephone : '')) }}"
                                               required>
                                        <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $advance->client_id) }}">
                                        <div class="account-suggestions" id="client_suggestions" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="acc_account_search" class="col-form-label">@lang('messages.Bank/Cash') <strong style="color: red;">*</strong></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               class="form-control account-input" 
                                               id="acc_account_search" 
                                               placeholder="@lang('messages.Tapez pour rechercher un compte...')" 
                                               autocomplete="off" 
                                               value="{{ old('acc_account_display', optional($advance->account)->account_number . '-' . optional($advance->account)->account_name) }}"
                                               required>
                                        <input type="hidden" name="account_id" id="account_id" value="{{ old('account_id', $advance->account_id) }}">
                                        <div class="account-suggestions" id="acc_account_suggestions" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="advance_account_search" class="col-form-label">@lang('messages.Advance Account (418)') <strong style="color: red;">*</strong></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               class="form-control account-input" 
                                               id="advance_account_search" 
                                               placeholder="@lang('messages.Type to search account (418)...')" 
                                               autocomplete="off" 
                                               value="{{ old('advance_account_display', optional($advance->advanceAccount)->account_number . '-' . optional($advance->advanceAccount)->account_name) }}"
                                               required>
                                        <input type="hidden" name="advance_account_id" id="advance_account_id" value="{{ old('advance_account_id', $advance->advance_account_id) }}">
                                        <div class="account-suggestions" id="advance_account_suggestions" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_name" class="col-form-label">@lang('messages.Contact Name') <strong style="color: red;">*</strong></label>
                                    <input type="text" name="contact_name" class="form-control" id="contact_name" placeholder="@lang('messages.Enter contact name')" value="{{ old('contact_name', $advance->contact_name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_tel" class="col-form-label">@lang('messages.Contact Phone Number') <strong style="color: red;">*</strong></label>
                                    <input type="text" name="contact_tel" class="form-control" id="contact_tel" placeholder="@lang('messages.Enter contact phone number')" value="{{ old('contact_tel', $advance->contact_tel) }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="col-form-label">@lang('messages.Amount') <strong style="color: red;">*</strong></label>
                                    <input type="number" name="amount" class="form-control" id="amount" placeholder="@lang('messages.Enter amount')" step="0.01" min="0" value="{{ old('amount', $advance->amount) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description" class="col-form-label">@lang('messages.Description')</label>
                                    <textarea name="description" class="form-control" id="description" placeholder="@lang('messages.Enter Description')" rows="3">{{ old('description', $advance->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="document" class="col-form-label">@lang('messages.Document')</label>
                                    <input type="file" name="document" class="form-control" id="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <small class="form-text text-muted">@lang('messages.Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG')</small>
                                    @if($advance->document)
                                        <div class="mt-2">
                                            <strong>@lang('messages.Current document:')</strong> 
                                            <a href="{{ route('admin.accounting-advance-payment.document.view', $advance->id) }}" target="_blank" class="btn btn-sm btn-info" title="View Document">
                                                <i class="fa fa-eye"></i> View Current Document
                                            </a>
                                            <a href="{{ route('admin.accounting-advance-payment.document.download', $advance->id) }}" class="btn btn-sm btn-success ml-2" title="Download Document">
                                                <i class="fa fa-download"></i> Download
                                            </a>
                                        </div>
                                    @endif
                                    <div id="document_preview" style="margin-top: 10px; display: none;">
                                        <div class="alert alert-info">
                                            <strong>@lang('messages.Selected file:')</strong> <span id="document_name"></span>
                                            <button type="button" class="btn btn-sm btn-danger ml-2" id="remove_document">@lang('messages.Remove')</button>
                                        </div>
                                        <div id="document_preview_content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                            <a href="{{ route('admin.accounting-advance-payment.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
@include('backend.layouts.partials.ajax_autocomplete')
<script>
    $(document).ready(function(){
        attachAjaxAutocomplete({
            $input: $('#client_search'),
            $hidden: $('#client_id'),
            url: '{{ route("admin.autocomplete.clients") }}'
        });

        attachAjaxAutocomplete({
            $input: $('#acc_account_search'),
            $hidden: $('#account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}',
            extraParams: function(){
                return { account_type: ($('#payment_type').val() === 'INTERNAL') ? 3 : null };
            }
        });

        attachAjaxAutocomplete({
            $input: $('#advance_account_search'),
            $hidden: $('#advance_account_id'),
            url: '{{ route("admin.accounting-accounts.search") }}',
            extraParams: { account_prefix: '418' }
        });

        $('#payment_type').on('change', function(){
            $('#account_id').val('');
        });
    });

    // Document upload preview
    function previewDocument(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            var fileName = file.name;
            var fileType = file.type;
            var fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
            
            $('#document_name').text(fileName + ' (' + fileSize + ' MB)');
            $('#document_preview').show();
            
            // Preview for images
            if (fileType.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#document_preview_content').html('<img src="' + e.target.result + '" style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">');
                };
                reader.readAsDataURL(file);
            } else {
                // For PDF and other documents, just show file info
                $('#document_preview_content').html('<p class="text-muted">File: ' + fileName + '</p>');
            }
        }
    }

    // Document upload handler
    $('#document').on('change', function() {
        previewDocument(this);
    });
</script>
@endsection


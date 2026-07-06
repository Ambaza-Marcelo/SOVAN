@php
    $display_text = $value_display ?? '';
    if ($display_text === '' && !empty($value_id) && !empty($accounts)) {
        $acc = $accounts->firstWhere('id', $value_id);
        $display_text = $acc ? ($acc->account_number . ' - ' . $acc->account_name) : '';
    }
@endphp
<div class="form-group account-search-wrapper" data-field="{{ $name }}">
    <label for="{{ $name }}">{{ $label }} @if(!empty($required))<span class="text-danger">*</span>@endif</label>
    <div class="position-relative">
        <input type="text"
               class="form-control account-search-input"
               id="{{ $name }}_display"
               name="{{ $name }}_display"
               placeholder="{{ __('messages.Search by account number or name...') }}"
               autocomplete="off"
               value="{{ $display_text }}"
               @if(!empty($required)) required @endif />
        <input type="hidden" class="account-search-id" name="{{ $name }}" id="{{ $name }}" value="{{ $value_id ?? '' }}" />
        <div class="account-search-suggestions" style="display: none;"></div>
    </div>
</div>

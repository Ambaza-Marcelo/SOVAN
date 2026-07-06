{{-- Pied de page document: coordonnées bancaires (Setting model only) --}}
@php
    $setting = $setting ?? \App\Models\Setting::first();
    $bankNames = $setting ? ($setting->bank_name ?? []) : [];
    $accountNumbers = $setting ? ($setting->account_number ?? []) : [];
    $bankNames = is_array($bankNames) ? $bankNames : [];
    $accountNumbers = is_array($accountNumbers) ? $accountNumbers : [];
    $count = max(count($bankNames), count($accountNumbers));
@endphp
<div style="margin-top:25px; padding-top:10px; border-top:1px solid #333; font-size:10px;">
    <strong style="text-decoration:underline;">@lang('messages.COORDONNÉES BANCAIRES')</strong><br>
    @if($count > 0)
        @for($i = 0; $i < $count; $i++)
            @if(!empty($bankNames[$i] ?? '') || !empty($accountNumbers[$i] ?? ''))
                <span>{{ $bankNames[$i] ?? '—' }}: {{ $accountNumbers[$i] ?? '—' }}</span><br>
            @endif
        @endfor
    @else
        <span>@lang('messages.BANCOBU: _______________________')</span><br>
        <span>@lang('messages.CORILAC: _______________________')</span><br>
        <span>@lang('messages.CRDB: _______________________')</span><br>
        <span>@lang('messages.BBCI: _______________________')</span><br>
        <span>@lang('messages.ECOBANK: _______________________')</span><br>
    @endif
</div>

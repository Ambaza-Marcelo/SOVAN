{{-- Bloc Client / Date (format type devis) --}}
@php
    $customerNif = $customerNif ?? '';
    $customerSubjectToVat = $customerSubjectToVat ?? null;
@endphp
<table style="width:100%; border:none; margin-bottom:14px;">
    <tr>
        <td style="border:none; width:50%; vertical-align:top;">
            @if(!empty($customerName))
                <strong>@lang('messages.Client:')</strong> {{ $customerName }}<br>
            @endif
            @if(!empty($customerAddress))
                <span style="font-size:10px;">{{ $customerAddress }}</span><br>
            @endif
            @if(!empty($customerPhone))
                <span style="font-size:10px;">Tél: {{ $customerPhone }}</span><br>
            @endif
            @if($customerNif !== '' && $customerNif !== null)
                <span style="font-size:10px;">NIF: {{ $customerNif }}</span><br>
            @endif
            @if(isset($customerSubjectToVat))
                <span style="font-size:10px;">Assujetti à la TVA: {{ $customerSubjectToVat }}</span>
            @endif
        </td>
        <td style="border:none; width:50%; text-align:right; vertical-align:top;">
            @if(!empty($documentDate))
                <strong>@lang('messages.Date:')</strong> {{ $documentDate }}<br>
            @endif
            @if(!empty($validTill))
                <strong>@lang('messages.Valide jusqu\'au:')</strong> {{ $validTill }}
            @endif
        </td>
    </tr>
</table>

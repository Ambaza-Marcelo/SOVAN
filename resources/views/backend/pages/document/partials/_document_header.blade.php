{{-- En-tête document: informations de la société (format type devis / facture) --}}
@php
    $setting = $setting ?? null;
    $companyName = $setting ? ($setting->name ?? $setting->company_name ?? 'MUSUMBA ELCOM') : 'MUSUMBA ELCOM';

    $docDate = null;
    $dateCandidates = array_filter([
        $documentDate ?? null,
        $date ?? null,
    ], static fn ($value) => $value !== null && $value !== '');

    foreach ($dateCandidates as $candidate) {
        if ($candidate instanceof \DateTimeInterface) {
            $docDate = $candidate->format('d/m/Y H:i');
            break;
        }

        if (!is_string($candidate)) {
            continue;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $candidate)) {
            $docDate = $candidate;
            break;
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            try {
                $docDate = \Carbon\Carbon::createFromFormat($format, $candidate)->format('d/m/Y H:i');
                break 2;
            } catch (\Exception $e) {
            }
        }
    }

    if (!$docDate) {
        $docDate = now()->format('d/m/Y H:i');
    }

    $publicLogoPath = public_path('img/logo.png');
@endphp
<table style="width:100%; border:none; border-bottom:1px solid #333; margin-bottom:12px;">
    <tr>
        <td style="border:none; width:22%; vertical-align:top;">
            @if(file_exists($publicLogoPath))
                <img src="{{ asset('img/logo.png') }}" alt="MUSUMBA ELCOM" style="max-width:300px; max-height:150px; object-fit:contain;">
            @endif
        </td>
        <td style="border:none; width:33%; vertical-align:top;">
            <strong style="font-size:14px;">{{ $companyName }}</strong><br>
            <span style="font-size:10px;">
                @if($setting && !empty($setting->nif))
                    NIF: {{ $setting->nif }}
                    @if(!empty($setting->rc)) &nbsp;|&nbsp; RC: {{ $setting->rc }} @endif
                    @if(!empty($setting->tp_fiscal_center)) &nbsp;|&nbsp; C.FISCAL: {{ $setting->tp_fiscal_center }} @endif
                    <br>
                @endif
                @if($setting && isset($setting->vat_taxpayer))
                    Assujetti à la TVA: {{ $setting->vat_taxpayer ? 'Oui' : 'Non' }}<br>
                @endif
                @if($setting && (!empty($setting->zone) || !empty($setting->commune)))
                    {{ $setting->zone ?? '' }} {{ $setting->commune ? '- '.$setting->commune : '' }}<br>
                @endif
                @if($setting && !empty($setting->province))
                    {{ $setting->province }}<br>
                @endif
                @if($setting && !empty($setting->rue))
                    {{ $setting->rue }}<br>
                @endif
                @if($setting && !empty($setting->quartier))
                    {{ $setting->quartier }}<br>
                @endif
                @if($setting && !empty($setting->postal_number))
                    B.P. {{ $setting->postal_number }}<br>
                @endif
                @if($setting && !empty($setting->telephone1))
                    Tél: {{ $setting->telephone1 }}@if(!empty($setting->telephone2)) / {{ $setting->telephone2 }}@endif<br>
                @endif
                @if($setting && !empty($setting->email))
                    Email: {{ $setting->email }}<br>
                @endif
            </span>
        </td>
        <td style="border:none; width:45%; text-align:right; vertical-align:top;">
            @if(!$publicLogoPath || !file_exists($publicLogoPath))
                @if($setting && !empty($setting->logo))
                @php
                    $logoPath = storage_path('public/logo/'.$setting->logo);
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" width="100" height="50" alt="" style="max-height:55px; object-fit:contain;"><br>
                @endif
                @endif
            @endif
            @if(!empty($documentTitle))
                <strong style="font-size:13px; text-decoration:underline;">{{ $documentTitle }}</strong><br>
            @endif
            @if(!empty($documentNumber))
                <span style="font-size:11px;">{{ $documentNumber }}</span><br>
            @endif
            <span style="font-size:10px;">{{ $docDate }}</span>
        </td>
    </tr>
</table>

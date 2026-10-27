{{-- En-tête document SOVAN : logo | infos société | date --}}
@php
    if (!isset($setting) || !$setting) {
        $setting = \App\Models\Setting::first();
    }

    $companyName = $setting
        ? ($setting->name ?? $setting->company_name ?? 'SOVAN')
        : 'SOVAN';

    $docDate = null;
    $dateCandidates = array_filter([
        $documentDate ?? null,
        $date ?? null,
    ], static function ($value) {
        return $value !== null && $value !== '';
    });

    foreach ($dateCandidates as $candidate) {
        if ($candidate instanceof \DateTimeInterface) {
            $docDate = $candidate->format('d/m/Y H:i');
            break;
        }

        if (!is_string($candidate)) {
            continue;
        }

        $candidate = trim($candidate);

        // Déjà au format français d/m/Y ou d/m/Y H:i
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}(\s+\d{2}:\d{2}(:\d{2})?)?$/', $candidate)) {
            $docDate = $candidate;
            break;
        }

        try {
            $docDate = \Carbon\Carbon::parse($candidate)->format('d/m/Y H:i');
            break;
        } catch (\Exception $e) {
            // essayer le candidat suivant
        }
    }

    if (!$docDate) {
        $docDate = now()->format('d/m/Y H:i');
    }

    $publicLogoPath = public_path('img/logo.png');
    $logoSrc = null;
    if (is_file($publicLogoPath)) {
        // Base64 : fiable pour PDF DomPDF et aperçu navigateur
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($publicLogoPath));
    } elseif ($setting && !empty($setting->logo)) {
        $storedLogo = storage_path('app/public/logo/' . $setting->logo);
        if (!is_file($storedLogo)) {
            $storedLogo = storage_path('public/logo/' . $setting->logo);
        }
        if (is_file($storedLogo)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($storedLogo) : 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($storedLogo));
        }
    }

    $addressParts = [];
    if ($setting) {
        foreach (['quartier', 'commune', 'province'] as $addrField) {
            $val = trim((string) ($setting->{$addrField} ?? ''));
            if ($val === '') {
                continue;
            }
            $alreadyListed = false;
            foreach ($addressParts as $existing) {
                if (mb_strtoupper($existing) === mb_strtoupper($val)) {
                    $alreadyListed = true;
                    break;
                }
            }
            if (!$alreadyListed) {
                $addressParts[] = $val;
            }
        }
    }
    $addressLine = implode(', ', $addressParts);

    // Lignes fiscales pré-composées (évite les @if imbriqués collés au texte)
    $fiscalParts = [];
    if ($setting) {
        if (!empty($setting->nif)) {
            $fiscalParts[] = 'NIF: ' . $setting->nif;
        }
        if (!empty($setting->rc)) {
            $fiscalParts[] = 'RC: ' . $setting->rc;
        }
        if (!empty($setting->tp_fiscal_center)) {
            $fiscalParts[] = 'C.FISCAL: ' . $setting->tp_fiscal_center;
        }
    }
    $fiscalLine = implode(' | ', $fiscalParts);

    $phoneLine = '';
    if ($setting && !empty($setting->telephone1)) {
        $phoneLine = 'Tél: ' . $setting->telephone1;
        if (!empty($setting->telephone2)) {
            $phoneLine .= ' / ' . $setting->telephone2;
        }
    }
@endphp
<table class="doc-header" style="width:100%; border:none; border-collapse:collapse; border-bottom:1px solid #333; margin:0 0 12px 0; padding:0 0 8px 0;">
    <tr>
        {{-- Logo --}}
        <td style="border:none; width:24%; vertical-align:top; padding:0 8px 0 0;">
            @if ($logoSrc)
                <img
                    src="{{ $logoSrc }}"
                    alt="{{ $companyName }}"
                    style="max-width:140px; max-height:70px; width:auto; height:auto; object-fit:contain; display:block;"
                >
            @endif
        </td>

        {{-- Infos société --}}
        <td style="border:none; width:52%; vertical-align:top; padding:0 8px;">
            <strong style="font-size:12px; line-height:1.25; display:block; margin-bottom:2px;">{{ $companyName }}</strong>
            <div style="font-size:9px; line-height:1.35; color:#222;">
                @if ($fiscalLine !== '')
                    {{ $fiscalLine }}<br>
                @endif

                @if ($setting && isset($setting->vat_taxpayer))
                    Assujetti à la TVA: {{ $setting->vat_taxpayer ? 'Oui' : 'Non' }}<br>
                @endif

                @if ($addressLine !== '')
                    {{ $addressLine }}<br>
                @endif

                @if ($phoneLine !== '')
                    {{ $phoneLine }}<br>
                @endif

                @if ($setting && !empty($setting->email))
                    Email: {{ $setting->email }}
                @endif
            </div>
        </td>

        {{-- Date génération --}}
        <td style="border:none; width:24%; text-align:right; vertical-align:top; padding:0 0 0 8px; white-space:nowrap;">
            <span style="font-size:10px; line-height:1.3;">{{ $docDate }}</span>
        </td>
    </tr>
</table>

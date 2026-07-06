{{-- Filtrage par période et export Excel - à inclure au-dessus du tableau dataTable --}}
@php
    $filterRoute = $filterRoute ?? request()->route()->getName();
    $exportRoute = $exportRoute ?? null;
    $useDateType = $useDateType ?? 'date'; // 'date' or 'datetime-local'
@endphp
<form action="{{ route($filterRoute) }}" method="GET" class="mb-3">
    <div class="row align-items-end">
        <div class="col-md-3">
            <label for="date_debut">@lang('messages.Date début')</label>
            <input type="{{ $useDateType }}" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
        </div>
        <div class="col-md-3">
            <label for="date_fin">@lang('messages.Date fin')</label>
            <input type="{{ $useDateType }}" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-info">@lang('messages.Filtrer')</button>
            <a href="{{ route($filterRoute) }}" class="btn btn-secondary">@lang('messages.Réinitialiser')</a>
            @if($exportRoute)
                @php
                    $exportUrl = route($exportRoute);
                    if (request('date_debut') || request('date_fin')) {
                        $exportUrl .= (strpos($exportUrl, '?') !== false ? '&' : '?') . http_build_query(request()->only('date_debut', 'date_fin'));
                    }
                @endphp
                <a href="{{ $exportUrl }}" class="btn btn-success" target="_blank">@lang('messages.export_excel')</a>
            @endif
        </div>
    </div>
</form>

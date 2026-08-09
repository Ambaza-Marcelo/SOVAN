@php
    $inputId = $inputId ?? 'employe_search';
    $hiddenId = $hiddenId ?? 'employe_id';
    $hiddenName = $hiddenName ?? 'employe_id';
    $listId = $listId ?? $inputId . '-list';
    $placeholder = $placeholder ?? 'Tapez nom ou matricule...';
    $selectedEmployeId = $selectedEmployeId ?? null;
    $initialLabel = $initialLabel ?? '';

    if (empty($initialLabel) && !empty($selectedEmployeId) && isset($employes)) {
        $matched = $employes->firstWhere('id', (int) $selectedEmployeId);
        $initialLabel = $matched ? $matched->display_label : '';
    }
@endphp
<div class="autocomplete-wrapper" style="position:relative;">
    <input type="text" id="{{ $inputId }}" class="form-control" placeholder="{{ $placeholder }}" autocomplete="off" value="{{ $initialLabel }}">
    <input type="hidden" name="{{ $hiddenName }}" id="{{ $hiddenId }}" value="{{ $selectedEmployeId }}">
    <div class="autocomplete-list" id="{{ $listId }}" style="position:absolute;border:1px solid #d4d4d4;border-top:none;z-index:99;max-height:200px;overflow-y:auto;background:#fff;width:100%;display:none;box-shadow:0 2px 4px rgba(0,0,0,0.1);"></div>
</div>

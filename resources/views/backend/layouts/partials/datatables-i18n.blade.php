@php
    $dtLanguage = [
        'lengthMenu' => __('messages.datatable_length_menu'),
        'zeroRecords' => __('messages.datatable_zero_records'),
        'info' => __('messages.datatable_info'),
        'infoEmpty' => __('messages.datatable_info_empty'),
        'infoFiltered' => __('messages.datatable_info_filtered'),
        'search' => __('messages.datatable_search'),
        'paginate' => [
            'first' => __('messages.datatable_first'),
            'last' => __('messages.datatable_last'),
            'previous' => __('messages.datatable_previous'),
            'next' => __('messages.datatable_next'),
        ],
    ];
@endphp
<script>
window.dtLanguage = @json($dtLanguage);
</script>
<script>
jQuery(document).on('preInit.dt', function (e, settings) {
    if (window.dtLanguage && settings) {
        settings.oLanguage = jQuery.extend(true, {}, window.dtLanguage, settings.oLanguage || {});
    }
});
</script>

@extends('backend.layouts.master')

@section('title')
@lang('messages.note_credit_title_page') — @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Note de crédit')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.invoice.show', $invoice->invoice_number) }}">{{ __('messages.note_credit_invoice_breadcrumb', ['number' => $invoice->invoice_number]) }}</a></li>
                    <li><span>@lang('messages.Avoir')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                @include('backend.layouts.partials.messages')

                <div class="alert alert-secondary small mb-3">
                    @lang('messages.note_credit_doc_type_alert', [
                        'fa' => __('messages.FA'),
                        'credit_note' => __('messages.note_credit_credit_note_abbr'),
                        'format' => __('messages.FA#####'),
                    ])
                </div>
                <div class="alert alert-info">
                    @if($alreadyCredited > 0)
                        {{ __('messages.note_credit_source_summary', [
                            'number' => $invoice->invoice_number,
                            'total' => number_format($invoiceTotal, 2, ',', ' '),
                            'currency' => $invoice->invoice_currency ?? 'BIF',
                            'credited' => number_format($alreadyCredited, 2, ',', ' '),
                            'remaining' => number_format($remaining, 2, ',', ' '),
                        ]) }}
                    @else
                        {{ __('messages.note_credit_source_summary_short', [
                            'number' => $invoice->invoice_number,
                            'total' => number_format($invoiceTotal, 2, ',', ' '),
                            'currency' => $invoice->invoice_currency ?? 'BIF',
                            'remaining' => number_format($remaining, 2, ',', ' '),
                        ]) }}
                    @endif
                </div>

                <form action="{{ route('admin.note-de-credit.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="source_invoice_number" value="{{ $invoice->invoice_number }}">

                    <div class="form-group">
                        <label for="cn_motif">@lang('messages.Motif de la note de crédit') <span class="text-danger">*</span></label>
                        <select name="cn_motif" id="cn_motif" class="form-control" required>
                            <option value="1">@lang('messages.Erreur sur la facture')</option>
                            <option value="2">@lang('messages.note_credit_motif_return_goods_stock')</option>
                            <option value="3">@lang('messages.Rabais / remise / ristourne')</option>
                            <option value="4">@lang('messages.Réduction hors facture')</option>
                        </select>
                    </div>

                    <h5 class="mt-4">@lang('messages.Lignes à créditer')</h5>
                    <p class="text-muted small">@lang('messages.note_credit_lines_help')</p>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('messages.Article')</th>
                                <th class="text-right">@lang('messages.Qté facturée')</th>
                                <th class="text-right">@lang('messages.Qté à créditer')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->details as $d)
                                @if($d->item_id)
                                <tr>
                                    <td>
                                        {{ $d->item_designation ?? ($d->item->name ?? 'N/A') }}
                                        <input type="hidden" name="item_id[]" value="{{ $d->item_id }}">
                                    </td>
                                    <td class="text-right">{{ number_format($d->item_quantity, 2, ',', ' ') }}</td>
                                    <td style="max-width:140px;">
                                        <input type="number" step="0.01" min="0" max="{{ $d->item_quantity }}" name="item_quantity[]" class="form-control text-right" value="0">
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                    <button type="submit" class="btn btn-primary">@lang('messages.Enregistrer la note de crédit')</button>
                    <a href="{{ route('admin.invoice.show', $invoice->invoice_number) }}" class="btn btn-light">@lang('messages.Annuler')</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

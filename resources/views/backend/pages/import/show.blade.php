@extends('backend.layouts.master')

@section('title')
@lang('messages.Détail importation') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <style>
        .nav-tabs .nav-link { color: #495057; border: 1px solid transparent; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; }
        .nav-tabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; }
        .nav-tabs .nav-link.active { color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff; }
        .tab-content { padding: 20px; border: 1px solid #dee2e6; border-top: none; background-color: #fff; }
        .info-row { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef; }
        .info-label { font-weight: 600; color: #495057; margin-bottom: 5px; }
        .info-value { color: #212529; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-encours { background-color: #ffc107; color: #000; }
        .badge-valide { background-color: #17a2b8; color: #fff; }
        .badge-confirme { background-color: #007bff; color: #fff; }
        .badge-approuve { background-color: #28a745; color: #fff; }
        .badge-rejete { background-color: #dc3545; color: #fff; }
    </style>
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Importation {{ $import->import_no }}</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.imports.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Détail')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    @include('backend.layouts.partials.messages')

                    <ul class="nav nav-tabs" id="importTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">@lang('messages.Informations générales')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="items-tab" data-toggle="tab" href="#items" role="tab">Articles ({{ $importDetails->count() }})</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab">@lang('messages.Financier &amp; douane')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab">@lang('messages.Statut')</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="importTabsContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.N° import')</div>
                                        <div class="info-value">{{ $import->import_no }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Signature')</div>
                                        <div class="info-value">{{ $import->import_signature }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Date')</div>
                                        <div class="info-value">{{ $import->date ? \Carbon\Carbon::parse($import->date)->format('d/m/Y H:i') : '—' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Type mouvement')</div>
                                        <div class="info-value">@lang('messages.EN — Entrée normale')</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Remettant')</div>
                                        <div class="info-value">{{ $import->handingover ?? '—' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Réceptionniste')</div>
                                        <div class="info-value">{{ $import->receptionist ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Origine')</div>
                                        <div class="info-value">{{ $import->origin ?? '—' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Magasin')</div>
                                        <div class="info-value">
                                            @if($import->finishedGoodsSmStore)
                                                <span class="badge badge-secondary">@lang('messages.PF SM')</span>
                                                {{ $import->finishedGoodsSmStore->code }} — {{ $import->finishedGoodsSmStore->name }}
                                            @elseif($import->finishedGoodsStore)
                                                <span class="badge badge-info">@lang('messages.Produits finis')</span>
                                                {{ $import->finishedGoodsStore->code }} — {{ $import->finishedGoodsStore->name }}
                                            @elseif($import->goodsStore)
                                                <span class="badge badge-primary">@lang('messages.Marchandises')</span>
                                                {{ $import->goodsStore->code }} — {{ $import->goodsStore->name }}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Devise')</div>
                                        <div class="info-value">{{ $import->item_purchase_or_sale_currency ?? 'BIF' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Réf. DMC')</div>
                                        <div class="info-value">{{ $import->reference_dmc ?? '—' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Rubrique tarifaire')</div>
                                        <div class="info-value">{{ $import->rubrique_tarifaire ?? '—' }}</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Créé par / le')</div>
                                        <div class="info-value">{{ $import->created_by }} — {{ $import->created_at ? \Carbon\Carbon::parse($import->created_at)->format('d/m/Y H:i') : '' }}</div>
                                    </div>
                                    @if($import->updated_by)
                                    <div class="info-row">
                                        <div class="info-label">@lang('messages.Modifié par')</div>
                                        <div class="info-value">{{ $import->updated_by }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @if($import->description)
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="info-label">@lang('messages.Description')</div>
                                    <div class="info-value" style="white-space: pre-wrap;">{{ $import->description }}</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="items" role="tabpanel">
                            <div class="table-responsive data-tables">
                                <table id="itemsTable" class="table table-bordered text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>@lang('messages.Article')</th>
                                            <th>@lang('messages.Qté')</th>
                                            <th>@lang('messages.Unité')</th>
                                            <th>@lang('messages.Nbr / paquet')</th>
                                            <th>@lang('messages.Desc. paquet')</th>
                                            <th>@lang('messages.Prix achat')</th>
                                            <th>@lang('messages.Total ligne')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($importDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-left">
                                                @if($detail->item)
                                                    {{ $detail->item->name }}
                                                    @if(!empty($detail->item->code))<br><small class="text-muted">Code : {{ $detail->item->code }}</small>@endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ number_format((float)$detail->item_quantity, 2, ',', ' ') }}</td>
                                            <td>{{ $detail->item_measurement_unit }}</td>
                                            <td>{{ $detail->nombre_par_paquet !== null && $detail->nombre_par_paquet !== '' ? number_format((float)$detail->nombre_par_paquet, 2, ',', ' ') : '—' }}</td>
                                            <td class="text-left">{{ $detail->description_paquet ?: '—' }}</td>
                                            <td>{{ number_format((float)$detail->purchase_price, 2, ',', ' ') }}</td>
                                            <td>{{ number_format((float)$detail->total_purchase_value, 2, ',', ' ') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="2" class="text-right">@lang('messages.Totaux')</td>
                                            <td>{{ number_format((float)$totalQuantity, 2, ',', ' ') }}</td>
                                            <td colspan="4"></td>
                                            <td>{{ number_format((float)$sumTotalPurchaseValue, 2, ',', ' ') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="financial" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">@lang('messages.Synthèse financière')</div>
                                        <div class="card-body">
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Total achat (lignes)')</div>
                                                <div class="info-value h5">{{ number_format((float)$sumTotalPurchaseValue, 2, ',', ' ') }} {{ $import->item_purchase_or_sale_currency ?? 'BIF' }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Frais transport')</div>
                                                <div class="info-value">{{ number_format((float)$transportCost, 2, ',', ' ') }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Frais dédouanement')</div>
                                                <div class="info-value">{{ number_format((float)$clearanceCost, 2, ',', ' ') }}</div>
                                            </div>
                                            <div class="info-row">
                                                <div class="info-label">@lang('messages.Total document')</div>
                                                <div class="info-value h4 text-primary">{{ number_format((float)$grandTotalAmount, 2, ',', ' ') }} {{ $import->item_purchase_or_sale_currency ?? 'BIF' }}</div>
                                            </div>
                                            <p class="text-muted small mb-0">À l’approbation, chaque ligne est transmise à l’OBR via l’API <code>@lang('messages.AddStockMovementImporters')</code> (<code>GUZZLE_TEST_URL</code> / <code>ebms_api/AddStockMovementImporters</code>). Champs requis côté document et lignes : référence DMC, rubrique tarifaire, code et désignation article, quantité &gt; 0, unité, prix unitaire &gt; 0, nombre par paquet &gt; 0, description du paquet ; configuration <code>OBR_SYSTEM_OR_DEVICE_ID</code> @lang('messages.(ou') <code>OBR_TEST_USERNAME</code>) et identifiants de connexion OBR. Aucune mise à jour locale n’est effectuée tant que la transmission n’a pas réussi.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="status" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-label">@lang('messages.Statut')</div>
                                    <div class="info-value mb-3">
                                        @if((string)$import->status === '1')<span class="status-badge badge-encours">@lang('messages.En cours')</span>
                                        @elseif((string)$import->status === '2')<span class="status-badge badge-valide">@lang('messages.Validé')</span>
                                        @elseif((string)$import->status === '3')<span class="status-badge badge-confirme">@lang('messages.Confirmé')</span>
                                        @elseif((string)$import->status === '4')<span class="status-badge badge-approuve">@lang('messages.Approuvé')</span>
                                        @elseif((string)$import->status === '-1')<span class="status-badge badge-rejete">@lang('messages.Rejeté')</span>
                                        @else<span class="status-badge">{{ $import->status }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @if(!empty($import->validated_by))<div class="info-row"><div class="info-label">@lang('messages.Validé par')</div><div class="info-value">{{ $import->validated_by }}</div></div>@endif
                                    @if(!empty($import->confirmed_by))<div class="info-row"><div class="info-label">@lang('messages.Confirmé par')</div><div class="info-value">{{ $import->confirmed_by }}</div></div>@endif
                                    @if(!empty($import->approuved_by))<div class="info-row"><div class="info-label">@lang('messages.Approuvé par')</div><div class="info-value">{{ $import->approuved_by }}</div></div>@endif
                                    @if(!empty($import->rejected_by))<div class="info-row"><div class="info-label">@lang('messages.Rejeté par')</div><div class="info-value text-danger">{{ $import->rejected_by }}</div></div>@endif
                                </div>
                            </div>
                            @if($import->rejected_motif)
                            <div class="alert alert-danger mt-2" style="white-space: pre-wrap;">{{ $import->rejected_motif }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script>
        if ($('#itemsTable').length) {
            $('#itemsTable').DataTable({ responsive: true, order: [[0, 'asc']], pageLength: 25 });
        }
        $('#importTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
     </script>
@endsection

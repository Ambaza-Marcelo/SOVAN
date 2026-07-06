
@extends('backend.layouts.master')

@section('title')
@lang('messages.factures') - @lang('messages.admin_panel')
@endsection

@section('styles')
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.factures')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.list')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->

<div class="main-content-inner">
    <div class="row">
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title float-left">@lang('messages.Liste des factures a credit')</h4>
                    <form action="{{ route('admin.exporter-en-excel-credits')}}" method="GET">
                        <p class="float-right mb-2">
                            <button type="submit" value="pdf" class="btn btn-info">@lang('messages.Exporter En Excel')</button>
                        </p>
                        <p class="float-right mb-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="date" name="start_date" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <input type="date" name="end_date" class="form-control">
                                </div>
                            </div>
                        </p>
                    </form><br>
                    <form action="{{ route('admin.facture-globale.client')}}" method="GET">
                        <p class="float-right mb-2">
                            <button type="submit" value="pdf" class="btn btn-success" title="Cliquer pour exporter en PDF">@lang('messages.Facture Globale')</button>
                        </p>
                        <p class="float-right mb-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="start_date">@lang('messages.Date Debut')</label>
                                    <input type="date" name="start_date" class="form-control" id="start_date">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date">@lang('messages.Date Fin')</label>
                                    <input type="date" name="end_date" class="form-control" id="end_date">
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="client_id">@lang('messages.Nom du Client')</label>
                                        <select class="form-control" name="client_id" id="client_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($clients as $client)
                                            <option value="{{$client->id}}">{{$client->customer_name}}</option>
                                        @endforeach
                                    </select>
                                    </div>
                                </div>
                            </div>
                        </p>
                    </form><br>
                    <div class="clearfix"></div>
                    <div class="data-tables">
                        @include('backend.layouts.partials.messages')
                        <table id="dataTable" class="text-center">
                            <thead class="bg-light text-capitalize">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">@lang('messages.Le numéro de la facture')</th>
                                    <th width="10%">@lang('messages.Date de facturation')</th>
                                    <th width="20%">@lang('messages.Serveur')</th>
                                    <th width="20%">@lang('messages.Nom du client')</th>
                                    <th width="20%">@lang('messages.NIF du client')</th>
                                    <th width="20%">@lang('messages.Telephone du client')</th>
                                    <th width="20%">@lang('messages.Mail du client')</th>
                                    <th width="10%">@lang('messages.No Commande')</th>
                                    <th width="10%">@lang('messages.Signature')</th>
                                    <th width="10%">@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($factures as $facture)
                               <tr>
                                    <td>{{ $loop->index+1}}</td>
                                    <td><a href="@if($facture->drink_order_no){{ route('admin.facture.show',$facture->invoice_number) }} @elseif($facture->food_order_no) {{ route('admin.invoice-kitchens.show',$facture->invoice_number) }} @elseif($facture->bartender_order_no) {{ route('admin.bartender-invoices.show',$facture->invoice_number) }} @elseif($facture->barrist_order_no) {{ route('admin.barrist-invoices.show',$facture->invoice_number) }} @else {{ route('admin.booking-invoices.show',$facture->invoice_number) }} @endif">{{ $facture->invoice_number }}</a>&nbsp;
                                    @if($facture->etat_recouvrement == '1')
                                        <span class="badge badge-info" title="MONTANT A RECOUVRER : {{ number_format($facture->montant_total_credit,0,',',' ') }} - MONTANT RESTANT : {{ number_format($facture->reste_credit,0,',',' ') }}">Paiement Partiel</span>
                                    @elseif($facture->etat_recouvrement == '2')
                                        <span class="badge badge-success" title="MONTANT A RECOUVRER : {{ number_format($facture->montant_total_credit,0,',',' ') }} - MONTANT RECOUVRE : {{ number_format($facture->montant_recouvre,0,',',' ') }}">Paiement Total</span>
                                    @else
                                        <span class="badge badge-warning" title="MONTANT A RECOUVRER : {{ number_format($facture->montant_total_credit,0,',',' ') }}">Encours..</span>
                                    @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($facture->invoice_date)->format('d/m/Y') }}</td>
                                    <td>@if($facture->employe_id && $facture->employe){{ $facture->employe->name }} @endif</td>
                                    <td>@if($facture->client_id && $facture->client){{ $facture->client->customer_name }} @else {{ $facture->customer_name }} @endif</td>
                                    <td>@if($facture->client_id && $facture->client){{ $facture->client->customer_TIN }} @else {{ $facture->customer_TIN }} @endif</td>
                                    <td>@if($facture->client_id && $facture->client){{ $facture->client->telephone }} @else {{ $facture->telephone }} @endif</td>
                                    <td>@if($facture->client_id && $facture->client){{ $facture->client->mail }} @else {{ $facture->mail }} @endif</td>
                                    <td>@if($facture->drink_order_no){{ $facture->drink_order_no }}<span class="badge badge-info">@lang('messages.ARTICLES')</span> @elseif($facture->food_order_no){{ $facture->food_order_no }}<span class="badge badge-info">@lang('messages.matières premières')</span> @elseif($facture->barrist_order_no){{ $facture->barrist_order_no }}<span class="badge badge-info">@lang('messages.barrist')</span> @elseif($facture->bartender_order_no){{ $facture->bartender_order_no }}<span class="badge badge-info">@lang('messages.bartender')</span> @else {{ $facture->booking_no }}<span class="badge badge-info">@lang('messages.reservation')</span> @endif</td>
                                    <td>{{ $facture->invoice_signature }}</td>
                                    <td>
                                        @if (Auth::guard('admin')->user()->can('recouvrement.create'))
                                        @if($facture->etat_recouvrement == 1 || $facture->etat_recouvrement == 0 || $facture->reste_credit > 0)
                                         <a href="{{ route('admin.payer-facture.credit', $facture->invoice_number) }}" class="btn btn-info">Payer Credit</a>
                                        @endif
                                        @endif                                       
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
@endsection


@section('scripts')
     <!-- Start datatable js -->
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
     
     <script>
         /*================================
        datatable active
        ==================================*/
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                responsive: true
            });
        }

     </script>
@endsection
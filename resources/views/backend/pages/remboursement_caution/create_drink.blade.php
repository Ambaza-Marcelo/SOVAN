
@extends('backend.layouts.master')

@section('title')
@lang('messages.Nouveau remboursement caution') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Nouveau remboursement caution')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.remboursement-caution.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Nouveau remboursement caution')</span></li>
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
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Nouveau remboursement caution')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.boissons-remboursement-caution.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="invoice_number" value="{{ $invoice_number }}">
                        <input type="hidden" name="etat" value="{{ $data->etat }}">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="employe_id">@lang('messages.Serveur')</label>
                                <select class="form-control" name="employe_id" id="employe_id">
                                <option disabled="disabled">@lang('messages.Merci de choisir un Serveur')</option>
                                <option value="{{$data->employe_id}}" selected="selected">{{$data->employe->name}}</option>
                            </select>
                            </div>
                            <div class="col-md-6">
                                <label for="cn_motif">@lang('messages.Remboursement Caution')</label>
                                <select class="form-control" name="cn_motif" id="cn_motif">
                                <option value="Remboursement Caution" selected>@lang('messages.Remboursement Caution')</option>
                            </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="invoice_type">@lang('messages.Type Facture')</label>
                                <div class="form-group">
                                    <label class="text">F. Normale
                                    <input type="checkbox" disabled name="invoice_type" value="FN" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">facture d'Avoir
                                    <input type="checkbox" disabled name="invoice_type" value="FA" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">R. Caution
                                    <input type="checkbox" checked name="invoice_type" value="RC" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_type">@lang('messages.Type Contribuable')</label>
                                <div class="form-group">
                                    <label class="text">Personne Physique
                                    <input type="checkbox" name="tp_type" value="1" @if($setting->tp_type == '1') checked="checked" @endif class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Société
                                    <input type="checkbox" name="tp_type" value="2" @if($setting->tp_type == '2') checked="checked" @endif class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="vat_taxpayer">@lang('messages.Assujetti à la TVA')</label>
                                <div class="form-group">
                                    <label class="text">Non Assujetti
                                    <input type="checkbox" name="vat_taxpayer" value="0" @if($setting->vat_taxpayer == '0') checked="checked" @endif class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Assujetti
                                    <input type="checkbox" name="vat_taxpayer" value="1" @if($setting->vat_taxpayer == '1') checked="checked" @endif class="form-control">
                                    </label>
                                </div>
                            </div>
                        </div>
                        @if($setting)
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_name">@lang('messages.Nom et Prenom')</label>
                                <input type="text" value="{{ $setting->name }}" name="tp_name" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_TIN">@lang('messages.NIF Contribuable')</label>
                                <input type="text" value="{{ $setting->nif }}" name="tp_TIN" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_trade_number">@lang('messages.RC du Contribuable')</label>
                                <input type="text" value="{{ $setting->rc }}" name="tp_trade_number" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_postal_number">@lang('messages.Boite Postal')</label>
                                <input type="text" value="0000" name="tp_postal_number" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_phone_number">@lang('messages.Tel. du Contribuable')</label>
                                <input type="text" value="{{ $setting->telephone1 }}" name="tp_phone_number" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_address_province">@lang('messages.Province')</label>
                                <input type="text" value="BUJUMBURA" name="tp_address_province" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_address_commune">@lang('messages.Commune')</label>
                                <input type="text" value="{{ $setting->commune }}" name="tp_address_commune" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_address_quartier">@lang('messages.Quartier')</label>
                                <input type="text" value="{{ $setting->quartier }}" name="tp_address_quartier" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_address_avenue">@lang('messages.Avenue')</label>
                                <input type="text" value="{{ $setting->rue }}" name="tp_address_avenue" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_address_rue">@lang('messages.Rue')</label>
                                <input type="text" value="{{ $setting->rue }}" name="tp_address_rue" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_address_number">@lang('messages.Numero')</label>
                                <input type="text" value="0" name="tp_address_number" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="ct_taxpayer">@lang('messages.Assujetti à la taxe de conso.')</label>
                                <div class="form-group">
                                    <label class="text">Non Assujetti
                                    <input type="checkbox" name="ct_taxpayer" value="0" @if($setting->ct_taxpayer == '0') checked="checked" @endif  class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Assujetti
                                    <input type="checkbox" name="ct_taxpayer" value="1" @if($setting->ct_taxpayer == '1') checked="checked" @endif class="form-control">
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tl_taxpayer">@lang('messages.Assujetti au PFL')</label>
                                <div class="form-group">
                                    <label class="text">Non Assujetti
                                    <input type="checkbox" name="tl_taxpayer" value="0" @if($setting->tl_taxpayer == '0') checked="checked" @endif class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Assujetti
                                    <input type="checkbox" name="tl_taxpayer" value="1" @if($setting->tl_taxpayer == '1') checked="checked" @endif class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_fiscal_center">@lang('messages.Centre Fiscale')</label>
                                <div class="form-group">
                                    <label class="text">DGC
                                    <input type="checkbox" name="tp_fiscal_center" value="DGC" @if($setting->tp_fiscal_center == 'DGC') checked="checked" @endif class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">DMC
                                    <input type="checkbox" name="tp_fiscal_center" value="DMC" @if($setting->tp_fiscal_center == 'DMC') checked="checked" @endif class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">DPMC
                                    <input type="checkbox" name="tp_fiscal_center" value="DPMC" @if($setting->tp_fiscal_center == 'DPMC') checked="checked" @endif class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="tp_activity_sector">@lang('messages.Secteur d\'activité')</label>
                                <input type="text" name="tp_activity_sector" class="form-control" value="{{ $setting->tp_activity_sector }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <label for="tp_legal_form">@lang('messages.Forme Juridique')</label>
                                <input type="text" name="tp_legal_form" value="{{ $setting->tp_legal_form }}" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label for="payment_type">@lang('messages.Type de Paiement')</label>
                                <div class="form-group">
                                    <label class="text">Espece
                                    <input type="checkbox" name="payment_type" value="1" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Banque
                                    <input type="checkbox" name="payment_type" value="2" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Credit
                                    <input type="checkbox" name="payment_type" value="3" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">Autres
                                    <input type="checkbox" name="payment_type" value="4" class="form-control">
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label for="invoice_currency">@lang('messages.Type de Monaie')</label>
                                <div class="form-group">
                                    <label class="text">BIF
                                    <input type="checkbox" name="invoice_currency" value="BIF" checked="checked" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">USD
                                    <input type="checkbox" name="invoice_currency" value="USD" class="form-control">
                                    </label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label class="text">EUR
                                    <input type="checkbox" name="invoice_currency" value="EUR" class="form-control">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                <label for="client_id">@lang('messages.Nom du Client')</label>
                                <select class="form-control" name="client_id">
                                <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir client')</option>
                                @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ $data->client_id === $client->id ? 'selected' : '' }} class="form-control">{{ $client->customer_name }}/{{ $client->telephone }}</option>
                                @endforeach
                                </select>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <label for="code_store">@lang('messages.Stock ARTICLES')</label>
                                <select class="form-control" name="code_store" id="code_store" required>
                                <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir un stock')</option>
                                @foreach($drink_small_stores as $drink_small_store)
                                <option value="{{$drink_small_store->code}}" {{ $data->code_store === $drink_small_store->code ? 'selected' : '' }}>{{$drink_small_store->name}}/{{ $drink_small_store->code }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                        <br>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.Article')</th>
                                <th>@lang('messages.Quantite')</th>
                                <th>@lang('messages.Prix Unitaire')</th>
                                <th>@lang('messages.TC')</th>
                                <th>@lang('messages.PFL')</th>
                                <th>@lang('messages.Commande No')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($datas as $data)
                            <tr>  
                                <td><select class="form-control" name="drink_id[]" id="drink_id">
                                <option value="{{ $data->drink_id }}" class="form-control">{{ $data->drink->name }}</option>
                                </select></td>  
                                <td><input type="number" step='any' min='0' value="{{ $data->item_quantity }}" name="item_quantity[]" placeholder="Quantite" class="form-control" @if(Auth::guard('admin')->user()->can('note_credit.create')) @else readonly @endif /></td>  
                                <td><input type="number" step='any' min='0' value="{{ $data->item_price }}" @if(Auth::guard('admin')->user()->can('note_credit.create')) @else readonly @endif name="item_price[]" placeholder="Prix" class="form-control" /></td>
                                <td><input type="number" step='any' min='0' name="item_ct[]" value="0" class="form-control" @if(Auth::guard('admin')->user()->can('note_credit.create')) @else readonly @endif /></td>   
                                <td><input type="number" step='any' min='0' name="item_tl[]" value="0" class="form-control" @if(Auth::guard('admin')->user()->can('note_credit.create')) @else readonly @endif /></td>
                                <td><input type="text" value="{{ $data->drink_order_no }}" name="drink_order_no[]" class="form-control" readonly /></td>
                                <td><button type='button' class='btn btn-danger remove-tr'><i class='fa fa-trash-o' title='Supprimer la ligne' aria-hidden='false'></i></button></td> 
                            </tr> 
                            @endforeach
                        </table> 
                        <div class="col-md-2 pull-right">
                            <input type="text" class="form-control" value="{{ number_format($total_amount,0,',',' ')}}" readonly>
                        </div>
                    <!--
                        <button type="button" name="add" id="add" class="btn btn-success"><i class="fa fa-plus-square" title="Ajouter Plus" aria-hidden="false"></i></button>
                    -->
                        <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.Enregistrer')</button>
                    </form>
                </div>

            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                        "<td>"+
                          "<select class='form-control' name='drink_id[]'"+
                            "<option>@lang('messages.merci de choisir')</option>"+
                             "@foreach($drinks as $drink)"+
                                 "<option value='{{ $drink->id }}'>{{ $drink->name }}</option>"+
                             "@endforeach>"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' step='any' min='0' name='item_quantity[]' placeholder='Quantite' class='form-control' />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' step='any' min='0' name='item_price[]' placeholder='Prix' class='form-control' />"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' step='any' min='0' name='item_ct[]' value='0' class='form-control'/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' step='any' min='0' name='item_tl[]' value='0' class='form-control'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'><i class='fa fa-trash-o' title='Supprimer la ligne' aria-hidden='false'></i></button>"+
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable").append(markup);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 

    //one checked box in checkbox group of invoice type

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('invoice_type'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('invoice_type'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of tp_type

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('tp_type'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('tp_type'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of assujeti a la taxe de consommation

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('ct_taxpayer'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('ct_taxpayer'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of tp_fiscal_center

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('tp_fiscal_center'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('tp_fiscal_center'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of payment_type

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('payment_type'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('payment_type'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of invoice_currency

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of cancelled_invoice vat_taxpayer

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('cancelled_invoice'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('cancelled_invoice'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of vat_taxpayer

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('vat_taxpayer'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('vat_taxpayer'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    //one checked box in checkbox group of invoice_currency

    var group_=(el,callback)=>{
        el.forEach((checkbox)=>{
        callback(checkbox)
        })
    }

    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.onclick=(e)=>{
    group_(document.getElementsByName('invoice_currency'),(item)=>{
    item.checked=false;
    })
    e.target.checked=true;

    }
    })

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };


</script>
@endsection
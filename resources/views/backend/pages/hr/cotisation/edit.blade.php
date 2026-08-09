
@extends('backend.layouts.master')

@section('title')
@lang('Modifier les cotisations') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier les cotisations')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-cotisation.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier les cotisations')</span></li>
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
                    <h4 class="header-title">Modifier les cotisations - Code {{ $cotisation->code ?? '' }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-cotisation.update',$cotisation->code ?? '') }}" method="POST">
                        @method('PUT')
                        @csrf

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="bg-secondary">
                                <th>@lang('Type Cotisation')</th>
                                <th>@lang('Groupe Cotisation')</th>
                                <th>@lang('Pourcentage SB')</th>
                                <th>@lang('Pourcentage Employeur')</th>
                                <th>Etat</th>
                                <th>Action</th>
                            </tr>
                            @foreach ($datas as $data)
                            <tr>  
                                <td> <select class="form-control" name="type_cotisation_id[]">
                                <option disabled>merci de choisir</option>
                                @foreach ($type_cotisations as $type_cotisation)
                                <option value="{{ $type_cotisation->id }}" @selected($data->type_cotisation_id == $type_cotisation->id)>{{ $type_cotisation->name }}</option>
                                @endforeach
                                </select></td>
                                <td> <select class="form-control" name="groupe_cotisation_id[]">
                                <option disabled>merci de choisir</option>
                                @foreach ($groupe_cotisations as $groupe_cotisation)
                                <option value="{{ $groupe_cotisation->id }}" @selected($data->groupe_cotisation_id == $groupe_cotisation->id)>{{ $groupe_cotisation->designation }}</option>
                                @endforeach
                                </select></td>
                                <td><input type="number" name="pourcentage_sb[]" value="{{ $data->pourcentage_sb }}" class="form-control" step="any" min="0"/></td>     
                                <td><input type="number" name="pourcentage_employeur[]" value="{{ $data->pourcentage_employeur }}" class="form-control" step="any" min="0"/></td>     
                                <td><input type="text" name="etat[]" value="{{ $data->etat }}" class="form-control"/></td>     
                                <td><button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button></td>  
                            </tr>  
                            @endforeach
                        </table> 
                        <button type="button" name="add" id="add" class="btn btn-success">@lang('Ajouter')</button>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
    <script type="text/javascript">
    $("#add").click(function(){

         var markup = "<tr>"+
                      "<td>"+
                         "<select class='form-control' name='type_cotisation_id[]'>"+
                            "<option disabled selected>merci de choisir</option>"+
                             "@foreach($type_cotisations as $type_cotisation)"+
                                 "<option value='{{ $type_cotisation->id }}'>{{ $type_cotisation->name }}</option>"+
                             "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                         "<select class='form-control' name='groupe_cotisation_id[]'>"+
                            "<option disabled selected>merci de choisir</option>"+
                             "@foreach($groupe_cotisations as $groupe_cotisation)"+
                                 "<option value='{{ $groupe_cotisation->id }}'>{{ $groupe_cotisation->designation }}</option>"+
                             "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='pourcentage_sb[]' placeholder='% SB' class='form-control' step='any' min='0'/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='pourcentage_employeur[]' placeholder='% Employeur' class='form-control' step='any' min='0'/>"+
                        "</td>"+
                        "<td>"+
                          "<input type='text' name='etat[]' placeholder='Etat' class='form-control'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";

        $("#dynamicTable").append(markup);
    });

    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 

</script>
@endsection

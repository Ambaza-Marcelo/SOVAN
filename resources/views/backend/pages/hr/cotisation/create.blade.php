
@extends('backend.layouts.master')

@section('title')
@lang('Nouvelle cotisation') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Nouvelle cotisation')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-cotisation.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.new')</span></li>
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
                    <h4 class="header-title">Nouvelle cotisation</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-cotisation.store') }}" method="POST">
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
                            <tr>  
                                <td> <select class="form-control" name="type_cotisation_id[]">
                                <option disabled selected>merci de choisir</option>
                                @foreach ($type_cotisations as $type_cotisation)
                                <option value="{{ $type_cotisation->id }}">{{ $type_cotisation->name }}</option>
                                @endforeach
                                </select></td>
                                <td> <select class="form-control" name="groupe_cotisation_id[]">
                                <option disabled selected>merci de choisir</option>
                                @foreach ($groupe_cotisations as $groupe_cotisation)
                                <option value="{{ $groupe_cotisation->id }}">{{ $groupe_cotisation->designation }}</option>
                                @endforeach
                                </select></td>
                                <td><input type="number" name="pourcentage_sb[]" placeholder="% SB" class="form-control" step="any" min="0"/></td>     
                                <td><input type="number" name="pourcentage_employeur[]" placeholder="% Employeur" class="form-control" step="any" min="0"/></td>     
                                <td><input type="text" name="etat[]" placeholder="Etat" class="form-control" value="actif"/></td>     
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('Ajouter')</button></td>  
                            </tr>  
                        </table> 
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
    <script type="text/javascript">
    $("#dynamicTable").on('click', '#add', function(){

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
                          "<input type='text' name='etat[]' placeholder='Etat' class='form-control' value='actif'/>"+
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

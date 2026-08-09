
@extends('backend.layouts.master')

@section('title')
@lang('Modifier les indemnités') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier les indemnités')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-indemnites.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier les indemnités')</span></li>
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
                    <h4 class="header-title">Modifier les indemnités - Code {{ $indemnite->code ?? '' }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-indemnites.update',$indemnite->code ?? '') }}" method="POST">
                        @method('PUT')
                        @csrf

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="bg-secondary">
                                <th>@lang('Type Indemnité')</th>
                                <th>@lang('Groupe Indemnité')</th>
                                <th>@lang('Pourcentage SB')</th>
                                <th>Action</th>
                            </tr>
                            @foreach ($datas as $data)
                            <tr>  
                                <td> <select class="form-control" name="type_indemnite_id[]">
                                <option disabled>merci de choisir</option>
                                @foreach ($type_indemnites as $type_indemnite)
                                <option value="{{ $type_indemnite->id }}" @selected($data->type_indemnite_id == $type_indemnite->id)>{{ $type_indemnite->name }}</option>
                                @endforeach
                                </select></td>
                                <td> <select class="form-control" name="groupe_indemnite_id[]">
                                <option disabled>merci de choisir</option>
                                @foreach ($groupe_indemnites as $groupe_indemnite)
                                <option value="{{ $groupe_indemnite->id }}" @selected($data->groupe_indemnite_id == $groupe_indemnite->id)>{{ $groupe_indemnite->designation }}</option>
                                @endforeach
                                </select></td>
                                <td><input type="number" name="pourcentage_sb[]" value="{{ $data->pourcentage_sb }}" class="form-control" step="any" min="0"/></td>     
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
                         "<select class='form-control' name='type_indemnite_id[]'>"+
                            "<option disabled selected>merci de choisir</option>"+
                             "@foreach($type_indemnites as $type_indemnite)"+
                                 "<option value='{{ $type_indemnite->id }}'>{{ $type_indemnite->name }}</option>"+
                             "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                         "<select class='form-control' name='groupe_indemnite_id[]'>"+
                            "<option disabled selected>merci de choisir</option>"+
                             "@foreach($groupe_indemnites as $groupe_indemnite)"+
                                 "<option value='{{ $groupe_indemnite->id }}'>{{ $groupe_indemnite->designation }}</option>"+
                             "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='pourcentage_sb[]' placeholder='Entrer % SB' class='form-control' step='any' min='0'/>"+
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

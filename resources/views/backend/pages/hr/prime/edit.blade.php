
@extends('backend.layouts.master')

@section('title')
@lang('Modifier la prime') - @lang('messages.admin_panel')
@endsection

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier la prime')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.hr-primes.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('Modifier la prime')</span></li>
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
                    <h4 class="header-title">Modifier la prime</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-primes.update',$prime->id) }}" method="POST">
                        @method('PUT')
                        @csrf

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="bg-secondary">
                                <th>@lang('Type Prime')</th>
                                <th>@lang('Pourcentage Prime')</th>
                                <th>Action</th>
                            </tr>
                            @foreach ($datas as $data)
                            <tr>  
                                <td> <select class="form-control" name="type_prime_id[]">
                                <option disabled>merci de choisir</option>
                                @foreach ($type_primes as $type_prime)
                                <option value="{{ $type_prime->id }}" @selected($data->type_prime_id == $type_prime->id)>{{ $type_prime->name }}</option>
                                @endforeach
                                </select></td>
                                <td><input type="number" name="pourcentage_prime[]" value="{{ $data->pourcentage_prime }}" class="form-control" step="any" min="0"/></td>     
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
                         "<select class='form-control' name='type_prime_id[]'>"+
                            "<option disabled selected>merci de choisir</option>"+
                             "@foreach($type_primes as $type_prime)"+
                                 "<option value='{{ $type_prime->id }}'>{{ $type_prime->name }}</option>"+
                             "@endforeach"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='pourcentage_prime[]' placeholder='Entrer % prime' class='form-control' step='any' min='0'/>"+
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

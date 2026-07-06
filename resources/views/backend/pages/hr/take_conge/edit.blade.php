
@extends('backend.layouts.master')

@section('title')
@lang('messages.Modifier le congé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Modifier le congé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.primes.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Modifier le congé')</span></li>
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
                    <h4 class="header-title">@lang('messages.Modifier le congé')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.primes.store') }}" method="POST">
                        @csrf
                    <div class="row">
                        <div class="col-sm-6" id="dynamicDiv">
                        <div class="form-group">
                            <label for="contrat_id">@lang('messages.Employé')</label>
                            <select class="form-control" name="contrat_id">
                                <option disabled selected>@lang('messages.Merci de choisir')</option>
                                @foreach($contrats as $contrat)
                                <option value="{{ $contrat->id }}">{{ $contrat->employe->firstname}}&nbsp;{{ $contrat->employe->lastname}}/{{ $contrat->employe->position->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="bg-secondary">
                                <th>@lang('messages.Type Prime')</th>
                                <th>@lang('messages.Pourcentage Prime')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            <tr>  
                                <td> <select class="form-control" name="type_prime_id[]" id="type_prime_id">
                                <option disabled="disabled" selected="selected">@lang('messages.merci de choisir')</option>
                                @foreach ($type_primes as $type_prime)
                                <option value="{{ $type_prime->id }}" class="form-control">{{ $type_prime->name }}</option>
                                @endforeach
                                </select></td> 
                                <td><input type="number" name="pourcentage_prime[]" placeholder="{{ __('messages.Entrer % prime') }}" class="form-control" step="any" min="0"/></td>     
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.Ajouter')</button></td>  
                            </tr>  
                        </table> 
                        <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.Ajouter')</button>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var primeI18n = { enterPercent: @json(__('messages.Entrer % prime')), delete: @json(__('messages.delete')) };
         var markup = "<tr'>"+
                      "<td>"+
                         "<select class='form-control' name='type_prime_id[]'"+
                            "<option>@lang('messages.merci de choisir')</option>"+
                             "@foreach($type_primes as $type_prime)"+
                                 "<option value='{{ $type_prime->id }}'>{{ $type_prime->name }}</option>"+
                             "@endforeach>"+
                          "</select>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='pourcentage_prime[]' placeholder='" + primeI18n.enterPercent + "' class='form-control' step='any' min='0'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>" + primeI18n.delete + "</button>"+
                        "</td>"+
                    "</tr>";
   
        $("#dynamicTable").append(markup);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 

</script>
@endsection

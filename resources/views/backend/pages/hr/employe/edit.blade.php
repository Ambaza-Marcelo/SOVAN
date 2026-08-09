
@extends('backend.layouts.master')

@section('title')
@lang('Modifier un Employé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Modifier un Employé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('Modifier un Employé')</span></li>
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
                    <h4 class="header-title">Modifier un Employé</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-employes.update',$employe->id) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="firstname">Nom *<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="firstname" value="{{ $employe->firstname }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="lastname">Prenom *<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="lastname" value="{{ $employe->lastname }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="company_id">Entreprise *<span class="text-danger"></span></label>
                                        <select class="form-control" name="company_id" required id="company_id">
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ $employe->company_id == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="type_contrat">Type Contrat<span class="text-danger"></span></label>
                                        <select id="type_contrat" class="form-control" name="type_contrat" required>
                                            <option disabled selected>Merci de choisir</option>
                                            <option value="CDI" {{ $employe->type_contrat == 'CDI' ? 'selected' : '' }}>Indéterminé</option>
                                            <option value="CDD" {{ $employe->type_contrat == 'CDD' ? 'selected' : '' }}>Déterminé</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="date_debut">Date d'Entrée *<span class="text-danger"></span></label>
                                        <input autofocus type="datetime-local" class="form-control" name="date_debut" value="{{ $employe->date_debut }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4" id="dynamicTypeContrat">
                                    <div class="form-group has-feedback">
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="somme_salaire_base">Salaire de Base *<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="somme_salaire_base" value="{{ $employe->somme_salaire_base }}" required min="0" step="any">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="prime_fonction">Prime de fonction *<span class="text-danger"></span></label>
                                        <input autofocus type="number" class="form-control" name="prime_fonction" value="{{ $employe->prime_fonction }}" required min="0" minlength="3" maxlength="10">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="banque_id">Banque *<span class="text-danger"></span></label>
                                        <select class="form-control" name="banque_id" required id="banque_id">
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($banques as $banque)
                                            <option value="{{ $banque->id }}" {{ $employe->banque_id == $banque->id ? 'selected' : '' }}>{{ $banque->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="numero_compte">Numero de Compte<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="numero_compte" value="{{ $employe->numero_compte }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="phone_no">Telephone<span class="text-danger"></span></label>
                                        <input autofocus type="tel" class="form-control" name="phone_no" value="{{ $employe->phone_no }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="grade_id">Grade *<span class="text-danger"></span></label>
                                        <select class="form-control" name="grade_id" required>
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($grades as $grade)
                                            <option value="{{ $grade->id }}" {{ $employe->grade_id == $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                    <label for="departement_id">Departement *<span class="text-danger"></span></label>
                                        <select class="form-control" name="departement_id" required>
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($departements as $departement)
                                            <option value="{{ $departement->id }}" {{ $employe->departement_id == $departement->id ? 'selected' : '' }}>{{ $departement->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                    <label for="service_id">Service *<span class="text-danger"></span></label>
                                        <select class="form-control" name="service_id" required>
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($services as $service)
                                            <option value="{{ $service->id }}" {{ $employe->service_id == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                    <label for="fonction_id">Fonction *<span class="text-danger"></span></label>
                                        <select class="form-control" name="fonction_id" required>
                                            <option disabled selected>Merci de choisir</option>
                                            @foreach($fonctions as $fonction)
                                            <option value="{{ $fonction->id }}" {{ $employe->fonction_id == $fonction->id ? 'selected' : '' }}>{{ $fonction->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="pays">Pays(Lieu Naissance) *<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="pays" value="{{ $employe->pays }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="province">Province(Lieu Naissance) *<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="province" value="{{ $employe->province }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group has-feedback">
                                        <label for="commune">Commune(Lieu Naissance)<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="commune" value="{{ $employe->commune }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="zone">Zone(Lieu Naissance)<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="zone" value="{{ $employe->zone }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="quartier">Quartier(Lieu Naissance)<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="quartier" value="{{ $employe->quartier }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="mail">E-mail<span class="text-danger"></span></label>
                                        <input autofocus type="mail" class="form-control" name="mail" value="{{ $employe->mail }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                    <label for="gender">Genre *<span class="text-danger"></span></label>
                                        <select class="form-control" name="gender" required>
                                            <option disabled selected>Merci de choisir</option>
                                            <option value="1" {{ $employe->gender == '1' ? 'selected' : '' }}>Masculin</option>
                                            <option value="2" {{ $employe->gender == '2' ? 'selected' : '' }}>Feminin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for=" birthdate">Date Naissance *<span class="text-danger"></span></label>
                                        <input type="date" class="form-control" name=" birthdate" required value="{{ $employe->birthdate }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="cni">CNI *<span class="text-danger"></span></label>
                                        <input autofocus type="text" class="form-control" name="cni" value="{{ $employe->cni }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="bloodgroup">Groupe Sanguin<span class="text-danger"></span></label>
                                        <select class="form-control" name="bloodgroup">
                                            <option disabled selected>Merci de choisir</option>
                                            <option value="A+" {{ $employe->bloodgroup == 'A+' ? 'selected' : '' }}>A+</option>
                                            <option value="A-" {{ $employe->bloodgroup == 'A-' ? 'selected' : '' }}>A-</option>
                                            <option value="B+" {{ $employe->bloodgroup == 'B+' ? 'selected' : '' }}>B+</option>
                                            <option value="B-" {{ $employe->bloodgroup == 'B-' ? 'selected' : '' }}>B-</option>
                                            <option value="O+" {{ $employe->bloodgroup == 'O+' ? 'selected' : '' }}>O+</option>
                                            <option value="O-" {{ $employe->bloodgroup == 'O-' ? 'selected' : '' }}>O-</option>
                                            <option value="AB+" {{ $employe->bloodgroup == 'AB+' ? 'selected' : '' }}>AB+</option>
                                            <option value="AB-" {{ $employe->bloodgroup == 'AB-' ? 'selected' : '' }}>AB-</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="quartier_residence_actuel">Residence Actuel *<span class="text-danger"></span></label>
                                        <input type="text" class="form-control" name="quartier_residence_actuel" value="{{ $employe->quartier_residence_actuel }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="statut_matrimonial">Etat Civil<span class="text-danger"></span></label>
                                        <select id="statut_matrimonial" class="form-control" name="statut_matrimonial" required>
                                            <option disabled selected>Merci de choisir</option>
                                            <option value="1" {{ $employe->statut_matrimonial == '1' ? 'selected' : '' }}>Marié(e)</option>
                                            <option value="2" {{ $employe->statut_matrimonial == '2' ? 'selected' : '' }}>Divorcé(e)</option>
                                            <option value="3" {{ $employe->statut_matrimonial == '3' ? 'selected' : '' }}>veuf(ve)</option>
                                            <option value="4" {{ $employe->statut_matrimonial == '4' ? 'selected' : '' }}>Célibtaire</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="image">Image<span class="text-danger"></span></label>
                                        <input autofocus type="file" class="form-control" name="image" accept="image/*" id="image_input">
                                        @if($employe->image)
                                            <div class="mt-2">
                                                <small class="form-text text-muted">Image actuelle:</small>
                                                <div class="d-flex align-items-center mt-1">
                                                    <a href="{{ route('admin.hr-employes.image', ['id' => $employe->id, 'filename' => $employe->image]) }}" target="_blank" class="mr-2">{{ $employe->image }}</a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-image-btn" data-employe-id="{{ $employe->id }}" title="Supprimer l'image">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="delete_image" id="delete_image" value="0">
                                            </div>
                                        @endif
                                        <small class="form-text text-muted">Format accepté: JPG, PNG, GIF (optionnel)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" style="position: relative;">
                                        <label for="account_search">@lang('Compte Avance') *</label>
                                        <input type="text" class="form-control" id="account_search" placeholder="Rechercher un compte..." autocomplete="off" value="@if($employe->account){{ $employe->account->account_number }} - {{ $employe->account->account_name }}@endif" required>
                                        <input type="hidden" name="account_id" id="account_id" value="{{ $employe->account_id }}" required>
                                        <ul id="account_autocomplete_list" class="list-group" style="position: absolute; z-index: 1000; max-height: 200px; overflow-y: auto; display: none; width: 100%;"></ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" style="position: relative;">
                                        <label for="cash_out_account_search">@lang('Compte Sortie de fonds') *</label>
                                        <input type="text" class="form-control" id="cash_out_account_search" placeholder="Rechercher un compte..." autocomplete="off" value="@if($employe->cashOutAccount){{ $employe->cashOutAccount->account_number }} - {{ $employe->cashOutAccount->account_name }}@endif" required>
                                        <input type="hidden" name="cash_out_account_id" id="cash_out_account_id" value="{{ $employe->cash_out_account_id }}" required>
                                        <ul id="cash_out_account_autocomplete_list" class="list-group" style="position: absolute; z-index: 1000; max-height: 200px; overflow-y: auto; display: none; width: 100%;"></ul>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group has-feedback">
                                        <label for="document">Documents<span class="text-danger"></span></label>
                                        <input type="file" class="form-control" name="document[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        @if($employe->document)
                                            @php
                                                $documents = json_decode($employe->document, true);
                                            @endphp
                                            @if($documents && is_array($documents))
                                                <div class="mt-2">
                                                    <small class="form-text text-muted">Documents actuels:</small>
                                                    <ul class="list-unstyled" id="documents-list">
                                                        @foreach($documents as $index => $doc)
                                                            <li class="mb-2 d-flex align-items-center" data-document-name="{{ $doc }}">
                                                                <a href="{{ route('admin.hr-employes.document', ['id' => $employe->id, 'filename' => $doc]) }}" target="_blank" class="mr-2 flex-grow-1" style="word-break: break-word;">{{ $doc }}</a>
                                                                <button type="button" class="btn btn-sm btn-danger delete-document-btn" data-document-name="{{ $doc }}" title="Supprimer ce document">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <input type="hidden" name="delete_documents" id="delete_documents" value="">
                                                </div>
                                            @endif
                                        @endif
                                        <small class="form-text text-muted">Vous pouvez sélectionner plusieurs fichiers (CV, Contrat, CNI/Passport, Diplôme). Formats acceptés: PDF, DOC, DOCX, JPG, PNG (optionnel)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="dynamicSelect">

                            </div>
                        <button type="submit" class="btn btn-primary mt-4 pr-4 pl-4">Modifier</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- data table end -->
        
    </div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    
    // Account autocomplete
    var accountSearchTimeout;
    $('#account_search').on('input', function() {
        var searchTerm = $(this).val();
        var $list = $('#account_autocomplete_list');
        
        clearTimeout(accountSearchTimeout);
        
        if (searchTerm.length < 2) {
            $list.hide().empty();
            // Don't clear account_id if search term is cleared, keep the existing value
            return;
        }
        
        accountSearchTimeout = setTimeout(function() {
            $.ajax({
                url: "{{ route('admin.hr-employes.search-accounts') }}",
                type: "GET",
                data: {
                    term: searchTerm,
                    account_prefix: '42'
                },
                dataType: 'json',
                success: function(data) {
                    $list.empty();
                    
                    // Handle both array and object with results property
                    var accounts = Array.isArray(data) ? data : (data.results || []);
                    
                    if (accounts.length > 0) {
                        accounts.forEach(function(account) {
                            var accountLabel = account.label || (account.account_number + ' - ' + account.account_name);
                            var $item = $('<li class="list-group-item" style="cursor: pointer; padding: 8px 12px;"></li>')
                                .html('<strong>' + accountLabel.replace(/'/g, "&#39;") + '</strong>')
                                .data('account', account)
                                .on('click', function() {
                                    var selected = $(this).data('account');
                                    var selectedLabel = selected.label || (selected.account_number + ' - ' + selected.account_name);
                                    $('#account_search').val(selectedLabel);
                                    $('#account_id').val(selected.id);
                                    $list.hide().empty();
                                })
                                .on('mouseenter', function() {
                                    $(this).css('background-color', '#f8f9fa');
                                })
                                .on('mouseleave', function() {
                                    $(this).css('background-color', 'white');
                                });
                            $list.append($item);
                        });
                        $list.show();
                    } else {
                        $list.append('<li class="list-group-item" style="padding: 10px; color: #999;">Aucun compte trouvé</li>');
                        $list.show();
                    }
                },
                error: function() {
                    $list.hide().empty();
                }
            });
        }, 300); // Debounce 300ms
    });

    // Cash Out Account autocomplete (Compte Sortie de fonds)
    var cashOutAccountSearchTimeout;
    $('#cash_out_account_search').on('input', function() {
        var searchTerm = $(this).val();
        var $list = $('#cash_out_account_autocomplete_list');
        
        clearTimeout(cashOutAccountSearchTimeout);
        
        if (searchTerm.length < 2) {
            $list.hide().empty();
            // Don't clear cash_out_account_id if search term is cleared, keep the existing value
            return;
        }
        
        cashOutAccountSearchTimeout = setTimeout(function() {
            $.ajax({
                url: "{{ route('admin.hr-employes.search-accounts') }}",
                type: "GET",
                data: {
                    term: searchTerm,
                    account_prefix: '46'
                },
                dataType: 'json',
                success: function(data) {
                    $list.empty();
                    
                    // Handle both array and object with results property
                    var accounts = Array.isArray(data) ? data : (data.results || []);
                    
                    if (accounts.length > 0) {
                        accounts.forEach(function(account) {
                            var accountLabel = account.label || (account.account_number + ' - ' + account.account_name);
                            var $item = $('<li class="list-group-item" style="cursor: pointer; padding: 8px 12px;"></li>')
                                .html('<strong>' + accountLabel.replace(/'/g, "&#39;") + '</strong>')
                                .data('account', account)
                                .on('click', function() {
                                    var selected = $(this).data('account');
                                    var selectedLabel = selected.label || (selected.account_number + ' - ' + selected.account_name);
                                    $('#cash_out_account_search').val(selectedLabel);
                                    $('#cash_out_account_id').val(selected.id);
                                    $list.hide().empty();
                                })
                                .on('mouseenter', function() {
                                    $(this).css('background-color', '#f8f9fa');
                                })
                                .on('mouseleave', function() {
                                    $(this).css('background-color', 'white');
                                });
                            $list.append($item);
                        });
                        $list.show();
                    } else {
                        $list.append('<li class="list-group-item" style="padding: 10px; color: #999;">Aucun compte trouvé</li>');
                        $list.show();
                    }
                },
                error: function() {
                    $list.hide().empty();
                }
            });
        }, 300); // Debounce 300ms
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#account_search, #account_autocomplete_list').length) {
            $('#account_autocomplete_list').hide();
        }
        if (!$(e.target).closest('#cash_out_account_search, #cash_out_account_autocomplete_list').length) {
            $('#cash_out_account_autocomplete_list').hide();
        }
    });

    // Keyboard navigation for account autocomplete (Compte Avance)
    $('#account_search').on('keydown', function(e) {
        var $list = $('#account_autocomplete_list');
        var $items = $list.find('.list-group-item');
        var $highlighted = $items.filter('.highlighted');
        
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.removeClass('highlighted');
                var $next = $highlighted.next();
                if ($next.length) {
                    $next.addClass('highlighted');
                } else {
                    $items.first().addClass('highlighted');
                }
            } else {
                $items.first().addClass('highlighted');
            }
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.removeClass('highlighted');
                var $prev = $highlighted.prev();
                if ($prev.length) {
                    $prev.addClass('highlighted');
                } else {
                    $items.last().addClass('highlighted');
                }
            } else {
                $items.last().addClass('highlighted');
            }
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.trigger('click');
            }
        } else if (e.keyCode === 27) { // Escape
            $list.hide();
        }
    });

    // Keyboard navigation for cash out account autocomplete (Compte Sortie de fonds)
    $('#cash_out_account_search').on('keydown', function(e) {
        var $list = $('#cash_out_account_autocomplete_list');
        var $items = $list.find('.list-group-item');
        var $highlighted = $items.filter('.highlighted');
        
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.removeClass('highlighted');
                var $next = $highlighted.next();
                if ($next.length) {
                    $next.addClass('highlighted');
                } else {
                    $items.first().addClass('highlighted');
                }
            } else {
                $items.first().addClass('highlighted');
            }
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.removeClass('highlighted');
                var $prev = $highlighted.prev();
                if ($prev.length) {
                    $prev.addClass('highlighted');
                } else {
                    $items.last().addClass('highlighted');
                }
            } else {
                $items.last().addClass('highlighted');
            }
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.trigger('click');
            }
        } else if (e.keyCode === 27) { // Escape
            $list.hide();
        }
    });
    
    // Statut matrimonial dynamic select with pre-filled values
    $('#statut_matrimonial').change(function () { 
        $("#dynamicSelect").empty(); // Clear previous content
        
        var statutValue = $(this).val();
        var wifeValue = "{{ $employe->wife_or_husband ?? '' }}";
        var childrenValue = "{{ $employe->children_number ?? '' }}";
        
        if (statutValue === '1' || statutValue === '2'){
            var wife_name = "<div class='col-md-6'>"+
                            "<div class='form-group'>"+
                                "<label for='wife_or_husband'>Marié(e)</label>"+
                                "<input type='text' class='form-control' id='wife_or_husband' name='wife_or_husband' placeholder='Nom Marié(e)' value='" + wifeValue + "'>"+
                                "</div>"+
                            "</div>";
            var ofchild = "<div class='col-md-6'>"+
                            "<div class='form-group'>"+
                                "<label for='children_number'>Nombre Enfants</label>"+
                                "<input type='number' class='form-control' id='children_number' name='children_number' placeholder='Nombre Enfants' min='0' value='" + childrenValue + "'>"+
                                "</div>"+
                            "</div>";
            
            $("#dynamicSelect").append(wife_name, ofchild);
        }

        if (statutValue === '3'){
            var ofchild = "<div class='col-md-6'>"+
                            "<div class='form-group'>"+
                                "<label for='children_number'>Nombre Enfants</label>"+
                                "<input type='number' class='form-control' id='children_number' name='children_number' placeholder='Nombre Enfants' min='0' value='" + childrenValue + "'>"+
                                "</div>"+
                            "</div>";
            
            $("#dynamicSelect").append(ofchild);
        }
    });

    // Type contrat dynamic select with pre-filled values
    $('#type_contrat').change(function () { 
        $("#dynamicTypeContrat").empty(); // Clear previous content
        
        var dateFinValue = "{{ $employe->date_fin ?? '' }}";
        
        if ($(this).val() === 'CDD'){
            var type = "<div class='form-group'>"+
                            "<label for='date_fin'>Date de Fin *</label>"+
                            "<input type='date' class='form-control' name='date_fin' required value='" + dateFinValue + "'>"+
                        "</div>";
            
            $("#dynamicTypeContrat").append(type);
        }
    });

    // Trigger change on page load to populate dynamic fields
    $(document).ready(function() {
        $('#statut_matrimonial').trigger('change');
        $('#type_contrat').trigger('change');
    });

    // Delete image functionality
    $('.delete-image-btn').on('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
            $('#delete_image').val('1');
            $(this).closest('.mt-2').find('a').css('text-decoration', 'line-through').css('opacity', '0.5');
            $(this).prop('disabled', true);
        }
    });

    // Delete document functionality
    var deletedDocuments = [];
    $('.delete-document-btn').on('click', function() {
        var documentName = $(this).data('document-name');
        if (confirm('Êtes-vous sûr de vouloir supprimer ce document : ' + documentName + ' ?')) {
            deletedDocuments.push(documentName);
            $('#delete_documents').val(JSON.stringify(deletedDocuments));
            $(this).closest('li').find('a').css('text-decoration', 'line-through').css('opacity', '0.5');
            $(this).prop('disabled', true);
        }
    });

</script>

<style>
#account_autocomplete_list {
    background-color: white;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    margin-top: 2px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#account_autocomplete_list .list-group-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

#account_autocomplete_list .list-group-item:last-child {
    border-bottom: none;
}

#account_autocomplete_list .list-group-item.highlighted,
#cash_out_account_autocomplete_list .list-group-item.highlighted {
    background-color: #e9ecef !important;
}
</style>
@endsection
@extends('backend.layouts.master')

@section('title')
@lang('Détails Employé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .info-value {
        color: #212529;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 15px;
        border-left: 3px solid #007bff;
    }
    .nav-tabs .nav-link.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        padding: 20px;
        background-color: white;
        min-height: 400px;
    }
    .employee-image {
        max-width: 200px;
        max-height: 200px;
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 3px solid #dee2e6;
    }
    .employee-image-placeholder {
        width: 200px;
        height: 200px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 3px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .document-list {
        list-style: none;
        padding: 0;
    }
    .document-list li {
        padding: 12px;
        margin-bottom: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #007bff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .document-list li:hover {
        background-color: #e9ecef;
    }
    .document-list li .document-info {
        display: flex;
        align-items: center;
        flex: 1;
    }
    .document-list li .document-icon {
        font-size: 24px;
        margin-right: 12px;
        color: #6c757d;
    }
    .document-list li .document-name {
        color: #212529;
        font-weight: 500;
        flex: 1;
    }
    .document-list li .document-actions {
        display: flex;
        gap: 8px;
    }
    .document-list li .btn-view,
    .document-list li .btn-download {
        color: white;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 4px;
        transition: all 0.3s;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .document-list li .btn-view {
        background-color: #17a2b8;
    }
    .document-list li .btn-view:hover {
        background-color: #138496;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .document-list li .btn-download {
        background-color: #28a745;
    }
    .document-list li .btn-download:hover {
        background-color: #218838;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .tab-content {
            border: none;
            padding: 0;
        }
        .nav-tabs {
            display: none;
        }
    }
</style>
@endsection

@section('admin-content')

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Détails Employé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('Détails Employé')</span></li>
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
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                        <h4 class="header-title">Détails de l'Employé</h4>
<div>
                            @if (Auth::guard('admin')->user()->can('hr_employe.edit'))
                                <a href="{{ route('admin.hr-employes.edit', $data->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> @lang('messages.edit')
                                </a>
                            @endif
                            <a href="{{ route('admin.hr-employes.badge', $data->id) }}" target="_blank" class="btn btn-success">
                                <i class="fa fa-id-card"></i> Badge
                            </a>
                            <button onclick="window.print()" class="btn btn-info">
                                <i class="fa fa-print"></i> Imprimer
                            </button>
                            <a href="{{ route('admin.hr-employes.index', $data->company_id) }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> @lang('Back to List')
                            </a>
                </div>
                    </div>

                    <!-- Employee Image and Basic Info -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            @if($data->image && isset($imageUrl) && $imageUrl)
                                <img src="{{ $imageUrl }}" alt="Photo de {{ $data->firstname }} {{ $data->lastname }}" class="employee-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="employee-image-placeholder" style="display: none;">
                                    <div class="text-center">
                                        <i class="fa fa-user fa-5x"></i>
                                        <p class="mt-2 mb-0">Photo</p>
                                    </div>
                                </div>
                            @else
                                <div class="employee-image-placeholder">
                                    <div class="text-center">
                                        <i class="fa fa-user fa-5x"></i>
                                        <p class="mt-2 mb-0">Photo</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h3>{{ $data->firstname }} {{ $data->lastname }}</h3>
                            <p class="text-muted">Matricule: <strong>{{ $data->matricule_no }}</strong></p>
                            @if($data->departement)
                                <p class="text-muted">Département: <strong>{{ $data->departement->name }}</strong></p>
                            @endif
                            @if($data->service)
                                <p class="text-muted">Service: <strong>{{ $data->service->name }}</strong></p>
                            @endif
                    </div>
                    </div>

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs no-print" id="employeTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab">
                                <i class="fa fa-info-circle"></i> Informations Personnelles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="professional-tab" data-toggle="tab" href="#professional" role="tab">
                                <i class="fa fa-briefcase"></i> Informations Professionnelles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="remuneration-tab" data-toggle="tab" href="#remuneration" role="tab">
                                <i class="fa fa-money-bill"></i> Rémunération
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab">
                                <i class="fa fa-file"></i> Documents
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Nom:</label>
                                        <div class="info-value">{{ $data->firstname }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Prénom:</label>
                                        <div class="info-value">{{ $data->lastname }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Numéro Matricule:</label>
                                        <div class="info-value">{{ $data->matricule_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">CNI:</label>
                                        <div class="info-value">{{ $data->cni }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date de Naissance:</label>
                                        <div class="info-value">
                                            @if($data->birthdate)
                                                {{ \Carbon\Carbon::parse($data->birthdate)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Genre:</label>
                                        <div class="info-value">
                                            @if($data->gender == 1)
                                                Masculin
                                            @elseif($data->gender == 2)
                                                Féminin
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Groupe Sanguin:</label>
                                        <div class="info-value">{{ $data->bloodgroup ?: 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">État Civil:</label>
                                        <div class="info-value">
                                            @if($data->statut_matrimonial == 1)
                                                Marié(e)
                                            @elseif($data->statut_matrimonial == 2)
                                                Divorcé(e)
                                            @elseif($data->statut_matrimonial == 3)
                                                Veuf(ve)
                                            @elseif($data->statut_matrimonial == 4)
                                                Célibataire
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Nombre d'Enfants:</label>
                                        <div class="info-value">{{ $data->children_number ?: '0' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Conjoint(e):</label>
                                        <div class="info-value">{{ $data->wife_or_husband ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Téléphone:</label>
                                        <div class="info-value">{{ $data->phone_no ?: 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Email:</label>
                                        <div class="info-value">{{ $data->mail ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">Lieu de Naissance:</label>
                                        <div class="info-value">
                                            {{ $data->pays }}{{ $data->province ? ', ' . $data->province : '' }}{{ $data->commune ? ', ' . $data->commune : '' }}{{ $data->zone ? ', ' . $data->zone : '' }}{{ $data->quartier ? ', ' . $data->quartier : '' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="info-label">Résidence Actuelle:</label>
                                        <div class="info-value">
                                            {{ $data->quartier_residence_actuel }}{{ $data->zone_residence_actuel ? ', ' . $data->zone_residence_actuel : '' }}{{ $data->commune_residence_actuel ? ', ' . $data->commune_residence_actuel : '' }}{{ $data->province_residence_actuel ? ', ' . $data->province_residence_actuel : '' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information Tab -->
                        <div class="tab-pane fade" id="professional" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date d'Entrée:</label>
                                        <div class="info-value">
                                            @if($data->date_debut)
                                                {{ \Carbon\Carbon::parse($data->date_debut)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Date de Fin:</label>
                                        <div class="info-value">
                                            @if($data->date_fin)
                                                {{ \Carbon\Carbon::parse($data->date_fin)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Type de Contrat:</label>
                                        <div class="info-value">{{ $data->type_contrat ?: 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Département:</label>
                                        <div class="info-value">
                                            @if($data->departement)
                                                {{ $data->departement->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Service:</label>
                                        <div class="info-value">
                                            @if($data->service)
                                                {{ $data->service->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Fonction:</label>
                                        <div class="info-value">
                                            @if($data->fonction)
                                                {{ $data->fonction->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Grade:</label>
                                        <div class="info-value">
                                            @if($data->grade)
                                                {{ $data->grade->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">État:</label>
                                        <div class="info-value">
                                            @if($data->etat == 0)
                                                <span class="badge badge-success">Actif</span>
                                            @else
                                                <span class="badge badge-danger">Inactif</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remuneration Tab -->
                        <div class="tab-pane fade" id="remuneration" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Salaire de Base:</label>
                                        <div class="info-value">{{ number_format($data->somme_salaire_base, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Prime de Fonction:</label>
                                        <div class="info-value">{{ number_format($data->prime_fonction, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Indemnité de Déplacement:</label>
                                        <div class="info-value">{{ number_format($data->indemnite_deplacement, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Indemnité de Logement:</label>
                                        <div class="info-value">{{ number_format($data->indemnite_logement, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Rémunération Brute:</label>
                                        <div class="info-value">
                                            <strong>{{ number_format(($data->somme_salaire_base + $data->indemnite_deplacement + $data->indemnite_logement + $data->prime_fonction), 0, ',', ' ') }} FBU</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Salaire Net:</label>
                                        <div class="info-value">{{ number_format($data->somme_salaire_net, 0, ',', ' ') }} FBU</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Banque:</label>
                                        <div class="info-value">
                                            @if($data->banque)
                                                {{ $data->banque->name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Numéro de Compte:</label>
                                        <div class="info-value">{{ $data->numero_compte ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Compte Avance:</label>
                                        <div class="info-value">
                                            @if($data->account)
                                                {{ $data->account->account_number }} - {{ $data->account->account_name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="info-label">Compte Sortie de fonds:</label>
                                        <div class="info-value">
                                            @if($data->cashOutAccount)
                                                {{ $data->cashOutAccount->account_number }} - {{ $data->cashOutAccount->account_name }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="mb-4">Documents de l'Employé</h5>
                                    @if($data->document)
                                        @php
                                            $documents = json_decode($data->document, true);
                                        @endphp
                                        @if($documents && is_array($documents) && count($documents) > 0)
                                            <ul class="document-list">
                                                @foreach($documents as $doc)
                                                    @php
                                                        $fileExtension = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
                                                        $fileIcon = 'fa-file';
                                                        $fileClass = 'text-secondary';
                                                        
                                                        if (in_array($fileExtension, ['pdf'])) {
                                                            $fileIcon = 'fa-file-pdf';
                                                            $fileClass = 'text-danger';
                                                        } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                                                            $fileIcon = 'fa-file-word';
                                                            $fileClass = 'text-primary';
                                                        } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                            $fileIcon = 'fa-file-image';
                                                            $fileClass = 'text-info';
                                                        } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
                                                            $fileIcon = 'fa-file-excel';
                                                            $fileClass = 'text-success';
                                                        }
                                                        
                                                        // Get URL from controller
                                                        $fileUrl = isset($documentUrls[$doc]) ? $documentUrls[$doc] : null;
                                                        $fileExists = $fileUrl !== null;
                                                    @endphp
                                                    @if($fileExists && $fileUrl)
                                                        <li>
                                                            <div class="document-info">
                                                                <i class="fa {{ $fileIcon }} document-icon {{ $fileClass }}"></i>
                                                                <span class="document-name">{{ $doc }}</span>
                                                            </div>
                                                            <div class="document-actions">
                                                                <a href="{{ $fileUrl }}" target="_blank" class="btn-view" title="Voir le document">
                                                                    <i class="fa fa-eye"></i> Voir
                                                                </a>
                                                                <a href="{{ $fileUrl }}?download=1" class="btn-download" title="Télécharger le document">
                                                                    <i class="fa fa-download"></i> Télécharger
                                                                </a>
                                                            </div>
                                                        </li>
                                                    @else
                                                        <li style="opacity: 0.6;">
                                                            <div class="document-info">
                                                                <i class="fa fa-file document-icon text-muted"></i>
                                                                <span class="document-name text-muted">{{ $doc }} <small>(Fichier non trouvé)</small></span>
                                                            </div>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fa fa-info-circle"></i> Aucun document disponible pour cet employé.
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> Aucun document disponible pour cet employé.
                </div>
                                    @endif
                    </div>
                    </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\HrEmploye;
use App\Models\HrGrade;
use App\Models\HrDepartement;
use App\Models\HrFonction;
use App\Models\HrService;
use App\Models\HrBanque;
use App\Models\HrReglage;
use App\Models\HrCompany;
use App\Models\Accounting\AccountAccount;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use App\Exports\Hr\EmployeExport;
use Excel;
use Illuminate\Support\Facades\Storage;


class EmployeController extends Controller
{
    //
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }


    public function index($company_id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }




        $employes = HrEmploye::with(['account', 'cashOutAccount'])->where('company_id',$company_id)->get();
        return view('backend.pages.hr.employe.index',compact('employes','company_id'));
    }
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $departements = HrDepartement::all();
        $services = HrService::all();
        $grades = HrGrade::all();
        $fonctions = HrFonction::all();
        $banques = HrBanque::all();
        $companies = HrCompany::all();
        $reglage = HrReglage::orderBy('created_at','desc')->first();
        $accounts = AccountAccount::where('account_number', 'LIKE', '42%')->get();

        return view('backend.pages.hr.employe.create',compact('departements','services','grades','fonctions','banques','companies','reglage','accounts'));
    }

    public function searchAccounts(Request $request)
    {
        $searchTerm = $request->get('term', '');
        $accountPrefix = $request->get('account_prefix', '42'); // Default to '42' if not provided
        
        $query = AccountAccount::query();
        
        // Filter accounts where account_number begins with the specified prefix
        // '42' for account_id (Compte Avance), '46' for cash_out_account_id (Compte Sortie de fonds)
        $query->where('account_number', 'LIKE', $accountPrefix . '%');
        
        // Filter by account type if needed (optional, can be removed if not required)
        // $query->where(function($q) {
        //     $q->where('account_type', 5)
        //       ->orWhere('account_type', 14);
        // });
        
        // Search by account name or number
        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('account_number', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('account_name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        
        $accounts = $query->limit(20)
            ->get(['id', 'account_number', 'account_name']);
        
        $data = [];
        foreach ($accounts as $account) {
            $data[] = [
                'id' => $account->id,
                'value' => $account->account_number . ' - ' . $account->account_name,
                'label' => $account->account_number . ' - ' . $account->account_name,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name
            ];
        }
        
        return response()->json($data);
    }

     public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        //
        $request->validate([
        'firstname' => 'required|min:4|max:255',
        'lastname' => 'required|min:4|max:255',
        'cni' => 'required|min:8|max:50',
        'birthdate' => 'required',
        'quartier_residence_actuel' => 'required',
        'pays' => 'required',
        'statut_matrimonial' => 'required',
        'date_debut' => 'required',
        'somme_salaire_base' => 'required',
        'departement_id' => 'required',
        'service_id' => 'required',
        'prime_fonction' => 'required',
        'fonction_id' => 'required',
        'grade_id' => 'required',
        'banque_id' => 'required',
        'account_id' => 'required',
        'cash_out_account_id' => 'required',
        'gender' => 'required',

        ]);


        $code_banque = HrBanque::where('id',$request->banque_id)->value('code');
        $code_departement = HrDepartement::where('id',$request->departement_id)->value('code');
        $code_service = HrService::where('id',$request->service_id)->value('code');

            $latest = HrEmploye::latest()->first();
            if ($latest) {
               $matricule_no = 'ME' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $matricule_no = 'ME' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }

        $employe = new HrEmploye();
        $employe->firstname = $request->firstname;
        $employe->lastname = $request->lastname;
        $employe->phone_no = $request->phone_no;
        $employe->mail = $request->mail;
        $employe->matricule_no = $matricule_no;
        $employe->fathername = $request->fathername;
        $employe->mothername = $request->mothername;
        $employe->cni = $request->cni;
        $employe->account_id = $request->account_id;
        $employe->cash_out_account_id = $request->cash_out_account_id;
        $employe->birthdate = $request->birthdate;
        $employe->bloodgroup = $request->bloodgroup;
        $employe->province = $request->province;
        $employe->commune = $request->commune;
        $employe->zone = $request->zone;
        $employe->quartier = $request->quartier;
        $employe->gender = $request->gender;
        $employe->children_number = $request->children_number;
        $employe->province_residence_actuel = $request->province_residence_actuel;
        $employe->commune_residence_actuel = $request->commune_residence_actuel;
        $employe->zone_residence_actuel = $request->zone_residence_actuel;
        $employe->quartier_residence_actuel = $request->quartier_residence_actuel;
        $employe->avenue_residence_actuel = $request->avenue_residence_actuel;
        $employe->numero = $request->numero;
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/employes/images', $imageName);
            $employe->image = $imageName;
        }
        
        // Handle documents upload (multiple files)
        if ($request->hasFile('document')) {
            $documents = [];
            foreach ($request->file('document') as $document) {
                $docName = time() . '_' . $document->getClientOriginalName();
                $document->storeAs('public/employes/documents', $docName);
                $documents[] = $docName;
            }
            $employe->document = json_encode($documents);
        }
        
        $employe->pays = $request->pays;
        $employe->statut_matrimonial = $request->statut_matrimonial;
        $employe->date_debut = $request->date_debut;
        $employe->date_fin = $request->date_fin;
        $employe->etat = 0;
        $employe->somme_salaire_base = $request->somme_salaire_base;
        $employe->somme_salaire_net = $request->somme_salaire_net;
        $employe->nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $employe->nbre_jours_conges = $request->nbre_jours_conges;
        $employe->nbre_jours_conges_consomes = $request->nbre_jours_conges_consomes;
        $employe->code_banque = $code_banque;
        $employe->code_departement = $code_departement;
        $employe->code_service = $code_service;
        $employe->numero_compte = $request->numero_compte;
        $employe->indemnite_deplacement = ($request->somme_salaire_base * 15)/100;
        $employe->indemnite_logement = ($request->somme_salaire_base*60)/100;
        $employe->prime_fonction = $request->prime_fonction;
        $employe->taux_assurance_maladie = $request->taux_assurance_maladie;
        $employe->taux_retraite = $request->taux_retraite;
        $employe->interet_credit_logement = $request->interet_credit_logement;
        $employe->retenue_pret = $request->retenue_pret;
        $employe->autre_retenue = $request->autre_retenue;
        $employe->created_by = $this->user->name;
        $employe->departement_id = $request->departement_id;
        $employe->service_id = $request->service_id;
        $employe->fonction_id = $request->fonction_id;
        $employe->grade_id = $request->grade_id;
        $employe->banque_id = $request->banque_id;
        $employe->company_id = $request->company_id;
        $employe->save();

        session()->flash('success', $this->user->name.', vous avez créé un Employé avec succés !!');

        return redirect()->route('admin.hr-employes.index',$employe->company_id);
    }

    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $data = HrEmploye::with(['account', 'cashOutAccount'])->where('id',$id)->first();
        $setting = DB::table('settings')->orderBy('created_at','desc')->first();

        // Generate URLs for image and documents
        $imageUrl = null;
        if($data->image) {
            $imagePath = 'public/employes/images/'.$data->image;
            if(Storage::exists($imagePath)) {
                // Use route to serve file directly
                $imageUrl = route('admin.hr-employes.image', ['id' => $data->id, 'filename' => $data->image]);
            }
        }

        // Generate URLs for documents
        $documentUrls = [];
        if($data->document) {
            $documents = json_decode($data->document, true);
            if($documents && is_array($documents)) {
                foreach($documents as $doc) {
                    $docPath = 'public/employes/documents/'.$doc;
                    if(Storage::exists($docPath)) {
                        // Use route to serve file directly
                        $documentUrls[$doc] = route('admin.hr-employes.document', ['id' => $data->id, 'filename' => $doc]);
                    } else {
                        $documentUrls[$doc] = null;
                    }
                }
            }
        }

        return view('backend.pages.hr.employe.show',compact('data','setting','imageUrl','documentUrls'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\HrEmploye  $employe
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        $departements = HrDepartement::all();
        $services = HrService::all();
        $grades = HrGrade::all();
        $fonctions = HrFonction::all();
        $banques = HrBanque::all();
        $companies = HrCompany::all();
        $reglage = HrReglage::orderBy('created_at','desc')->first();
        $accounts = AccountAccount::where('account_number', 'LIKE', '42%')->get();
        $employe = HrEmploye::with(['account', 'cashOutAccount'])->where('id',$id)->first();
        return view('backend.pages.hr.employe.edit', compact('employe','departements','services','grades','fonctions','banques','companies','reglage','accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrEmploye  $employe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
        'firstname' => 'required|min:4|max:255',
        'lastname' => 'required|min:4|max:255',
        'cni' => 'required|min:8|max:50',
        'birthdate' => 'required',
        'quartier_residence_actuel' => 'required',
        'pays' => 'required',
        'statut_matrimonial' => 'required',
        'date_debut' => 'required',
        'somme_salaire_base' => 'required',
        'departement_id' => 'required',
        'service_id' => 'required',
        'prime_fonction' => 'required',
        'fonction_id' => 'required',
        'grade_id' => 'required',
        'banque_id' => 'required',
        'account_id' => 'required',
        'cash_out_account_id' => 'required',

        ]);


        $code_banque = HrBanque::where('id',$request->banque_id)->value('code');
        $code_departement = HrDepartement::where('id',$request->departement_id)->value('code');
        $code_service = HrService::where('id',$request->service_id)->value('code');

        $employe = HrEmploye::findOrFail($id);
        $employe->firstname = $request->firstname;
        $employe->lastname = $request->lastname;
        $employe->phone_no = $request->phone_no;
        $employe->mail = $request->mail;
        $employe->fathername = $request->fathername;
        $employe->mothername = $request->mothername;
        $employe->cni = $request->cni;
        $employe->account_id = $request->account_id;
        $employe->cash_out_account_id = $request->cash_out_account_id;
        $employe->birthdate = $request->birthdate;
        $employe->bloodgroup = $request->bloodgroup;
        $employe->province = $request->province;
        $employe->commune = $request->commune;
        $employe->zone = $request->zone;
        $employe->quartier = $request->quartier;
        $employe->gender = $request->gender;
        $employe->children_number = $request->children_number;
        $employe->province_residence_actuel = $request->province_residence_actuel;
        $employe->commune_residence_actuel = $request->commune_residence_actuel;
        $employe->zone_residence_actuel = $request->zone_residence_actuel;
        $employe->quartier_residence_actuel = $request->quartier_residence_actuel;
        $employe->avenue_residence_actuel = $request->avenue_residence_actuel;
        $employe->numero = $request->numero;
        
        // Handle image deletion
        if ($request->has('delete_image') && $request->delete_image == '1') {
            if ($employe->image && Storage::exists('public/employes/images/' . $employe->image)) {
                Storage::delete('public/employes/images/' . $employe->image);
            }
            $employe->image = null;
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($employe->image && Storage::exists('public/employes/images/' . $employe->image)) {
                Storage::delete('public/employes/images/' . $employe->image);
            }
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/employes/images', $imageName);
            $employe->image = $imageName;
        }
        
        // Handle documents deletion
        if ($request->has('delete_documents') && !empty($request->delete_documents)) {
            $documentsToDelete = json_decode($request->delete_documents, true);
            if (is_array($documentsToDelete)) {
                foreach ($documentsToDelete as $docName) {
                    if (Storage::exists('public/employes/documents/' . $docName)) {
                        Storage::delete('public/employes/documents/' . $docName);
                    }
                }
            }
        }
        
        // Handle documents upload (multiple files)
        $existingDocs = [];
        if ($employe->document) {
            $existingDocs = json_decode($employe->document, true) ?? [];
        }
        
        // Remove deleted documents from existing list
        if ($request->has('delete_documents') && !empty($request->delete_documents)) {
            $documentsToDelete = json_decode($request->delete_documents, true);
            if (is_array($documentsToDelete)) {
                $existingDocs = array_filter($existingDocs, function($doc) use ($documentsToDelete) {
                    return !in_array($doc, $documentsToDelete);
                });
                $existingDocs = array_values($existingDocs); // Re-index array
            }
        }
        
        if ($request->hasFile('document')) {
            // Upload new documents
            $newDocuments = [];
            foreach ($request->file('document') as $document) {
                $docName = time() . '_' . $document->getClientOriginalName();
                $document->storeAs('public/employes/documents', $docName);
                $newDocuments[] = $docName;
            }
            
            // Merge existing and new documents
            $allDocuments = array_merge($existingDocs, $newDocuments);
            $employe->document = json_encode($allDocuments);
        } else {
            // If no new documents but existing docs were modified, update the document field
            if ($request->has('delete_documents') && !empty($request->delete_documents)) {
                $employe->document = !empty($existingDocs) ? json_encode($existingDocs) : null;
            }
        }
        
        $employe->pays = $request->pays;
        $employe->statut_matrimonial = $request->statut_matrimonial;
        $employe->date_debut = $request->date_debut;
        $employe->date_fin = $request->date_fin;
        $employe->etat = 0;
        $employe->somme_salaire_base = $request->somme_salaire_base;
        $employe->somme_salaire_net = $request->somme_salaire_net;
        $employe->nbre_jours_ouvrables = $request->nbre_jours_ouvrables;
        $employe->nbre_jours_conges = $request->nbre_jours_conges;
        $employe->nbre_jours_conges_consomes = $request->nbre_jours_conges_consomes;
        $employe->code_banque = $code_banque;
        $employe->code_departement = $code_departement;
        $employe->code_service = $code_service;
        $employe->numero_compte = $request->numero_compte;
        $employe->indemnite_deplacement = ($request->somme_salaire_base * 15)/100;
        $employe->indemnite_logement = ($request->somme_salaire_base*60)/100;
        $employe->prime_fonction = $request->prime_fonction;
        $employe->taux_assurance_maladie = $request->taux_assurance_maladie;
        $employe->taux_retraite = $request->taux_retraite;
        $employe->interet_credit_logement = $request->interet_credit_logement;
        $employe->retenue_pret = $request->retenue_pret;
        $employe->autre_retenue = $request->autre_retenue;
        $employe->created_by = $this->user->name;
        $employe->departement_id = $request->departement_id;
        $employe->service_id = $request->service_id;
        $employe->fonction_id = $request->fonction_id;
        $employe->grade_id = $request->grade_id;
        $employe->banque_id = $request->banque_id;
        $employe->company_id = $request->company_id;
        $employe->save();
        session()->flash('success', $this->user->name.', vous avez modifié Employé!!');
        return redirect()->back();
    }


    public function exportToExcel(Request $request)
    {
        return Excel::download(new EmployeExport, 'employes.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\HrEmploye  $employe
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer l\'employé! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $employe = HrEmploye::findOrFail($id);
        $employe->delete();
        session()->flash('success', 'Employé est supprimé !!');
        return redirect()->back();
    }

    /**
     * Serve employee image
     */
    public function serveImage($id, $filename)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les employés!');
        }

        $employe = HrEmploye::findOrFail($id);
        
        // Verify the image belongs to this employee
        if($employe->image !== $filename) {
            abort(404);
        }

        $path = storage_path('app/public/employes/images/'.$filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = \Illuminate\Support\Facades\File::get($path);
        $type = \Illuminate\Support\Facades\File::mimeType($path);
        
        $response = response($file, 200);
        $response->header('Content-Type', $type);
        $response->header('Content-Disposition', 'inline; filename="'.$filename.'"');
        
        return $response;
    }

    /**
     * Serve employee document
     */
    public function serveDocument($id, $filename, Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les employés!');
        }

        $employe = HrEmploye::findOrFail($id);
        
        // Verify the document belongs to this employee
        if($employe->document) {
            $documents = json_decode($employe->document, true);
            if(!$documents || !is_array($documents) || !in_array($filename, $documents)) {
                abort(404);
            }
        } else {
            abort(404);
        }

        $path = storage_path('app/public/employes/documents/'.$filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = \Illuminate\Support\Facades\File::get($path);
        $type = \Illuminate\Support\Facades\File::mimeType($path);
        
        // Check if download is requested
        $disposition = $request->get('download') ? 'attachment' : 'inline';
        
        $response = response($file, 200);
        $response->header('Content-Type', $type);
        $response->header('Content-Disposition', $disposition.'; filename="'.$filename.'"');
        
        return $response;
    }

    /**
     * Display employee badge
     */
    public function badge($id)
    {
        if (is_null($this->user) || !$this->user->can('hr_employe.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les employés! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $data = HrEmploye::where('id',$id)->first();
        
        if (!$data) {
            abort(404, 'Employé non trouvé');
        }

        // Generate URL for image
        $imageUrl = null;
        if($data->image) {
            $imagePath = 'public/employes/images/'.$data->image;
            if(Storage::exists($imagePath)) {
                $imageUrl = route('admin.hr-employes.image', ['id' => $data->id, 'filename' => $data->image]);
            }
        }

        return view('backend.pages.hr.employe.badge', compact('data', 'imageUrl'));
    }
}

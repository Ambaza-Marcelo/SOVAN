<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\EGRClient;
use App\Models\Accounting\AccountAccount;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class ClientController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('client.view')) {
            abort(403, 'Sorry !! You are Unauthorized to view any client !');
        }

        $clients = EGRClient::with('account')->orderBy('customer_name','asc')->get();
        return view('backend.pages.client.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('client.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any client !');
        }

        $accounts = AccountAccount::all();
        return view('backend.pages.client.create',compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('client.create')) {
            abort(403, 'Sorry !! You are Unauthorized to create any client !');
        }

        // Validation Data
        $request->validate([
            'account_id' => 'required',
            'tp_type' => 'required',
            'telephone' => 'required',
            'customer_name' => 'required',
        ]);

        $tp_TIN = $request->customer_TIN;

        if ($request->tp_type == '2' && empty($tp_TIN)) {
            session()->flash('error', 'Le NIF du client est obligatoire pour une société locale.');
            return redirect()->back();
        }
        if (!empty($tp_TIN) && strlen($tp_TIN) < 10) {
            session()->flash('error', 'Le NIF du client n\'existe pas');
            return redirect()->back();
        }

        /*
        // Transmission des données vers OBR (désactivée)
        if ($request->tp_type == 2) {

            $theUrl = config('app.guzzle_test_url').'/ebms_api/login/';
            $response = Http::post($theUrl, [
            'username'=> config('app.obr_test_username'),
            'password'=> config('app.obr_test_pwd')

            ]);

            $data = $response->json();
            $data2 = $data['result'] ?? [];
        
    
            $token = $data2['token'] ?? null;
            if (empty($token)) {
                session()->flash('error', 'Impossible de se connecter au service OBR.');
                return redirect()->back();
            }


            $theUrl = config('app.guzzle_test_url').'/ebms_api/checkTIN/';
            $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json'])->post($theUrl, [
            'tp_TIN'=>$tp_TIN,

            ]); 

            $data = $response->json();
            $data2 = $data['result'] ?? [];
        
            
            $success = $data['success'] ?? false;
            $msg = $data['msg'] ?? 'NIF invalide ou inconnu.';
            $taxpayers = $data2['taxpayer'] ?? [];
            $index_one = $taxpayers[0] ?? null;
            $tp_name = $index_one['tp_name'] ?? null;
            if (!$success || empty($tp_name)) {
                session()->flash('error', $msg);
                return redirect()->back();
            }

            $client = new EGRClient();
            $client->date = $request->date;
            $client->account_id = $request->account_id;
            $client->customer_name = $tp_name;
            $client->tp_type = $request->tp_type;
            $client->telephone = $request->telephone;
            $client->mail = $request->mail;
            $client->customer_TIN = $request->customer_TIN;
            $client->customer_address = $request->customer_address;
            $client->vat_customer_payer = $request->vat_customer_payer;
            $client->company = $request->company;
            $client->save();
            if ($success == true) {
                session()->flash('success', 'Le client a été créé avec succés !!, OBR Message : '.$msg.'('.$tp_name.')');
                return redirect()->route('admin.clients.index');
            }else{
                session()->flash('error', 'Something wrong');
                return redirect()->back();
            }

        }else{
        */
            $client = new EGRClient();
            $client->date = $request->date;
            $client->account_id = $request->account_id;
            $client->customer_name = $request->customer_name;
            $client->tp_type = $request->tp_type;
            $client->telephone = $request->telephone;
            $client->mail = $request->mail;
            $client->customer_TIN = $request->customer_TIN;
            $client->customer_address = $request->customer_address;
            $client->vat_customer_payer = $request->vat_customer_payer;
            $client->company = $request->company;
            $client->save();
        // }

        session()->flash('success', 'Client has been created !!');
        return redirect()->route('admin.clients.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('client.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any client !');
        }

        $client = EGRClient::find($id);
        $accounts = AccountAccount::all();
        return view('backend.pages.client.edit', compact('client','accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('client.edit')) {
            abort(403, 'Sorry !! You are Unauthorized to edit any client !');
        }

        $client = EGRClient::find($id);

        $request->validate([
            'account_id' => 'required',
            'tp_type' => 'required',
            'telephone' => 'required',
            'customer_name' => 'required',
        ]);

        $tp_TIN = $request->customer_TIN;

        if ($request->tp_type == '2' && empty($tp_TIN)) {
            session()->flash('error', 'Le NIF du client est obligatoire pour une société locale.');
            return redirect()->back();
        }
        if (!empty($tp_TIN) && strlen($tp_TIN) < 10) {
            session()->flash('error', 'Le NIF du client n\'existe pas');
            return redirect()->back();
        }

        /*
        // Transmission des données vers OBR (désactivée)
        if ($request->tp_type == 2) {

            $theUrl = config('app.guzzle_test_url').'/ebms_api/login/';
            $response = Http::post($theUrl, [
            'username'=> config('app.obr_test_username'),
            'password'=> config('app.obr_test_pwd')

            ]);

            $data = $response->json();
            $data2 = $data['result'] ?? [];
        
            
            $token = $data2['token'] ?? null;
            if (empty($token)) {
                session()->flash('error', 'Impossible de se connecter au service OBR.');
                return redirect()->back();
            }

            $theUrl = config('app.guzzle_test_url').'/ebms_api/checkTIN/';
            $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json'])->post($theUrl, [
            'tp_TIN'=>$tp_TIN,

            ]); 

            $data = $response->json();
            $data2 = $data['result'] ?? [];
        
    
            $success = $data['success'] ?? false;
            $msg = $data['msg'] ?? 'NIF invalide ou inconnu.';
            $taxpayers = $data2['taxpayer'] ?? [];
            $index_one = $taxpayers[0] ?? null;
            $tp_name = $index_one['tp_name'] ?? null;
            if (!$success || empty($tp_name)) {
                session()->flash('error', $msg);
                return redirect()->back();
            }

            $client->date = $request->date;
            $client->account_id = $request->account_id;
            $client->customer_name = $tp_name;
            $client->tp_type = $request->tp_type;
            $client->telephone = $request->telephone;
            $client->mail = $request->mail;
            $client->customer_TIN = $request->customer_TIN;
            $client->customer_address = $request->customer_address;
            $client->vat_customer_payer = $request->vat_customer_payer;
            $client->company = $request->company;
            $client->save();
            if ($success == true) {
                session()->flash('success', 'Le client a été modifié avec succés !!, OBR Message : '.$msg.'('.$tp_name.')');
                return redirect()->route('admin.clients.index');
            }else{
                session()->flash('error', 'Something wrong');
                return redirect()->back();
            }
            
        }else{
        */
            $client->date = $request->date;
            $client->account_id = $request->account_id;
            $client->customer_name = $request->customer_name;
            $client->tp_type = $request->tp_type;
            $client->telephone = $request->telephone;
            $client->mail = $request->mail;
            $client->customer_TIN = $request->customer_TIN;
            $client->customer_address = $request->customer_address;
            $client->vat_customer_payer = $request->vat_customer_payer;
            $client->company = $request->company;
            $client->save();
        // }

        session()->flash('success', 'Client has been updated !!');
        return redirect()->route('admin.clients.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('client.delete')) {
            abort(403, 'Sorry !! You are Unauthorized to delete any client !');
        }

        $client = EGRClient::find($id);
        if (!is_null($client)) {
            $client->delete();
        }

        session()->flash('success', 'Client has been deleted !!');
        return back();
    }
}

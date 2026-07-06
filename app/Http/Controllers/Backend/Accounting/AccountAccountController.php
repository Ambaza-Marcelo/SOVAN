<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountParentAccount;
use App\Models\Accounting\AccountCurrency;


class AccountAccountController extends Controller
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

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('account_account.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $accounts = AccountAccount::orderBy('account_number', 'asc')->get();
        $parent_accounts = AccountParentAccount::all();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.account.index',compact('accounts','parent_accounts','currencies'));
    }


    public function create(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_account.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $parent_accounts = AccountParentAccount::all();
        $currencies = AccountCurrency::all();
        $selected_parent_id = $request->get('parent_id'); // Get parent_id from URL parameter

        return view('backend.pages.accounting.account.create',compact('parent_accounts','currencies','selected_parent_id'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_account.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'account_name' => 'required|min:2',
            'account_number' => 'required|min:2',
            'account_type' => 'required',
            'balance_must_be' => 'required',
            'description' => 'required|min:5',
            'currency_id' => 'required',

        ]);


            $latest = AccountAccount::latest()->first();
            if ($latest) {
               $code = 'ACC' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $code = 'ACC' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }


        $compte = new AccountAccount();
        $compte->account_name = $request->account_name;
        $compte->account_number = $request->account_number;
        $compte->code = $code;
        $compte->account_type = $request->account_type;
        $compte->balance_must_be = $request->balance_must_be;
        $compte->description = $request->description;
        $compte->currency_id = $request->currency_id;
        $compte->acc_parent_id = $request->acc_parent_id;
        $compte->save();

        session()->flash('success', 'compte est créé !!');

        return redirect()->back();
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_account.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $account = AccountAccount::findOrFail($id);
        $parent_accounts = AccountParentAccount::all();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.account.edit',compact('account','parent_accounts','currencies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_account.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'account_name' => 'required|min:2',
            'account_number' => 'required|min:2',
            'account_type' => 'required',
            'balance_must_be' => 'required',
            'description' => 'required|min:5',
            'currency_id' => 'required',

        ]);

        $compte = AccountAccount::findOrFail($id);

        $compte->account_name = $request->account_name;
        $compte->account_number = $request->account_number;
        $compte->account_type = $request->account_type;
        $compte->balance_must_be = $request->balance_must_be;
        $compte->description = $request->description;
        $compte->currency_id = $request->currency_id;
        $compte->acc_parent_id = $request->acc_parent_id;
        $compte->save();
        session()->flash('success', 'compte est modifié !!');
        return redirect()->route('admin.accounting-accounts.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_account.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $compte = AccountAccount::findOrFail($id);
        $compte->delete();
        session()->flash('success', 'compte est supprimé !!');
        return redirect()->back();
    }

    /**
     * Search accounts for autocomplete
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $term = $request->get('q', '');
        $accountType = $request->get('account_type', null);
        $accountPrefix = $request->get('account_prefix', null); // Optional prefix filter (e.g., '40', '42')
        
        $query = AccountAccount::query();
        
        // Filter accounts by prefix if provided (for suppliers: '40', for employees: '42')
        if ($accountPrefix) {
            $query->where('account_number', 'LIKE', $accountPrefix . '%');
        }
        
        // Filter by account type if provided
        if ($accountType) {
            $query->where('account_type', $accountType);
        }
        
        // Search by account name or number
        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('account_name', 'LIKE', '%' . $term . '%')
                  ->orWhere('account_number', 'LIKE', '%' . $term . '%');
            });
        }
        
        $accounts = $query->limit(50)
            ->get(['id', 'account_number', 'account_name']);

        $results = [];
        foreach ($accounts as $account) {
            $results[] = [
                'id' => $account->id,
                'text' => $account->account_number . ' - ' . $account->account_name
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }
}

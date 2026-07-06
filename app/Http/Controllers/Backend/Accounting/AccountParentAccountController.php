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
use App\Models\Accounting\AccountParentAccount;
use App\Models\Accounting\AccountAccount;
use App\Models\Accounting\AccountCurrency;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Accounting\ChartAccount;

class AccountParentAccountController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_parent_account.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $parent_accounts = AccountParentAccount::with('account')->orderBy('account_number', 'asc')->get();
        $accounts = AccountAccount::all();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.parent_account.index',compact('parent_accounts','accounts','currencies'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.parent_account.create',compact('currencies'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.create')) {
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


            $latest = AccountParentAccount::latest()->first();
            if ($latest) {
               $code = 'ACC' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $code = 'ACC' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }


        $compte = new AccountParentAccount();
        $compte->account_name = $request->account_name;
        $compte->account_number = $request->account_number;
        $compte->code = $code;
        $compte->account_type = $request->account_type;
        $compte->balance_must_be = $request->balance_must_be;
        $compte->description = $request->description;
        $compte->currency_id = $request->currency_id;
        $compte->save();

        session()->flash('success', 'compte est créé !!');

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountParentAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (is_null($this->user) || !$this->user->can('account_account.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $parent_account = AccountParentAccount::where('id',$id)->first();
        $accounts = AccountAccount::where('acc_parent_id',$id)->orderBy('account_number')->get();

        return view('backend.pages.accounting.parent_account.show',compact('parent_account','accounts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountParentAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $account = AccountParentAccount::findOrFail($id);
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.parent_account.edit',compact('account','currencies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountParentAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.edit')) {
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

        $compte = AccountParentAccount::findOrFail($id);

        $compte->account_name = $request->account_name;
        $compte->account_number = $request->account_number;
        $compte->account_type = $request->account_type;
        $compte->balance_must_be = $request->balance_must_be;
        $compte->description = $request->description;
        $compte->currency_id = $request->currency_id;
        $compte->save();
        session()->flash('success', 'compte est modifié !!');
        return redirect()->route('admin.accounting-parent-accounts.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountParentAccount  $compte
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le compte! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $compte = AccountParentAccount::findOrFail($id);
        $compte->delete();
        session()->flash('success', 'compte est supprimé !!');
        return redirect()->back();
    }

    /**
     * Exporte le plan comptable en Excel (comptes principaux et sous-comptes).
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        if (is_null($this->user) || !$this->user->can('account_parent_account.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le plan comptable.');
        }

        $fileName = 'plan_comptable_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new ChartAccount(), $fileName);
    }
}

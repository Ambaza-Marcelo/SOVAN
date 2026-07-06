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
use App\Models\Accounting\AccountCurrency;

class AccountCurrencyController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_currency.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.currency.index',compact('currencies'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_currency.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }


        return view('backend.pages.accounting.currency.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_currency.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'account_name' => 'required|min:5',
            'account_number' => 'required|min:2',
            'account_type' => 'required|min:2',
            'balance_must_be' => 'required|min:2',
            'description' => 'required|min:2',
            'currency_id' => 'required|min:2',
            'acc_parent_id' => 'required|min:2',

        ]);


            $latest = AccountAccount::latest()->first();
            if ($latest) {
               $code = 'ACC' . (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $code = 'ACC' . (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }


        $monnaie = new AccountAccount();
        $monnaie->account_name = $request->account_name;
        $monnaie->account_number = $request->account_number;
        $monnaie->code = $code;
        $monnaie->account_type = $request->account_type;
        $monnaie->balance_must_be = $request->balance_must_be;
        $monnaie->description = $request->description;
        $monnaie->currency_id = $request->currency_id;
        $monnaie->acc_parent_id = $request->acc_parent_id;
        $monnaie->save();

        session()->flash('success', 'monnaie est créé !!');

        return redirect()->back();
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountAccount  $monnaie
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_currency.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $account = AccountAccount::findOrFail($id);
        $parent_accounts = AccountParentAccount::all();
        $currencies = AccountCurrency::all();

        return view('backend.pages.accounting.currency.edit',compact('account','parent_accounts','currencies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountAccount  $monnaie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_currency.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'account_name' => 'required|min:5',
            'account_number' => 'required|min:2',
            'account_type' => 'required|min:2',
            'balance_must_be' => 'required|min:2',
            'description' => 'required|min:2',
            'currency_id' => 'required|min:2',
            'acc_parent_id' => 'required|min:2',

        ]);

        $monnaie = AccountAccount::findOrFail($id);

        $monnaie->account_name = $request->account_name;
        $monnaie->account_number = $request->account_number;
        $monnaie->account_type = $request->account_type;
        $monnaie->balance_must_be = $request->balance_must_be;
        $monnaie->description = $request->description;
        $monnaie->currency_id = $request->currency_id;
        $monnaie->acc_parent_id = $request->acc_parent_id;
        $monnaie->save();
        session()->flash('success', 'monnaie est modifié !!');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountAccount  $monnaie
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_currency.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le monnaie! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $monnaie = AccountAccount::findOrFail($id);
        $monnaie->delete();
        session()->flash('success', 'monnaie est supprimé !!');
        return redirect()->back();
    }
}

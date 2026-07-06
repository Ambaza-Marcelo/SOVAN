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
use App\Models\Accounting\AccountCostCenter;

class AccountCostCenterController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_cost_center.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $cost_centers = AccountCostCenter::all();

        return view('backend.pages.accounting.cost_center.index',compact('cost_centers'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_cost_center.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }


        return view('backend.pages.accounting.cost_center.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_cost_center.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:5',
            'default_account_id' => 'required',

        ]);



        $cost_center = new AccountCostCenter();
        $cost_center->name = $request->name;
        $cost_center->default_account_id = $request->default_account_id;
        $cost_center->save();

        session()->flash('success', 'centre de coût est créé !!');

        return redirect()->back();
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountCostCenter  $cost_center
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_cost_center.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $cost_center = AccountCostCenter::findOrFail($id);

        return view('backend.pages.accounting.cost_center.edit',compact('cost_center'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountCostCenter  $cost_center
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_cost_center.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:5',
            'default_account_id' => 'required',

        ]);

        $cost_center = AccountCostCenter::findOrFail($id);

        $cost_center->name = $request->name;
        $cost_center->default_account_id = $request->default_account_id;
        $cost_center->save();
        session()->flash('success', 'centre de coût est modifié !!');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountCostCenter  $cost_center
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_cost_center.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer le centre de coût! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $cost_center = AccountCostCenter::findOrFail($id);
        $cost_center->delete();
        session()->flash('success', 'centre de coût est supprimé !!');
        return redirect()->back();
    }
}

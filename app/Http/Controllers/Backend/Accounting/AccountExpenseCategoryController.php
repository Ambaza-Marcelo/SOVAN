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
use App\Models\Accounting\AccountExpenseCategory;

class AccountExpenseCategoryController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_expense_category.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $expense_categories = AccountExpenseCategory::all();

        return view('backend.pages.accounting.expense_category.index',compact('expense_categories'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_expense_category.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }


        return view('backend.pages.accounting.expense_category.create');
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_expense_category.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:5',

        ]);


        $expense_category = new AccountExpenseCategory();
        $expense_category->name = $request->name;
        $expense_category->save();

        session()->flash('success', 'categorie depense est créée !!');

        return redirect()->back();
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountExpenseCategory  $expense_category
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense_category.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $expense_category = AccountExpenseCategory::findOrFail($id);

        return view('backend.pages.accounting.expense_category.edit',compact('expense_category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountExpenseCategory  $expense_category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense_category.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:5',

        ]);

        $expense_category = AccountExpenseCategory::findOrFail($id);

        $expense_category->name = $request->name;
        $expense_category->save();
        session()->flash('success', 'categorie depense est modifiée !!');
        return redirect()->route('admin.expense-categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountExpenseCategory  $expense_category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense_category.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer la categorie depense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $expense_category = AccountExpenseCategory::findOrFail($id);
        $expense_category->delete();
        session()->flash('success', 'expense_category est supprimé !!');
        return redirect()->back();
    }
}

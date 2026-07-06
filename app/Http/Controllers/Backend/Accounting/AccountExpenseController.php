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
use App\Models\Accounting\AccountExpense;
use App\Models\Accounting\AccountExpenseCategory;


class AccountExpenseController extends Controller
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
        if (is_null($this->user) || !$this->user->can('account_expense.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $expenses = AccountExpense::all();
        $accounts = AccountAccount::all();
        $expense_categories = AccountExpenseCategory::all();

        return view('backend.pages.accounting.expense.index',compact('expenses','accounts','expense_categories'));
    }


    public function create()
    {
        if (is_null($this->user) || !$this->user->can('account_expense.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $accounts = AccountAccount::all();
        $expense_categories = AccountExpenseCategory::all();

        return view('backend.pages.accounting.expense.create',compact('accounts','currencies'));
    }

    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('account_expense.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:3',
            'cost_price' => 'required',
            'expense_category_id' => 'required',
            'default_account_id' => 'required',

        ]);


            $latest = AccountExpense::latest()->first();
            if ($latest) {
               $code = (str_pad((int)$latest->id + 1, 4, '0', STR_PAD_LEFT)); 
            }else{
               $code = (str_pad((int)0 + 1, 4, '0', STR_PAD_LEFT));  
            }


        $expense = new AccountExpense();
        $expense->name = $request->name;
        $expense->specification = $request->specification;
        $expense->code = $code;
        $expense->quantity = 0;
        $expense->cost_price = $request->cost_price;
        $expense->status = 1;
        $expense->total_cost = $request->cost_price * $expense->quantity;
        $expense->expense_category_id = $request->expense_category_id;
        $expense->default_account_id = $request->default_account_id;
        $expense->created_by = $this->user->name;
        $expense->save();

        session()->flash('success', 'expense est créé !!');

        return redirect()->route('admin.accounting-expenses.index');
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountExpense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $expense = AccountExpense::findOrFail($id);
        $accounts = AccountAccount::all();
        $expense_categories = AccountExpenseCategory::all();

        return view('backend.pages.accounting.expense.edit',compact('expense','accounts','expense_categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountExpense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $request->validate([
            'name' => 'required|min:3',
            'cost_price' => 'required',
            'expense_category_id' => 'required',
            'default_account_id' => 'required',

        ]);

        $expense = AccountExpense::findOrFail($id);

        $expense->name = $request->name;
        $expense->specification = $request->specification;
        $expense->quantity = 0;
        $expense->cost_price = $request->cost_price;
        $expense->status = $request->status;
        $expense->total_cost = $request->cost_price * $expense->quantity;
        $expense->expense_category_id = $request->expense_category_id;
        $expense->default_account_id = $request->default_account_id;
        $expense->created_by = $this->user->name;
        $expense->save();
        session()->flash('success', 'dépense est modifié !!');
        return redirect()->route('admin.accounting-expenses.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountExpense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('account_expense.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer dépense! Mufise ico mubaza hamagara kuri 130 canke 122');
        }
        //
        $expense = AccountExpense::findOrFail($id);
        $expense->delete();
        session()->flash('success', 'dépense est supprimé !!');
        return redirect()->back();
    }

}

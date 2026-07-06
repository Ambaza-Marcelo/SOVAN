<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Display a listing of exchange rates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (is_null($this->user) || !$this->user->can('item.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $exchange_rates = ExchangeRate::with(['originCurrency', 'destinationCurrency'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('backend.pages.exchange_rate.index', compact('exchange_rates'));
    }

    /**
     * Show the form for creating a new exchange rate.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->user) || !$this->user->can('item.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $currencies = Currency::orderBy('currency_name')->get();
        return view('backend.pages.exchange_rate.create', compact('currencies'));
    }

    /**
     * Store a newly created exchange rate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('item.create')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de créer les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'origin_currency_id' => 'required|different:destination_currency_id',
            'destination_currency_id' => 'required|different:origin_currency_id',
            'rate' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        // Vérifier si un taux existe déjà pour cette paire de devises à cette date
        $existing = ExchangeRate::where('origin_currency_id', $request->origin_currency_id)
            ->where('destination_currency_id', $request->destination_currency_id)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Un taux de change existe déjà pour cette paire de devises à cette date. Veuillez le modifier au lieu d\'en créer un nouveau.');
        }

        $exchange_rate = new ExchangeRate();
        $exchange_rate->origin_currency_id = $request->origin_currency_id;
        $exchange_rate->destination_currency_id = $request->destination_currency_id;
        $exchange_rate->rate = $request->rate;
        $exchange_rate->date = $request->date;
        $exchange_rate->description = $request->description;
        $exchange_rate->created_by = $this->user->name ?? null;
        $exchange_rate->active = $request->has('active') ? 1 : 0;
        $exchange_rate->save();

        session()->flash('success', 'Taux de change créé avec succès !!');
        return redirect()->route('admin.exchange-rates.index');
    }

    /**
     * Show the form for editing the specified exchange rate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('item.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $exchange_rate = ExchangeRate::findOrFail($id);
        $currencies = Currency::orderBy('currency_name')->get();
        return view('backend.pages.exchange_rate.edit', compact('exchange_rate', 'currencies'));
    }

    /**
     * Update the specified exchange rate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (is_null($this->user) || !$this->user->can('item.edit')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de modifier les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $request->validate([
            'origin_currency_id' => 'required|different:destination_currency_id',
            'destination_currency_id' => 'required|different:origin_currency_id',
            'rate' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $exchange_rate = ExchangeRate::findOrFail($id);

        // Vérifier si un autre taux existe déjà pour cette paire de devises à cette date
        $existing = ExchangeRate::where('origin_currency_id', $request->origin_currency_id)
            ->where('destination_currency_id', $request->destination_currency_id)
            ->where('date', $request->date)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Un taux de change existe déjà pour cette paire de devises à cette date.');
        }

        $exchange_rate->origin_currency_id = $request->origin_currency_id;
        $exchange_rate->destination_currency_id = $request->destination_currency_id;
        $exchange_rate->rate = $request->rate;
        $exchange_rate->date = $request->date;
        $exchange_rate->description = $request->description;
        $exchange_rate->updated_by = $this->user->name ?? null;
        $exchange_rate->active = $request->has('active') ? 1 : 0;
        $exchange_rate->save();

        session()->flash('success', 'Taux de change modifié avec succès !!');
        return redirect()->route('admin.exchange-rates.index');
    }

    /**
     * Remove the specified exchange rate from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (is_null($this->user) || !$this->user->can('item.delete')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de supprimer les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $exchange_rate = ExchangeRate::findOrFail($id);
        $exchange_rate->delete();

        session()->flash('success', 'Taux de change supprimé avec succès !!');
        return redirect()->back();
    }

    /**
     * Get exchange rate for conversion
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRate(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('item.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de visualiser les taux de change! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $origin_currency_id = $request->input('origin_currency_id');
        $destination_currency_id = $request->input('destination_currency_id');
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));

        $exchange_rate = ExchangeRate::where('origin_currency_id', $origin_currency_id)
            ->where('destination_currency_id', $destination_currency_id)
            ->where('date', '<=', $date)
            ->where('active', 1)
            ->orderBy('date', 'desc')
            ->first();

        if ($exchange_rate) {
            return response()->json([
                'success' => true,
                'rate' => $exchange_rate->rate,
                'date' => $exchange_rate->date
            ]);
        }

        // Essayer le taux inverse
        $reverse_rate = ExchangeRate::where('origin_currency_id', $destination_currency_id)
            ->where('destination_currency_id', $origin_currency_id)
            ->where('date', '<=', $date)
            ->where('active', 1)
            ->orderBy('date', 'desc')
            ->first();

        if ($reverse_rate) {
            return response()->json([
                'success' => true,
                'rate' => 1 / $reverse_rate->rate,
                'date' => $reverse_rate->date
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun taux de change trouvé pour cette paire de devises'
        ]);
    }
}


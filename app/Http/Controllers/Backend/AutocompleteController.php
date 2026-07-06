<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\EGRClient;
use App\Models\HrEmploye;
use App\Models\Item;
use App\Models\Material;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\Accounting\AccountExpense;
use App\Support\StockReportArticleSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutocompleteController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Ensure admin is authenticated (same guard used across backend)
            Auth::guard('admin')->user();
            return $next($request);
        });
    }

    public function clients(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = EGRClient::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'LIKE', '%' . $term . '%')
                  ->orWhere('telephone', 'LIKE', '%' . $term . '%')
                  ->orWhere('customer_TIN', 'LIKE', '%' . $term . '%');
            });
        }

        $clients = $query->orderBy('customer_name')->limit(30)->get([
            'id',
            'customer_name',
            'telephone',
        ]);

        $results = $clients->map(function ($c) {
            $text = trim(($c->customer_name ?? '') . '/' . ($c->telephone ?? ''));
            return [
                'id' => $c->id,
                'text' => $text,
                'name' => $c->customer_name,
                'telephone' => $c->telephone,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function items(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Item::query()->active();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $items = $query->orderBy('name')->limit(30)->get([
            'id',
            'name',
            'code',
            'selling_price',
            'cump',
        ]);

        $results = $items->map(function ($i) {
            $label = trim(($i->code ?? '') . ' - ' . ($i->name ?? ''));
            $text = $label !== '-' ? $label : ($i->name ?? '');

            return [
                'id' => $i->id,
                'text' => $text,
                'name' => $i->name,
                'code' => $i->code,
                'price' => (float) ($i->selling_price ?? 0),
                'cump' => (float) ($i->cump ?? 0),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Produits finis + marchandises (stockables et non stockables) pour facturation proforma / vente.
     */
    public function invoiceItems(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Item::query()->billable();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                    ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $items = $query->orderBy('name')->limit(30)->get([
            'id',
            'name',
            'code',
            'selling_price',
            'cump',
            'store_type',
            'status',
        ]);

        $results = $items->map(function ($i) {
            $label = trim(($i->code ?? '') . ' - ' . ($i->name ?? ''));
            $text = $label !== '-' ? $label : ($i->name ?? '');

            return [
                'id' => $i->id,
                'text' => $text,
                'name' => $i->name,
                'code' => $i->code,
                'price' => (float) ($i->selling_price ?? 0),
                'cump' => (float) ($i->cump ?? 0),
                'type_label' => $i->billableTypeLabel(),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function suppliers(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Supplier::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('supplier_name', 'LIKE', '%' . $term . '%')
                  ->orWhere('telephone', 'LIKE', '%' . $term . '%')
                  ->orWhere('supplier_TIN', 'LIKE', '%' . $term . '%');
            });
        }

        $suppliers = $query->orderBy('supplier_name')->limit(30)->get(['id', 'supplier_name']);

        $results = $suppliers->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->supplier_name,
            'name' => $s->supplier_name,
        ])->values();

        return response()->json(['results' => $results]);
    }

    public function rawMaterials(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = RawMaterial::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $rows = $query->orderBy('name')->limit(30)->get(['id', 'name', 'code', 'cump', 'purchase_price', 'cost_price']);

        $results = $rows->map(function ($r) {
            $purchasePrice = (float) ($r->purchase_price ?? 0);
            if ($purchasePrice <= 0) {
                $purchasePrice = (float) ($r->cost_price ?? $r->cump ?? 0);
            }

            $label = trim(($r->code ?? '') . ' - ' . ($r->name ?? ''));
            $text = $label !== '-' ? $label : ($r->name ?? '');

            return [
                'id' => $r->id,
                'text' => $text,
                'name' => $r->name,
                'code' => $r->code,
                'cump' => (float) ($r->cump ?? 0),
                'purchase_price' => $purchasePrice,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function materials(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Material::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $rows = $query->orderBy('name')->limit(30)->get(['id', 'name', 'code', 'cump']);

        $results = $rows->map(function ($m) {
            $label = trim(($m->code ?? '') . ' - ' . ($m->name ?? ''));
            $text = $label !== '-' ? $label : ($m->name ?? '');

            return [
                'id' => $m->id,
                'text' => $text,
                'name' => $m->name,
                'code' => $m->code,
                'cump' => (float) ($m->cump ?? 0),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function articles(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 30);
        if ($limit < 1) $limit = 30;
        if ($limit > 50) $limit = 50;

        $materialsQuery = Material::query();
        $itemsQuery = Item::query()->active();

        if ($term !== '') {
            $materialsQuery->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
            $itemsQuery->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $materials = $materialsQuery->orderBy('name')->limit($limit)->get(['id', 'name', 'cump']);
        $items = $itemsQuery->orderBy('name')->limit($limit)->get(['id', 'name', 'cump']);

        $results = collect();

        foreach ($materials as $m) {
            $results->push([
                'id' => 'material:' . $m->id,
                'type' => 'material',
                'entity_id' => $m->id,
                'text' => $m->name,
                'name' => $m->name,
                'cump' => (float) ($m->cump ?? 0),
            ]);
        }
        foreach ($items as $it) {
            $results->push([
                'id' => 'item:' . $it->id,
                'type' => 'item',
                'entity_id' => $it->id,
                'text' => $it->name,
                'name' => $it->name,
                'cump' => (float) ($it->cump ?? 0),
            ]);
        }

        return response()->json(['results' => $results->values()]);
    }

    public function stockReportArticles(Request $request)
    {
        $results = StockReportArticleSearch::search($request);

        return response()->json(['results' => $results]);
    }

    public function employees(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = HrEmploye::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('firstname', 'LIKE', '%' . $term . '%')
                  ->orWhere('lastname', 'LIKE', '%' . $term . '%')
                  ->orWhere('matricule_no', 'LIKE', '%' . $term . '%');
            });
        }

        $rows = $query->orderBy('firstname')->orderBy('lastname')->limit(30)->get([
            'id',
            'firstname',
            'lastname',
            'matricule_no',
        ]);

        $results = $rows->map(function ($e) {
            $name = trim(($e->firstname ?? '') . ' ' . ($e->lastname ?? ''));
            $text = $e->matricule_no ? ($e->matricule_no . ' - ' . $name) : $name;
            return [
                'id' => $e->id,
                'text' => $text,
                'name' => $name,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function expenses(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = AccountExpense::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('code', 'LIKE', '%' . $term . '%');
            });
        }

        $rows = $query->orderBy('name')->limit(30)->get(['id', 'name', 'cost_price']);

        $results = $rows->map(function ($x) {
            $text = $x->name . '/' . number_format((float) ($x->cost_price ?? 0), 0, ',', ' ');
            return [
                'id' => $x->id,
                'text' => $text,
                'name' => $x->name,
                'cost_price' => (float) ($x->cost_price ?? 0),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }
}


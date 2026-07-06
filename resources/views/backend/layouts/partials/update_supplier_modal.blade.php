{{-- Partial: bouton "Modifier le fournisseur" + modal. Affiche le bouton seulement si @can('supplier.edit').
    Variables attendues:
    - $updateSupplierRoute: route('admin.xxx.update-supplier', $identifier)
    - $currentSupplierId: id du fournisseur actuel (pour pré-sélection)
    - $suppliers: collection Supplier (liste déroulante)
    - $modalId: (optionnel) id du modal, défaut 'updateSupplierModal'
    - $entityLabel: (optionnel) libellé pour le titre du modal, ex. "cette demande d'achat"
--}}
@php $modalId = $modalId ?? 'updateSupplierModal'; $entityLabel = $entityLabel ?? __('messages.cet enregistrement'); @endphp
@if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->can('supplier.edit'))
<button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#{{ $modalId }}" title="@lang('messages.Modifier du fournisseur')">
    <i class="fa fa-edit"></i> @lang('messages.Modifier du fournisseur')
</button>
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ $updateSupplierRoute }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">@lang('messages.Modifier du fournisseur')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">@lang('messages.Choisissez le nouveau fournisseur pour') {{ $entityLabel }}.</p>
                    <div class="form-group">
                        <label for="supplier_id_{{ $modalId }}">@lang('messages.supplier') <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id_{{ $modalId }}" class="form-control" required>
                            <option value="">-- @lang('messages.Choisir un fournisseur') --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (old('supplier_id', $currentSupplierId) == $supplier->id) ? 'selected' : '' }}>{{ $supplier->supplier_name ?? $supplier->name ?? $supplier->id }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

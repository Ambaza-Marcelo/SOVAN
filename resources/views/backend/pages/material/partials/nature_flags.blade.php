{{-- Cases à cocher de nature article + logique d'affichage conditionnelle --}}
<div class="row mb-3">
    <div class="col-md-12">
        <h5 class="mb-3">Nature de l'article</h5>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_stockable" name="is_stockable" value="1"
                    {{ old('is_stockable', isset($material) ? ($material->is_stockable ?? true) : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_stockable">Article stockable</label>
            </div>
            <small class="text-muted">Active magasin, quantités et seuil.</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_amortizable" name="is_amortizable" value="1"
                    {{ old('is_amortizable', isset($material) ? $material->is_amortizable : false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_amortizable">Actif amortissable</label>
            </div>
            <small class="text-muted">Active le plan d'amortissement linéaire.</small>
        </div>
    </div>
</div>

<div id="stockable-hint" class="alert alert-info py-2" style="display:none;">
    Article stockable : magasin et quantités obligatoires.
</div>
<div id="non-stockable-hint" class="alert alert-secondary py-2" style="display:none;">
    Article non stockable : pas d'affectation magasin (ex: logiciel interne).
</div>
<div id="amortizable-hint" class="alert alert-info py-2" style="display:none;">
    Actif amortissable : durée obligatoire. La date de mise en service est posée automatiquement à l'approbation (entrée si non stockable, sortie si stockable).
</div>
<div id="immobilization-hint" class="alert alert-warning py-2" style="display:none;">
    Compte d'immobilisation (classe 2) requis pour l'imputation comptable à l'approbation.
</div>

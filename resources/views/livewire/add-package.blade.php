<div>
    <p class="small">Créez ou modifiez un package en remplissant le formulaire ci-dessous.</p>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group form-group-default">
                    <label>Nom du package</label>
                    <input wire:model="name" type="text" class="form-control" placeholder="Entrez le nom" required/>
                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    <label>Départements:</label>
                    <select wire:model.live="department" class="form-control selectpicker" data-live-search="true" title="Sélectionnez un département">
                        <option value="">Sélectionnez un département</option>
                        @foreach ($departments as $dep)
                            <option value="{{$dep->id}}">{{ $dep->name }}</option>
                        @endforeach
                    </select>
                    @error('department') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    <label>Ajouter des examens:</label>
                    {{-- Utilisation de select multiple pour les tests --}}
                    <select wire:model.live="selectedTests" class="form-control selectpicker" multiple data-live-search="true" title="Sélectionnez les examens">
                        @foreach($allTests as $test) {{-- Nous aurons besoin de passer tous les tests depuis le composant --}}
                            <option value="{{ $test->id }}" {{ in_array($test->id, $selectedTests) ? 'selected' : '' }}>
                                {{ $test->name }} = {{ number_format($test->amount, 0, ',', ' ') }} FG
                            </option>
                        @endforeach
                    </select>
                    @error('selectedTests') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    <label>Ajouter des services:</label>
                    @if($department)
                        {{-- Utilisation de select multiple pour les services --}}
                        <select wire:model.live="selectedServices" class="form-control selectpicker" multiple data-live-search="true" title="Sélectionnez les services">
                            @foreach($allServicesByDepartment as $service) {{-- Nous aurons besoin de passer les services filtrés par département --}}
                                <option value="{{ $service->id }}" {{ in_array($service->id, $selectedServices) ? 'selected' : '' }}>
                                    {{ $service->name }} = {{ number_format($service->amount, 0, ',', ' ') }} FG
                                </option>
                            @endforeach
                        </select>
                        @error('selectedServices') <span class="text-danger">{{ $message }}</span> @enderror
                    @else
                        <div class="alert alert-warning">Veuillez d'abord sélectionner un département pour ajouter des services.</div>
                    @endif
                </div>
            </div>

            {{-- <div class="col-sm-6">
                <div class="form-group form-group-default">
                    <label>Montant Total du Package</label>
                    <input type="text" class="form-control"
                        value="{{ number_format($totalAmount, 0, ',', ' ') }} FG"
                        readonly style="background-color: #f8f9fa; font-weight: bold; color: #28a745;">
                </div>
            </div> --}}

            <div class="col-sm-6">
                <div class="form-group form-group-default">
                    <label>Description</label>
                    <textarea wire:model="description" class="form-control" placeholder="Description"></textarea>
                </div>
            </div>
        </div>

        <div class="modal-footer border-0">
            <button type="submit" class="btn btn-primary">
                {{ $editMode ? 'Mettre à jour' : 'Sauvegarder' }}
            </button>
            <a href="{{ route('package.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

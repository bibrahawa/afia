<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Department;
use App\Models\Tax;
use App\Models\Hospital;


class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $services = Service::with('department')->orderBy('name')->get();
        $departments = Department::select('id','name')->orderBy('name')->get();
        return view('services.index', compact('services' , 'departments'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'name'=>'required',
            'amount'=>'required|numeric',
            'department_id' => 'required|exists_etablissement:departments,id',
            'famille_acte' => ['nullable', \Illuminate\Validation\Rule::enum(\App\Enums\Assurance\FamilleActe::class)],
        ], ['name.required' => 'Indiquez le nom de l\'acte.', 'amount.required' => 'Indiquez le prix.', 'amount.numeric' => 'Le prix doit être un nombre.']);

        $tax = Hospital::first()->tax_percent;

        if($request->with_tax) {
            $tax_cal = 100 + $tax;
            $request['amount'] = $request->amount*100/$tax_cal;
        }

        Service::create($request->only(['name', 'amount', 'department_id', 'famille_acte']));
        return back()->with('success', 'Acte ajouté au catalogue.');
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
         $tax = Hospital::first()->tax_percent;
        $request->validate(['id' => 'required|exists_etablissement:services,id', 'name' => 'required', 'amount' => 'required|numeric', 'department_id' => 'required|exists_etablissement:departments,id',
            'famille_acte' => ['nullable', \Illuminate\Validation\Rule::enum(\App\Enums\Assurance\FamilleActe::class)]]);
        $data = Service::find ( $request->id );
        $data->name = ($request->name);

        if($request->with_tax) {

            $tax_cal = 100 + $tax;
            $request['amount'] = $request->amount*100/$tax_cal;
        }

        $data->amount = ($request->amount);
        $data->department_id = ($request->department_id);
        $data->famille_acte = $request->input('famille_acte', $data->famille_acte);
        $data->save ();
        return back()->with('success', 'Acte modifié. Les factures déjà émises gardent leur ancien prix.');
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {

      $service = Service::find($request->id);

      if (! $service) {
          return back()->with('error', 'Acte introuvable.');
      }

      // CORRIGÉ — supprimait un acte déjà utilisé : consultations et factures
      // perdaient la référence de ce qui avait été fait et facturé.
      $utilise = \Illuminate\Support\Facades\DB::table('consultation_service')->where('service_id', $service->id)->exists()
          || \App\Models\InvoiceItem::whereIn('coverage_type_type', \App\Support\Facturation\TypesFacturables::variantes(Service::class))
              ->where('coverage_type_id', $service->id)->exists();

      if ($utilise) {
          return back()->with('error', "« {$service->name} » a déjà été utilisé en consultation ou facturé : il ne peut pas être supprimé. Masquez-le : il ne sera plus proposé, l'historique reste intact.");
      }

      $service->delete();
      return back()->with('success', 'Acte supprimé.');
    }

    /** Lot S3 — Masquer / réafficher : plus proposé au choix, historique intact. */
    public function basculerVisibilite(Service $service)
    {
        $service->update(['actif' => ! $service->actif]);

        // Un motif de rendez-vous qui facture cet acte à l'arrivée continue de le facturer :
        // on le signale pour que l'administrateur choisisse un autre acte.
        $motifs = \App\Models\MotifRdv::where('service_id', $service->id)->pluck('nom');
        $avertissement = ! $service->actif && $motifs->isNotEmpty()
            ? ' Attention : il est encore facturé à l\'arrivée pour le(s) motif(s) ' . $motifs->implode(', ') . '.'
            : '';

        return back()->with('success', ($service->actif ? "« {$service->name} » est de nouveau proposé." : "« {$service->name} » est masqué : il n'est plus proposé, l'historique reste intact.") . $avertissement);

    //   if(count($service->consultations)) {

    //     return back()->with('error', 'Service cannot be deleted...');
    //   } else {
    //     $service->delete();
    //     return back()->with('success', 'Service deleted successfully');
    //   }


    }

}

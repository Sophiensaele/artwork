<?php

namespace App\Http\Controllers;

use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ContractTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return ContractType::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        ContractType::create([
            'name' => $request->name
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContractType  $contractType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ContractType $contractType): \Illuminate\Http\RedirectResponse
    {
        $contractType->delete();
        return Redirect::back()->with('success', 'ContractType deleted');
    }

    public function forceDelete(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);

        $contractType->forceDelete();

        return Redirect::route('projects.settings.trashed')->with('success', 'ContractType deleted');
    }

    public function restore(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);

        $contractType->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'ContractType restored');
    }
}

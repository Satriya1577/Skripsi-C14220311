<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;


class MaterialTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Material $material)
    {
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialTransaction $material_transaction)
    {
        //
    }
}

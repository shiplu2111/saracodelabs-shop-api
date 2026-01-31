<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;

class ShippingChargeController extends Controller
{
    public function index()
    {
        return ShippingCharge::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'city' => 'required|string|unique:shipping_charges,city',
            'amount' => 'required|numeric|min:0',
        ]);

        // Store city in lowercase to match easily later
        $charge = ShippingCharge::create([
            'city' => strtolower(trim($request->city)),
            'amount' => $request->amount
        ]);

        return response()->json($charge, 201);
    }

    public function update(Request $request, $id)
    {
        $charge = ShippingCharge::findOrFail($id);

        $request->validate([
            'city' => 'required|string|unique:shipping_charges,city,'.$id,
            'amount' => 'required|numeric|min:0',
        ]);

        $charge->update([
            'city' => strtolower(trim($request->city)),
            'amount' => $request->amount
        ]);

        return response()->json($charge);
    }

    public function destroy($id)
    {
        ShippingCharge::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
}

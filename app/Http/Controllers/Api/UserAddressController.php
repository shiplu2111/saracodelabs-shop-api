<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    /**
     * Get logged-in user's addresses
     */
    public function index()
    {
        $addresses = auth()->user()->addresses()->orderBy('is_default', 'desc')->get();
        return response()->json($addresses);
    }

    /**
     * Store a new address
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'is_default' => 'boolean'
        ]);

        // If setting as default, make all other addresses non-default
        if ($request->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        $address = auth()->user()->addresses()->create($request->all());

        return response()->json($address, 201);
    }

    /**
     * Update an address
     */
    public function update(Request $request, $id)
    {
        $address = auth()->user()->addresses()->findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default) {
            auth()->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json($address);
    }

    /**
     * Delete an address
     */
    public function destroy($id)
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }
}

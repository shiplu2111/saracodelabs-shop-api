<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        return response()->json(PaymentGateway::all());
    }

    public function update(Request $request, $id)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $request->validate([
            'credentials' => 'nullable|array',
            'is_active' => 'boolean',
            'is_sandbox' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        $data = $request->only(['is_active', 'is_sandbox']);

        if ($request->has('credentials')) {
            $data['credentials'] = $request->credentials;
        }

        // Upload Logo
        if ($request->hasFile('logo')) {
            // 🔥 FIX: ডিলিট করার সময় Raw Path নিতে হবে, URL নয়
            $oldLogoPath = $gateway->getRawOriginal('logo');

            if ($oldLogoPath && Storage::exists('public/' . $oldLogoPath)) {
                Storage::delete('public/' . $oldLogoPath);
            }

            $path = $request->file('logo')->store('gateways', 'public');
            $data['logo'] = $path;
        }

        $gateway->update($data);

        // Refresh model to apply Accessor (URL format)
        $gateway->refresh();

        return response()->json([
            'message' => 'Gateway updated successfully',
            'data' => $gateway
        ]);
    }
}

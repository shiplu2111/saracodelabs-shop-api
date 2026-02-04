<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualPaymentMethod;
use App\Http\Requests\StoreManualPaymentMethodRequest;
use App\Http\Requests\UpdateManualPaymentMethodRequest;
use App\Http\Resources\ManualPaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualPaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $methods = ManualPaymentMethod::latest()->get();
        return ManualPaymentResource::collection($methods);
    }

    /**
     * Public endpoint for active methods
     */
    public function activeMethods()
    {
        $methods = ManualPaymentMethod::where('is_active', true)->get();
        return ManualPaymentResource::collection($methods);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreManualPaymentMethodRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('qr_code')) {
            $validated['qr_code'] = $request->file('qr_code')->store('payment_qrs', 'public');
        }

        $method = ManualPaymentMethod::create($validated);

        return response()->json([
            'message' => 'Payment method created successfully',
            'data' => new ManualPaymentResource($method)
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManualPaymentMethodRequest $request, $id)
    {
        $method = ManualPaymentMethod::findOrFail($id);
        $validated = $request->validated();

        if ($request->hasFile('qr_code')) {
            // Delete Old QR
            $oldPath = $method->getRawOriginal('qr_code');
            if ($oldPath && Storage::exists('public/' . $oldPath)) {
                Storage::delete('public/' . $oldPath);
            }

            // Store New QR
            $validated['qr_code'] = $request->file('qr_code')->store('payment_qrs', 'public');
        }

        $method->update($validated);
        $method->refresh();

        return response()->json([
            'message' => 'Payment method updated successfully',
            'data' => new ManualPaymentResource($method)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $method = ManualPaymentMethod::findOrFail($id);

        $path = $method->getRawOriginal('qr_code');
        if ($path && Storage::exists('public/' . $path)) {
            Storage::delete('public/' . $path);
        }

        $method->delete();

        return response()->json(['message' => 'Payment method deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get All Orders (With Filters)
     */
    public function index(Request $request)
    {
        $query = Order::with('items.product')->latest();

        // Filter by Status (Optional)
        if ($request->has('status') && $request->status != 'all') {
            $query->where('order_status', $request->status);
        }

        // Search by Order Number or Phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('order_number', 'like', "%$search%")
                  ->orWhere('customer_phone', 'like', "%$search%");
        }

        $orders = $query->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Show Single Order Details
     */
    public function show($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return new OrderResource($order);
    }

    /**
     * Update Order Status (Admin Action)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed',
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'order_status' => $request->order_status,
            // Only update payment status if provided
            'payment_status' => $request->payment_status ?? $order->payment_status,
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'current_status' => $order->order_status
        ]);
    }

    /**
     * Delete Order (Optional - usually we don't delete orders, just cancel them)
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('order.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::findOrFail($id);
        $order->items()->delete(); // Delete items first
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}

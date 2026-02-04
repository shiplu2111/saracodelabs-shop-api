<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    // List all reviews (Pending first)
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name', 'product:id,name']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reviews = $query->latest()->paginate(20);
        return response()->json($reviews);
    }

    // Approve/Reject Review
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected,pending']);

        Review::findOrFail($id)->update(['status' => $request->status]);

        return response()->json(['message' => 'Review status updated']);
    }

    // Delete Review
    public function destroy($id)
    {
        Review::findOrFail($id)->delete();
        return response()->json(['message' => 'Review deleted']);
    }
}

<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;   // 🔥 Import Custom Request
use App\Http\Requests\UpdateReviewRequest;  // 🔥 Import Custom Request
use App\Models\Order;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * 📝 1. Submit Review
     * Uses: StoreReviewRequest for automatic validation
     */
    public function store(StoreReviewRequest $request) // 🔥 Type-hinted
    {
        $user = Auth::user();

        // 1. Business Logic: Check Verified Purchase (Delivered)
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('order_status', 'delivered')
            ->whereHas('items', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['message' => 'You can only review products that you have purchased and received.'], 403);
        }

        // 2. Business Logic: Check Duplicate Review
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product. You can edit your existing review.',
                'review_id' => $existingReview->id
            ], 403);
        }

        // Image Upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reviews', 'public');
        }

        // Save Data (Using validated() ensures only valid fields are passed)
        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $request->validated('product_id'),
            'rating'     => $request->validated('rating'),
            'comment'    => $request->validated('comment'),
            'image'      => $imagePath,
            'status'     => 'pending'
        ]);

        return response()->json(['message' => 'Review submitted successfully! Waiting for approval.', 'data' => $review], 201);
    }

    /**
     * ✏️ 2. Edit Review
     * Uses: UpdateReviewRequest for automatic validation
     */
    public function update(UpdateReviewRequest $request, $id) // 🔥 Type-hinted
    {
        // Find review ensuring it belongs to the authenticated user
        $review = Review::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $data = $request->validated(); // Get safe data
        $data['status'] = 'pending';   // Reset status to pending on update

        // Handle Image Update
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($review->image) {
                Storage::disk('public')->delete($review->image);
            }
            $data['image'] = $request->file('image')->store('reviews', 'public');
        }

        $review->update($data);

        return response()->json(['message' => 'Review updated successfully! Waiting for approval.', 'data' => $review]);
    }

    /**
     * 🗑️ 3. Delete Review (Optional: User can delete their review)
     */
    public function destroy($id)
    {
        $review = Review::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if ($review->image) {
            Storage::disk('public')->delete($review->image);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }

    /**
     * 👀 Get Reviews (Public - Only Approved)
     */
    public function index($productId)
    {
        $reviews = Review::with('user:id,name,avatar') // User info load korchi
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }
}

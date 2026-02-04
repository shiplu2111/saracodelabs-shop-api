<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    /**
     * 🌍 Public: Get Active Sliders
     */
    public function activeSliders()
    {
        $sliders = Slider::where('status', 'active')
            ->orderBy('serial', 'asc')
            ->get()
            ->map(function ($slider) {
                return $this->formatSlider($slider);
            });

        return response()->json($sliders);
    }

    /**
     * 🔒 Admin: List all sliders
     */
    public function index()
    {
        $sliders = Slider::orderBy('serial', 'asc')
            ->get()
            ->map(function ($slider) {
                return $this->formatSlider($slider);
            });

        return response()->json($sliders);
    }

    /**
     * 🔒 Admin: Create New Slider
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link'     => 'nullable|url',
            'serial'   => 'required|integer',
            'status'   => 'required|in:active,inactive',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('sliders', 'public');
        }

        $slider = Slider::create([
            'title'    => $request->title,
            'subtitle' => $request->subtitle,
            'image'    => $imagePath,
            'link'     => $request->link,
            'serial'   => $request->serial,
            'status'   => $request->status,
        ]);

        return response()->json([
            'message' => 'Slider created successfully',
            'data'    => $this->formatSlider($slider)
        ], 201);
    }

    /**
     * 🔒 Admin: Get Single Slider
     */
    public function show($id)
    {
        $slider = Slider::findOrFail($id);
        return response()->json($this->formatSlider($slider));
    }

    /**
     * 🔒 Admin: Update Slider
     */
    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $request->validate([
            'title'    => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link'     => 'nullable|url',
            'serial'   => 'required|integer',
            'status'   => 'required|in:active,inactive',
        ]);

        $data = $request->only(['title', 'subtitle', 'link', 'serial', 'status']);

        if ($request->hasFile('image')) {
            // Delete Old Image
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }
            $data['image'] = $request->file('image')->store('sliders', 'public');
        }

        $slider->update($data);

        return response()->json([
            'message' => 'Slider updated successfully',
            'data'    => $this->formatSlider($slider)
        ]);
    }

    /**
     * 🔒 Admin: Delete Slider
     */
    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);

        if ($slider->image && Storage::disk('public')->exists($slider->image)) {
            Storage::disk('public')->delete($slider->image);
        }

        $slider->delete();

        return response()->json(['message' => 'Slider deleted successfully']);
    }

    /**
     * 🛠 Helper: Format Response
     * Replaces the relative 'image' path with the Full URL directly.
     */
    private function formatSlider($slider)
    {
        // 🔥 Overwrite the 'image' field directly with the full URL
        $slider->image = url('storage/' . $slider->image);
        return $slider;
    }
}

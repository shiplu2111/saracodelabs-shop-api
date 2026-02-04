<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * 🟢 Public: Get All Settings (Formatted with Full Image URLs)
     */
    public function index()
    {
        // define keys that are images
        $imageKeys = ['site_logo', 'site_favicon', 'meta_image'];

        $settings = Setting::all()->mapWithKeys(function ($item) use ($imageKeys) {
            // If the key is an image key, convert to Full URL
            if (in_array($item->key, $imageKeys) && $item->value) {
                return [$item->key => url('storage/' . $item->value)];
            }
            // Otherwise return normal value
            return [$item->key => $item->value];
        });

        return response()->json($settings);
    }

    /**
     * 🔴 Admin: Update General Settings
     */
    public function update(Request $request)
    {
        // Loop through all inputs and update text settings
        foreach ($request->except(['site_logo', 'site_favicon']) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Handle File Uploads (Logo)
        if ($request->hasFile('site_logo')) {
            $this->uploadFile($request->file('site_logo'), 'site_logo');
        }

        // Handle File Uploads (Favicon)
        if ($request->hasFile('site_favicon')) {
            $this->uploadFile($request->file('site_favicon'), 'site_favicon');
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * 🛠 Helper: File Upload Logic
     */
    private function uploadFile($file, $key)
    {
        // 1. Delete old file if exists
        $oldFile = Setting::where('key', $key)->value('value');
        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
            Storage::disk('public')->delete($oldFile);
        }

        // 2. Upload new file
        $path = $file->store('settings', 'public');

        // 3. Save path to DB
        Setting::updateOrCreate(['key' => $key], ['value' => $path]);
    }
}

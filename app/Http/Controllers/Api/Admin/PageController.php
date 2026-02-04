<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    // 🟢 Public: Get Page by Slug (e.g. /api/pages/privacy-policy)
    public function getBySlug($slug)
    {
        $page = Page::where('slug', $slug)->where('status', 'active')->firstOrFail();
        return response()->json($page);
    }

    // 🔴 Admin: CRUD
    public function index()
    {
        return response()->json(Page::latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required', 'content' => 'required']);

        Page::create([
            'title' => $request->title,
            'slug'  => Str::slug($request->title), // Auto generate slug
            'content' => $request->content, // HTML from Rich Text Editor
            'status' => $request->status ?? 'active'
        ]);

        return response()->json(['message' => 'Page created']);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        $page->update([
            'title' => $request->title,
            'content' => $request->content,
            'status' => $request->status
        ]);
        return response()->json(['message' => 'Page updated']);
    }

    public function destroy($id) { Page::destroy($id); return response()->json(['message' => 'Deleted']); }
}

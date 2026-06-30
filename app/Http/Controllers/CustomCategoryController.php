<?php

namespace App\Http\Controllers;

use App\Models\CustomCategory;
use Illuminate\Http\Request;

class CustomCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'name' => 'required|string|max:50',
        ]);

        CustomCategory::firstOrCreate([
            'user_id' => auth()->id(),
            'type'    => $request->type,
            'name'    => $request->name,
        ]);

        return back()->with('success', 'Kategori ditambahkan!');
    }

    public function destroy(CustomCategory $category)
    {
        abort_if($category->user_id !== auth()->id(), 403);
        $category->delete();
        return back()->with('success', 'Kategori dihapus!');
    }
}

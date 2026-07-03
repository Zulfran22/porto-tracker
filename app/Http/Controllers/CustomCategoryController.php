<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomCategoryRequest;
use App\Models\CustomCategory;

class CustomCategoryController extends Controller
{
    public function store(CustomCategoryRequest $request)
    {
        CustomCategory::firstOrCreate([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'name' => $request->name,
        ]);

        return back()->with('success', 'Kategori ditambahkan!');
    }

    public function destroy(CustomCategory $category)
    {
        $this->authorize('delete', $category);
        $category->delete();

        return back()->with('success', 'Kategori dihapus!');
    }
}

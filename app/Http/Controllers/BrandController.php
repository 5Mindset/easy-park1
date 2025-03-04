<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Exports\BrandsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Tampilkan daftar brand dengan fitur pencarian & sorting.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = $request->input('sort', 'desc');

        $brands = Brand::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%{$search}%");
        })->orderBy($sortField, $sortOrder)->paginate($perPage);

        return view('admin.brands.index', compact('brands', 'sortOrder', 'sortField'));
    }

    /**
     * Tampilkan form tambah brand.
     */
    public function create()
    {
        return view('admin.brands.create');
    }

    /**
     * Simpan brand baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:brands|max:255',
            'description' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('brand/upload', 'public')
            : null;

        Brand::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return redirect()->route('brands.index', ['sort' => 'desc', 'sort_field' => 'created_at'])
            ->with('success', 'Brand berhasil ditambahkan!');
    }

    /**
     * Tampilkan detail satu brand.
     */
    public function show($slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();
        return view('admin.brands.show', compact('brand'));
    }

    /**
     * Tampilkan form edit brand berdasarkan slug.
     */
    public function edit($slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();
        return view('admin.brands.edit', compact('brand'));
    }

    /**
     * Update data brand di database.
     */
    public function update(Request $request, $slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $request->validate([
            'name' => 'required|max:255|unique:brands,name,' . $brand->id,
            'description' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $imagePath = $request->file('image')->store('brand/upload', 'public');
            $brand->image = $imagePath;
        }

        $brand->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'image' => $brand->image,
        ]);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui!');
    }

    /**
     * Hapus brand dari database.
     */
    public function destroy(Brand $brand)
    {
        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dihapus!');
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // ← TAMBAHAN

class ProductController extends Controller
{
    /**
     * GET /products
     * Menampilkan semua produk aktif dengan pagination (tanpa auth token)
     *
     * Query params:
     * - ?search=nama        → filter by nama produk (BONUS)
     * - ?category_id=1      → filter by kategori (BONUS)
     * - ?per_page=10        → jumlah item per halaman
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')->where('is_active', true);

        // BONUS: Filter pencarian berdasarkan nama produk
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // BONUS: Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Data produk berhasil diambil',
            'data'    => $products,
        ], 200);
    }

    /**
     * POST /products
     * Membuat produk baru (category_id, name, description, price, stock, foto) - butuh auth token
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            // BONUS: Upload foto produk
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // BONUS: Simpan foto ke storage jika ada
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => Str::slug($request->name), // ← generate slug otomatis
            'description' => $request->description,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'foto'        => $fotoPath,                 // ← fix: masuk ke create()
            'is_active'   => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat',
            'data'    => $product->load('category'),
        ], 201);
    }

    /**
     * GET /products/{id}
     * Menampilkan detail produk beserta kategori (tanpa auth token)
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data'    => $product,
        ], 200);
    }

    /**
     * PUT /products/{id}
     * Memperbarui data produk - butuh auth token
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            // BONUS: Upload foto produk
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Kumpulkan semua data update dalam satu array
        $data = $request->only(['category_id', 'name', 'description', 'price', 'stock']);

        // Slug ikut update kalau name berubah
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name); // ← generate slug otomatis
        }

        // BONUS: Update foto - hapus foto lama jika ada foto baru
        if ($request->hasFile('foto')) {
            if ($product->foto) {
                Storage::disk('public')->delete($product->foto);
            }
            $data['foto'] = $request->file('foto')->store('products', 'public'); // ← fix: masuk $data
        }

        $product->update($data); // ← satu kali update, beres

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data'    => $product->load('category'),
        ], 200);
    }

    /**
     * PATCH /products/{id}/toggle
     * Mengaktifkan / menonaktifkan produk (toggle is_active) - butuh auth token
     */
    public function toggle(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $product->update([
            'is_active' => !$product->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status produk berhasil diubah menjadi ' . ($product->is_active ? 'aktif' : 'nonaktif'),
            'data'    => $product,
        ], 200);
    }

    /**
     * DELETE /products/{id}
     * Menghapus produk - butuh auth token
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        // BONUS: Hapus foto dari storage jika ada
        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus',
        ], 200);
    }
}
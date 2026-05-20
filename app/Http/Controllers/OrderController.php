<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * GET /orders
     * Menampilkan semua pesanan milik user yang login - butuh auth token
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pesanan berhasil diambil',
            'data'    => $orders,
        ], 200);
    }

    /**
     * POST /orders
     * Membuat pesanan baru dengan array item produk - butuh auth token
     *
     * Body contoh:
     * {
     *   "items": [
     *     { "product_id": 1, "quantity": 2 },
     *     { "product_id": 3, "quantity": 1 }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $totalPrice = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Produk dengan ID {$item['product_id']} tidak ditemukan atau tidak aktif",
                    ], 404);
                }

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stok produk '{$product->name}' tidak mencukupi (sisa: {$product->stock})",
                    ], 409);
                }

                $subtotal     = $product->price * $item['quantity'];
                $totalPrice  += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'subtotal'   => $subtotal,
                ];

                // Kurangi stok produk
                $product->decrement('stock', $item['quantity']);
            }

            // Buat order
            $order = Order::create([
                'user_id'     => $request->user()->id,
                'total_price' => $totalPrice,
                'status'      => 'pending',
            ]);

            // Buat order items
            foreach ($orderItems as &$orderItem) {
                $orderItem['order_id'] = $order->id;
            }
            OrderItem::insert($orderItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data'    => $order->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat pesanan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /orders/{id}
     * Menampilkan detail pesanan beserta item-itemnya - butuh auth token
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::with('items.product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data'    => $order,
        ], 200);
    }

    /**
     * PATCH /orders/{id}/status
     * Memperbarui status pesanan - butuh auth token
     *
     * Body contoh:
     * { "status": "processing" }
     *
     * Status yang tersedia: pending, processing, shipped, delivered, cancelled
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui menjadi ' . $request->status,
            'data'    => $order,
        ], 200);
    }
}
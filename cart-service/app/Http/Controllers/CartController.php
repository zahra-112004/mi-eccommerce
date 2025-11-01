<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;
use App\Models\Cart;

class CartController extends Controller
{
    private $client;


    public function __construct()
    {
        $appEnv = env('APP_ENV', 'local');
        $baseUri = $appEnv == 'local' ? 'http://localhost:3000' : 'http://product-service:3000';
        $this->client = new \GuzzleHttp\Client(['base_uri' => $baseUri]);
    }

    public function getProduct($productId = null)
    {
        try {
            $url = $productId ? "/products/{$productId}" : '/products';
            $response = $this->client->request('GET', $url);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && isset($responseData['data'])) {
                return $responseData['data'];
            }

            return null;
        } catch (\Throwable $th) {
            Log::error([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return null;
        }
    }

     public function index()
    {
        try {
            $cartItems = Cart::orderBy('created_at', 'desc')->get();
            return ResponseHelper::successResponse('Item keranjang berhasil diambil', $cartItems);
        } catch (\Throwable $th) {
            Log::error([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $cartItem = Cart::find($id);
            if (!$cartItem)return ResponseHelper::errorResponse('Item keranjang tidak ditemukan', 404);

            return ResponseHelper::successResponse('Item keranjang berhasil diambil', $cartItem);
        } catch (\Throwable $th) {
            Log::error([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validate = $this->validate($request, [
            'product_id'    => 'required|integer',
            'quantity'      => 'required|integer'
        ]);

        try {
            $product = $this->getProduct($validate['product_id']);

            if (!$product) return ResponseHelper::errorResponse('Produk tidak ditemukan', 404);

            $cartItem = Cart::create([
                'product_id'    => $validate['product_id'],
                'name'          => $product['name'],
                'quantity'      => $validate['quantity'],
                'price'         => $product['price'] *$validate['quantity']
            ]);

            if (!$cartItem) return ResponseHelper::errorResponse('Gagal membuat item keranjang', 500);

            return ResponseHelper::successResponse('Item keranjang berhasil dibuat', $cartItem);
        } catch (\Throwable $th) {
            Log::error([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validate = $this->validate($request, [
            'quantity'      => 'required|integer'
        ]);

        try {
            $cartItem = Cart::find($id);
            if (!$cartItem)return ResponseHelper::errorResponse('Item keranjang tidak ditemukan', 404);

            // get original price from product
            $product = $this->getProduct($cartItem->product_id);

            if (!$product || !isset($product['price'])) {
                return ResponseHelper::errorResponse('Produk tidak ditemukan atau harga tidak tersedia', 404);
            }

            $originalPrice = $product['price'];

            $cartItem->quantity = $validate['quantity'];
            $cartItem->price = $originalPrice * $validate['quantity'];
            $cartItem->save();

            return ResponseHelper::successResponse('Item keranjang berhasil diperbarui', $cartItem);
        } catch (\Throwable $th) {
            Log::error([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);

            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

     public function destroy($id)
   {
       try {
           $cartItem = Cart::find($id);
           if (!$cartItem)return ResponseHelper::errorResponse('Item keranjang tidak ditemukan', 404);

           $cartItem->delete();

           return ResponseHelper::successResponse('Item keranjang berhasil dihapus');
       } catch (\Throwable $th) {
           Log::error([
               'message' => $th->getMessage(),
               'file' => $th->getFile(),
               'line' => $th->getLine()
           ]);

           return ResponseHelper::errorResponse($th->getMessage());
       }
   }
}

?>

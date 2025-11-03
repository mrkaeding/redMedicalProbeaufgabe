<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\RedProvider\OrderProvider;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($name = $request->query('name')) {
            $query->where('name', 'like', "%".$name."%");
        }

        $sort = $request->query('sort', 'created_at');
        $dir = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['name', 'created_at'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $dir);

        $orders = $query->get();

        return OrderResource::collection($orders);
    }
}

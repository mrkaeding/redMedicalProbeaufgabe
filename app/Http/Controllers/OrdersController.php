<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Resources\OrderResource;
use App\Jobs\SyncOrderStatusJob;
use App\Models\Order;
use App\Services\RedProvider\OrderProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function show(string $id)
    {
        $order = Order::query()->findOrFail($id);
        return new OrderResource($order);
    }

    public function store(Request $request, OrderProvider $provider)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([OrderType::CONNECTOR->value, OrderType::VPN_CONNECTION->value])],
        ]);

        $order = new Order();
        $order->name = $data['name'];
        $order->type = OrderType::from($data['type']);
        $order->status = OrderStatus::ORDERED;
        $order->save();

        $providerOrder = $provider->createOrder($order->type);
        $order->provider_reference = $providerOrder->id;
        $order->status = $providerOrder->status;
        $order->save();

        SyncOrderStatusJob::dispatch($order->id)->delay(now()->addSeconds(10));

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(string $id, OrderProvider $provider)
    {
        $order = Order::query()->findOrFail($id);
        if ($order->status !== OrderStatus::COMPLETED) {
            return response()->json([
                'message' => 'Order can only be deleted when status is completed.',
            ], 422);
        }

        if ($order->provider_reference) {
            try {
                $provider->deleteOrder($order->provider_reference);
            } catch (\Throwable $e) {
            }
        }

        $order->delete();

        return response()->noContent();
    }
}

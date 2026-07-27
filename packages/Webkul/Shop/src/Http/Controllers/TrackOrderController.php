<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\View\View;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Requests\TrackOrderRequest;

class TrackOrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected OrderRepository $orderRepository) {}

    /**
     * Render the public order-tracking lookup form.
     */
    public function index(): View
    {
        return view('shop::track-order.index');
    }

    /**
     * Look up an order by order number + email, scoped to the current
     * channel. No authentication required — this is the guest-facing
     * counterpart to the customer account order view.
     */
    public function track(TrackOrderRequest $request): View
    {
        $currentChannel = core()->getCurrentChannel();

        $order = $this->orderRepository->getModel()
            ->newQuery()
            ->where('increment_id', $request->input('order_id'))
            ->where('customer_email', $request->input('email'))
            ->when($currentChannel, fn ($query) => $query->where('channel_id', $currentChannel->id))
            ->first();

        return view('shop::track-order.index', [
            'order'    => $order,
            'searched' => true,
        ]);
    }
}

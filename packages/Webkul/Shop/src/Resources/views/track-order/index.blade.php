<x-shop::layouts>
    <x-slot:title>
        @lang('shop::app.track_order.page_title')
    </x-slot>

    <div class="container mx-auto mt-10 max-w-3xl px-5 max-md:mt-6 max-md:px-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-8 max-sm:p-5">
            <h1 class="text-2xl font-medium max-md:text-xl">
                @lang('shop::app.track_order.heading')
            </h1>

            <p class="mt-2 text-sm text-zinc-600">
                @lang('shop::app.track_order.intro')
            </p>

            <form
                method="POST"
                action="{{ route('shop.track_order.track') }}"
                class="mt-6 grid gap-5 sm:grid-cols-2"
            >
                @csrf

                <div>
                    <label
                        for="order_id"
                        class="mb-1.5 block text-sm font-medium text-zinc-900"
                    >
                        @lang('shop::app.track_order.order_number')
                    </label>

                    <input
                        id="order_id"
                        name="order_id"
                        type="text"
                        required
                        autocomplete="off"
                        value="{{ old('order_id', $order->increment_id ?? '') }}"
                        class="block w-full rounded-lg border border-zinc-200 px-4 py-3 text-sm focus:border-navyBlue focus:outline-none focus:ring-1 focus:ring-navyBlue"
                    >

                    @error('order_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        for="email"
                        class="mb-1.5 block text-sm font-medium text-zinc-900"
                    >
                        @lang('shop::app.track_order.email')
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                        value="{{ old('email', $order->customer_email ?? '') }}"
                        class="block w-full rounded-lg border border-zinc-200 px-4 py-3 text-sm focus:border-navyBlue focus:outline-none focus:ring-1 focus:ring-navyBlue"
                    >

                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="primary-button col-span-full py-3 text-base"
                >
                    @lang('shop::app.track_order.submit')
                </button>
            </form>
        </div>

        @if (! empty($searched))
            @if ($order)
                <div class="mt-8 rounded-xl border border-zinc-200 bg-white p-8 max-sm:p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                        v-pre
                    >
                        <h2 class="text-xl font-medium">
                            @lang('shop::app.customers.account.orders.view.order-id') #{{ $order->increment_id }}
                        </h2>

                        @switch ($order->status)
                            @case ('completed')
                                <p class="label-completed">{{ ucfirst($order->status) }}</p>
                                @break

                            @case ('pending')
                                <p class="label-pending">{{ ucfirst($order->status) }}</p>
                                @break

                            @case ('closed')
                                <p class="label-closed">{{ ucfirst($order->status) }}</p>
                                @break

                            @case ('processing')
                                <p class="label-processing">{{ ucfirst($order->status) }}</p>
                                @break

                            @case ('canceled')
                                <p class="label-canceled">{{ ucfirst($order->status) }}</p>
                                @break

                            @default
                                <p class="label-info">{{ ucfirst($order->status) }}</p>
                        @endswitch
                    </div>

                    <p
                        class="mt-1 text-sm text-zinc-600"
                        v-pre
                    >
                        @lang('shop::app.customers.account.orders.view.information.placed-on')

                        {{ core()->formatDate($order->created_at, 'd M Y') }}
                    </p>

                    <!-- Items -->
                    <div
                        class="relative mt-6 overflow-x-auto rounded-xl border"
                        v-pre
                    >
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-100 text-black">
                                <tr class="[&>*]:px-6 [&>*]:py-4 [&>*]:font-medium">
                                    <th scope="col">
                                        @lang('shop::app.customers.account.orders.view.information.sku')
                                    </th>

                                    <th scope="col">
                                        @lang('shop::app.customers.account.orders.view.information.product-name')
                                    </th>

                                    <th scope="col">
                                        @lang('shop::app.customers.account.orders.view.information.item-status')
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr class="border-b bg-white align-top [&>*]:px-6 [&>*]:py-4">
                                        <td>{{ $item->sku }}</td>

                                        <td>{{ $item->name }}</td>

                                        <td>
                                            @if ($item->qty_ordered)
                                                @lang('shop::app.customers.account.orders.view.information.ordered-item', ['qty_ordered' => $item->qty_ordered])
                                            @endif

                                            @if ($item->qty_shipped)
                                                <br>
                                                @lang('shop::app.customers.account.orders.view.information.item-shipped', ['qty_shipped' => $item->qty_shipped])
                                            @endif

                                            @if ($item->qty_refunded)
                                                <br>
                                                @lang('shop::app.customers.account.orders.view.information.item-refunded', ['qty_refunded' => $item->qty_refunded])
                                            @endif

                                            @if ($item->qty_canceled)
                                                <br>
                                                @lang('shop::app.customers.account.orders.view.information.item-canceled', ['qty_canceled' => $item->qty_canceled])
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Shipments -->
                    @if ($order->shipments->count())
                        <div
                            class="mt-6"
                            v-pre
                        >
                            <h3 class="text-base font-medium">
                                @lang('shop::app.customers.account.orders.view.shipments.shipments')
                            </h3>

                            <div class="mt-3 grid gap-3">
                                @foreach ($order->shipments as $shipment)
                                    <div class="rounded-lg border border-zinc-200 p-4 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-zinc-500">
                                                @lang('shop::app.customers.account.orders.view.shipments.tracking-number')
                                            </span>

                                            <span class="font-medium">
                                                {{ $shipment->track_number ?: '-' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Grand Total -->
                    <div
                        class="mt-6 flex justify-end"
                        v-pre
                    >
                        <div class="flex w-full max-w-max justify-between gap-x-10 text-sm font-semibold">
                            @lang('shop::app.customers.account.orders.view.information.grand-total')

                            <p>{{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-8 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <span class="icon-cancel mt-0.5 text-xl leading-none"></span>

                    <p>@lang('shop::app.track_order.not_found')</p>
                </div>
            @endif
        @endif
    </div>
</x-shop::layouts>

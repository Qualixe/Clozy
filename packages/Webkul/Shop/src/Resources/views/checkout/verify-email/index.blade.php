<x-shop::layouts>
    <x-slot:title>
        @lang('shop::app.guest_email_verification.page_title')
    </x-slot>

    <div class="container mx-auto mt-10 max-w-xl px-5 max-md:mt-6 max-md:px-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-8 max-sm:p-5">
            <h1 class="text-2xl font-medium max-md:text-xl">
                @lang('shop::app.guest_email_verification.heading')
            </h1>

            <p class="mt-2 text-sm text-zinc-600">
                @lang('shop::app.guest_email_verification.intro')
            </p>

            @if (! $pendingEmail)
                <form
                    method="POST"
                    action="{{ route('shop.checkout.verify_email.send') }}"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-sm font-medium text-zinc-900"
                        >
                            @lang('shop::app.guest_email_verification.email')
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            value="{{ old('email') }}"
                            class="block w-full rounded-lg border border-zinc-200 px-4 py-3 text-sm focus:border-navyBlue focus:outline-none focus:ring-1 focus:ring-navyBlue"
                        >

                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="primary-button w-full py-3 text-base"
                    >
                        @lang('shop::app.guest_email_verification.send_code')
                    </button>
                </form>
            @else
                @if (session('otp_sent'))
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        <span class="icon-check-box mt-0.5 text-xl leading-none"></span>

                        <p>@lang('shop::app.guest_email_verification.code_sent', ['email' => $pendingEmail])</p>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('shop.checkout.verify_email.verify') }}"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div>
                        <label
                            for="code"
                            class="mb-1.5 block text-sm font-medium text-zinc-900"
                        >
                            @lang('shop::app.guest_email_verification.code')
                        </label>

                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            required
                            autocomplete="one-time-code"
                            class="block w-full rounded-lg border border-zinc-200 px-4 py-3 text-center text-lg tracking-[0.5em] focus:border-navyBlue focus:outline-none focus:ring-1 focus:ring-navyBlue"
                        >

                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="primary-button w-full py-3 text-base"
                    >
                        @lang('shop::app.guest_email_verification.verify')
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('shop.checkout.verify_email.send') }}"
                    class="mt-3"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="email"
                        value="{{ $pendingEmail }}"
                    >

                    <button
                        type="submit"
                        class="w-full text-center text-xs font-medium text-navyBlue hover:underline"
                    >
                        @lang('shop::app.guest_email_verification.resend')
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-shop::layouts>

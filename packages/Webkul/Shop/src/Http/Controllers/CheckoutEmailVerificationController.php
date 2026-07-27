<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Shop\Services\CheckoutEmailOtpService;

class CheckoutEmailVerificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected CheckoutEmailOtpService $otp) {}

    /**
     * Render the email-entry form, or the code-entry form when a code is
     * already pending for this session.
     */
    public function index(): View|RedirectResponse
    {
        if (auth()->guard('customer')->check()) {
            return redirect()->route('shop.checkout.onepage.index');
        }

        return view('shop::checkout.verify-email.index', [
            'pendingEmail' => $this->otp->pendingEmail(session()->getId()),
        ]);
    }

    /**
     * Generate and email a fresh code for the submitted address.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        if (! $this->otp->send(session()->getId(), $request->input('email'))) {
            return redirect()
                ->route('shop.checkout.verify_email.index')
                ->withErrors(['email' => trans('shop::app.guest_email_verification.send_failed')]);
        }

        return redirect()
            ->route('shop.checkout.verify_email.index')
            ->with('otp_sent', true);
    }

    /**
     * Verify the submitted code. On success, mark this session as verified
     * for the pending email and continue on to checkout.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $email = $this->otp->pendingEmail(session()->getId());

        if (
            ! $email
            || ! $this->otp->verify(session()->getId(), $email, $request->input('code'))
        ) {
            return redirect()
                ->route('shop.checkout.verify_email.index')
                ->withErrors(['code' => trans('shop::app.guest_email_verification.invalid_code')]);
        }

        session(['checkout_verified_email' => $email]);

        return redirect()->route('shop.checkout.onepage.index');
    }
}

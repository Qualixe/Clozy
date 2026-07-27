<?php

namespace Webkul\Shop\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webkul\Shop\Mail\Checkout\GuestEmailOtp;

class CheckoutEmailOtpService
{
    /**
     * How long a generated code stays valid.
     */
    const CODE_TTL_MINUTES = 10;

    /**
     * Failed verify attempts allowed before a code is invalidated outright.
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Generate a fresh 6-digit code for the given email, scoped to the
     * current browser session, and mail it out. Returns false (without
     * caching a code) if the mail could not be dispatched, so a mail
     * outage doesn't leave guests stuck on a "code sent" screen for a
     * code that never arrived.
     */
    public function send(string $sessionId, string $email): bool
    {
        $code = (string) random_int(100000, 999999);

        try {
            Mail::queue(new GuestEmailOtp($email, $code, self::CODE_TTL_MINUTES));
        } catch (\Throwable $e) {
            Log::error('Checkout guest OTP email failed', ['error' => $e->getMessage()]);

            return false;
        }

        Cache::put(
            $this->cacheKey($sessionId),
            [
                'email' => mb_strtolower($email),
                'code' => $code,
                'attempts' => 0,
            ],
            now()->addMinutes(self::CODE_TTL_MINUTES)
        );

        return true;
    }

    /**
     * Verify a submitted code against the cached OTP for this session.
     * Clears the cache entry on success so a code cannot be reused. Failed
     * attempts count against a cap, after which the code is invalidated and
     * a fresh one must be requested.
     */
    public function verify(string $sessionId, string $email, string $code): bool
    {
        $key = $this->cacheKey($sessionId);

        $payload = Cache::get($key);

        if (
            ! $payload
            || mb_strtolower($email) !== $payload['email']
            || $payload['attempts'] >= self::MAX_ATTEMPTS
        ) {
            Cache::forget($key);

            return false;
        }

        if (! hash_equals($payload['code'], $code)) {
            $payload['attempts']++;

            Cache::put($key, $payload, now()->addMinutes(self::CODE_TTL_MINUTES));

            return false;
        }

        Cache::forget($key);

        return true;
    }

    /**
     * The email a code is currently pending for in this session, if any.
     */
    public function pendingEmail(string $sessionId): ?string
    {
        return Cache::get($this->cacheKey($sessionId))['email'] ?? null;
    }

    protected function cacheKey(string $sessionId): string
    {
        return "checkout_email_otp:{$sessionId}";
    }
}

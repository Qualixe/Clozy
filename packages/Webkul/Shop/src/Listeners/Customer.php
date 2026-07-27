<?php

namespace Webkul\Shop\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\NoteNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;
use Webkul\Shop\Mail\Customer\SubscriptionNotification;
use Webkul\Shop\Mail\Customer\UpdatePasswordNotification;

class Customer extends Base
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected OrderRepository $orderRepository) {}

    /**
     * Link any guest orders placed under this email to the account that
     * just logged in, so they show up in "My Orders" going forward. Mirrors
     * Cart::mergeCart(), which does the equivalent for an in-progress cart.
     */
    public function afterLogin($customer)
    {
        $this->orderRepository->getModel()
            ->newQuery()
            ->where('is_guest', 1)
            ->whereNull('customer_id')
            ->where('customer_email', $customer->email)
            ->update([
                'customer_id' => $customer->id,
                'customer_type' => get_class($customer),
                'customer_first_name' => $customer->first_name,
                'customer_last_name' => $customer->last_name,
                'is_guest' => 0,
            ]);
    }

    /**
     * After customer is created
     *
     * @param  \Webkul\Customer\Contracts\Customer  $customer
     * @return void
     */
    public function afterCreated($customer)
    {
        if (core()->getConfigData('customer.settings.email.verification')) {
            try {
                if (! core()->getConfigData('customer.settings.email.verification')) {
                    return;
                }

                Mail::queue(new EmailVerificationNotification($customer));
            } catch (\Exception $e) {
                \Log::info('EmailVerificationNotification Error');

                report($e);
            }

            return;
        }

        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                return;
            }

            Mail::queue(new RegistrationNotification($customer));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send mail on updating password.
     *
     * @param  \Webkul\Customer\Models\Customer  $customer
     * @return void
     */
    public function afterPasswordUpdated($customer)
    {
        try {
            Mail::queue(new UpdatePasswordNotification($customer));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send mail on subscribe
     *
     * @param  \Webkul\Customer\Models\Customer  $customer
     * @return void
     */
    public function afterSubscribed($customer)
    {
        try {
            Mail::queue(new SubscriptionNotification($customer));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send mail on creating Note
     *
     * @param  \Webkul\Customer\Models\Customer  $customer
     * @return void
     */
    public function afterNoteCreated($note)
    {
        if (! $note->customer_notified) {
            return;
        }

        try {
            Mail::queue(new NoteNotification($note));
        } catch (\Exception $e) {
            session()->flash('warning', $e->getMessage());
        }
    }
}

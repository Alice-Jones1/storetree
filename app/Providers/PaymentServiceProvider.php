<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(['frontend.story.step6', 'frontend.payment','frontend.payment-addon'], function ($view){
            $cart  = Session::get('cart');
            $plans = config('plans');

            return $view->with('packagePlan', $plans[$cart['plan']]);
        });
    }
}

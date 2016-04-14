<?php namespace Sanatorium\Orderspaymentcsob\Providers;

use Cartalyst\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class PaymentCsobServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function boot()
	{
		// Register the default payment service
		$this->app['sanatorium.orders.payment.services']->registerService(
			'\Sanatorium\Orderspaymentcsob\Controllers\Services\CsobPaymentService'
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function register()
	{
		// Prepare resources
        $this->prepareResources();
	}

	/**
     * Prepare the package resources.
     *
     * @return void
     */
    protected function prepareResources()
    {
        $config = realpath(__DIR__.'/../../config/config.php');

        $this->mergeConfigFrom($config, 'sanatorium-orderspaymentcsob');

        $this->publishes([
            $config => config_path('sanatorium-orderspaymentcsob.php'),
        ], 'config');
    }

}

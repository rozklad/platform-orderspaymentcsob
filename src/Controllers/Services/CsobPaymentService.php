<?php namespace Sanatorium\Orderspaymentcsob\Controllers\Services;

use Log;
use OndraKoupil\Csob\Client as CsobClient;
use OndraKoupil\Csob\Config as CsobConfig;
use OndraKoupil\Csob\Payment as CsobPayment;
use OndraKoupil\Tools\Strings;
use Product;
use Cart;
use Sanatorium\Localization\Models\Language;

class CsobPaymentService {

	public $name;
	public $description;

	protected $client;

	public function __construct()
	{
		$this->name = trans('sanatorium/orderspaymentcsob::payment_services.csob.name');
		$this->description = trans('sanatorium/orderspaymentcsob::payment_services.csob.description');
	
		$merchant_id = config('sanatorium-orderspaymentcsob.merchant_id');
		$environment = config('sanatorium-orderspaymentcsob.environment');

		$log_file_path = __DIR__ . sprintf("/../../../storage/csob.log");

		if ( $environment == 'test' ) {
			$private_key_path = __DIR__ . sprintf("/../../../storage/keys/rsa_%s_test.key", $merchant_id);
		} else {
			$private_key_path = __DIR__ . sprintf("/../../../storage/keys/rsa_%s_prod.key", $merchant_id);
		}

		if ( $environment == 'test' ) {
			$public_key_path = __DIR__ . sprintf("/../../../storage/keys/mips_iplatebnibrana.csob.cz.pub", $merchant_id);
		} else {
			$public_key_path = __DIR__ . sprintf("/../../../storage/keys/mips_platebnibrana.csob.cz.pub", $merchant_id);
		}

		$config = new CsobConfig(
			$merchant_id,
			$private_key_path,
			$public_key_path,
			config('platform.app.title'),

    		// Return URL
			route('sanatorium.orders.cart.placed'),

		    // API address URL
			config('sanatorium-orderspaymentcsob.api_url')
			);

		$this->client = new CsobClient($config);

		// Set logging
		$this->client->setLog($log_file_path);

		$this->client->setTraceLog(function($message) {
		    //Log::info($message);
		});

		try {
		    $this->client->testGetConnection();
		    $this->client->testPostConnection();

		} catch (\Exception $e) {
		    Log::critical('orderspaymentcsob: ' . $e->getMessage());
		}
	}

	public function process($order)
	{	
		$payment = new CsobPayment($order->id);

		/*
	    |--------------------------------------------------------------------------
	    | Currency
	    |--------------------------------------------------------------------------
	    |
	    | Allowed currencies: CZK, EUR, USD, GBP
	    |
	    */
	   
	   	$allowed_currencies = config('sanatorium-orderspaymentcsob.allowed_currencies');

		$active_currency = \Sanatorium\Pricing\Models\Currency::find( Product::getActiveCurrencyId() );
		
		if ( !isset( $allowed_currencies[$active_currency->code] ) )
			return redirect()->back()->withErrors(['Currency is not allowed with selected payment provider']);

		$payment->currency = $allowed_currencies[$active_currency->code];

		/*
	    |--------------------------------------------------------------------------
	    | Language
	    |--------------------------------------------------------------------------
	    |
	    | Allowed languages: CZ, EN, DE, SK
	    |
	    */
	   
	    $allowed_languages = config('sanatorium-orderspaymentcsob.allowed_languages');

	    $active_language = Language::getActiveLanguageLocale();

	    if ( isset( $allowed_languages[$active_language] ) )
	    	$language = $allowed_languages[$active_language];
	    else
	    	$language = config('sanatorium-orderspaymentcsob.fallback_language');

		$payment->language = $language;

		/*
	    |--------------------------------------------------------------------------
	    | Cart
	    |--------------------------------------------------------------------------
	    |
	    | Fill cart with valid items
	    |
	    */

		// Get cart
		Cart::unserialize($order->cart);
		
		$items_in_cart = 0;	# how many items is in cart at the moment
		$items_should_be_in_cart = 0; # how many items should be in cart at the moment

		// Add items to payment
		foreach( Cart::items() as $item ) {
			// Prevent "This version of banks's API supports only up to 2 cart items in single payment, 
			// you can't add any more items." in test environment
			if ( config('sanatorium-orderspaymentcsob.version') > 1.5 || $items_in_cart < 2 ) {
				$payment->addCartItem(
					trim(Strings::shorten($item->get('name'), 20, "", true, true)), 
					$item->get('quantity'), 
					($item->get('price') * $item->get('quantity')) * 100
				);

				$items_in_cart++;
			}

			$items_should_be_in_cart++;
		}

		// Add discounts to cart
		$conditions = Cart::conditionsTotal('discount');

		foreach( $conditions as $name => $condition ) {
			// Prevent "This version of banks's API supports only up to 2 cart items in single payment, 
			// you can't add any more items." in test environment
			if ( config('sanatorium-orderspaymentcsob.version') > 1.5 || $items_in_cart < 2 ) {
				$payment->addCartItem(
					trim(Strings::shorten($name, 20, "", true, true)), 
					1, 
					$condition * 100
				);

				$items_in_cart++;
			}

			$items_should_be_in_cart++;
		}

		// Add delivery to payment
		if ( is_object( $order->deliverytype ) ) {

			if ( $order->deliverytype->price_vat ) {

				// Prevent "This version of banks's API supports only up to 2 cart items in single payment, 
				// you can't add any more items." in test environment
				if ( config('sanatorium-orderspaymentcsob.version') > 1.5 || $items_in_cart < 2 ) {

					$shipping_multiplier = 1;

					if ( Cart::getMetaData('shipping_multiplier') )
						$shipping_multiplier = Cart::getMetaData('shipping_multiplier');													 
					$payment->addCartItem(
						trim(Strings::shorten($order->deliverytype->delivery_title, 20, "", true, true)), 
						1, 
						ceil($order->deliverytype->price_vat * $shipping_multiplier) * 100
					);

					$items_in_cart++;
				}

			}

			$items_should_be_in_cart++;
		}

		/**
		 * CAUTION: Due to CSOB limitation to 2 items in API version 1 and 1.5
		 * if the $item_should_be_in_cart > $items_in_cart, then we gracefully
		 * turn all the cart to one item + delivery.
		 */

		if ( $items_should_be_in_cart > $items_in_cart ) {
			
			// Remove the old payment
			unset($payment);

			$payment = new CsobPayment($order->id);
			$summary_total_price = 0;

			$payment->currency = $allowed_currencies[$active_currency->code];
			$payment->language = $language;

			$summary_total_price = 0;

			foreach( Cart::items() as $item ) {

				$summary_total_price = $summary_total_price + ( ( round($item->get('price')) * $item->get('quantity') ) * 100 );

			}

			foreach( $conditions as $name => $condition ) {

				$summary_total_price = $summary_total_price + round($condition) * 100;

			}

			// Add total price to cart
			$payment->addCartItem(
				trim(Strings::shorten(trans('sanatorium/orderspaymentcsob::common.order_title', ['app' => config('platform.app.title')]), 20, "", true, true)), 
				1,
				$summary_total_price 
			);

			if ( is_object( $order->deliverytype ) ) {

				if ( $order->deliverytype->price_vat ) {

					$shipping_multiplier = 1;

					if ( Cart::getMetaData('shipping_multiplier') )
						$shipping_multiplier = Cart::getMetaData('shipping_multiplier');													 
					$payment->addCartItem(
						trim(Strings::shorten($order->deliverytype->delivery_title, 20, "", true, true)), 
						1, 
						ceil($order->deliverytype->price_vat * $shipping_multiplier) * 100
					);

				}

			}

		}

		// payment/init call
		try {
			$response = $this->client->paymentInit($payment);
		} catch (\RuntimeException $e) {
			Log::info('orderspaymentcsob: ' . $e->getMessage());
			return redirect()->back()->withErrors(['Payment gate does not support your browser.']);
		}

		// CSOB payment id
		$provider_id = $payment->getPayId();

		// Check for payment status
		$provider_status = $this->client->paymentStatus($payment);

		// Update payment with retrieved data
		if ( $order->payment ) {
			$payment_object = $order->payment;
			$payment_object->provider_id = $provider_id;
			$payment_object->provider_status = $provider_status;
			$payment_object->save();
		}

		// Get URL to send the customer to
		$url = $this->client->getPaymentProcessUrl($payment);

		return redirect()->to($url);
	}

	public function reverse($order, $args = [])
	{
		extract($args);
		
		try {
			$result = $this->client->paymentReverse($order->payment->provider_id);
		} catch(\RuntimeException $e) {
			Log::info('orderspaymentcsob: ' . $e->getMessage());
			$result = false;
		}

		if ( $result ) {
			return [
				'success' => true
			];
		} else {
			return [
				'success' => false,
				'msg' => $msg,
			];
		}
	}

	public function refund($order, $args = [])
	{
		extract($args);

		if ( !isset($ignoreWrongPaymentStatusError) )
			$ignoreWrongPaymentStatusError = config('sanatorium-orderspaymentcsob.ignoreWrongPaymentStatusError');

		if ( !isset($amount) )
			$amount = 0;

		try {
			$result = $this->client->paymentRefund($order->payment->provider_id, $ignoreWrongPaymentStatusError, $amount * 100);
		} catch(\RuntimeException $e) {
			Log::info('orderspaymentcsob: ' . $e->getMessage());
			$result = false;
		}

		if ( $result ) {
			return [
				'success' => true
			];
		} else {
			return [
				'success' => false,
				'msg' => $msg,
			];
		}
	}

	public function close($order, $args = [])
	{
		extract($args);

		if ( !isset($ignoreWrongPaymentStatusError) )
			$ignoreWrongPaymentStatusError = config('sanatorium-orderspaymentcsob.ignoreWrongPaymentStatusError');

		if ( !isset($amount) )
			$amount = 0;
		
		try {
			$result = $this->client->paymentClose($order->payment->provider_id, $ignoreWrongPaymentStatusError, $amount * 100);
		} catch(\RuntimeException $e) {
			Log::info('orderspaymentcsob: ' . $e->getMessage());
			$result = false;
		}

		if ( $result ) {
			return [
				'success' => true
			];
		} else {
			return [
				'success' => false,
				'msg' => $msg,
			];
		}
	}

	public function status($order, $args = [])
	{
		extract($args);

		try {
			return $this->client->paymentStatus($order->payment->provider_id);
		} catch(\RuntimeException $e) {
			return 0;
		}
	}

	public function isPaymentOpened($order)
	{
		$status = $this->status($order);

		switch ( $status ) {

			case 4:
			case 7:
				return true;
			break;

			default:
				return false;
			break;

		}
	}

	public function status_human_readable($order, $args = [])
	{
		$status = $this->status($order);

		return trans('sanatorium/orderspaymentcsob::statuses.'.$status);
	}

	public function isSuccess($order)
	{
		$status = $this->status($order);

		switch ( $status ) {

			case 4:
			case 7:
				return true;
			break;

			default:
				return false;
			break;

		}
	}

}
<?php

return [

	'title' => 'Payment CSOB',

	'merchant_id' => [
		'label' => 'Merchant ID',
		'info' => 'Information you will get from payment gate provider',
	],

	'api_url' => [
		'label' => 'API URL',
		'info' => 'Payment gate API URL'
	],

	'log' => [
		'label' => 'Enable log',
		'info' => 'Enable debug log',

		'values' => [
			'true' => 'Enabled',
			'false' => 'Disabled',
		],
	],

	'environment' => [
		'label' => 'Environment',
		'info' => 'Payment environment',

		'values' => [
			'test' => 'Testing',
			'production' => 'Production',
		],
	],

];
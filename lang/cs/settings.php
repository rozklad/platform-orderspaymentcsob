<?php

return [

	'title' => 'Platba ČSOB',

	'merchant_id' => [
		'label' => 'Merchant ID',
		'info' => 'Informaci vám poskytne poskytovatel platební brány, jde o Vaše ID obchodníka',
	],

	'api_url' => [
		'label' => 'API URL',
		'info' => 'API URL platební brány'
	],

	'log' => [
		'label' => 'Zapnout log',
		'info' => 'Zapnout debug log',

		'values' => [
			'true' => 'Povolený',
			'false' => 'Zakázaný',
		],
	],

	'environment' => [
		'label' => 'Prostředí',
		'info' => 'Platební prostředí může být testovací nebo produkční, podle toho, zda chcete platby zkoušet nebo skutečně provádět',

		'values' => [
			'test' => 'Testovací',
			'production' => 'Produkční',
		],
	],

];
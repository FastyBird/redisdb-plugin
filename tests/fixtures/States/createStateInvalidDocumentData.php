<?php declare(strict_types = 1);

use FastyBird\RedisDbExchangePlugin\States;

return [
	'one' => [
		States\State::class,
		[],
	],
	'two' => [
		States\State::class,
		[
			'id' => 'invalid-string',
		],
	],
];

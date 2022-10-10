<?php declare(strict_types = 1);

use FastyBird\RedisDbExchangePlugin\States;
use Ramsey\Uuid\Uuid;

return [
	'one' => [
		States\State::class,
		[
			'id' => Uuid::uuid4()->toString(),
		],
	],
	'two' => [
		States\State::class,
		[
			'id' => Uuid::uuid4()->toString(),
		],
	],
];

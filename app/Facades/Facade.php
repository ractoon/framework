<?php

namespace App\Facades;

use App\Container\Container;
use Mockery;
use Mockery\MockInterface;

abstract class Facade
{
	protected static $resolvedInstance;

	abstract protected static function getFacadeAccessor();

	public static function __callStatic($method, $args)
	{
		$name = Facade::getFacadeAccessor();

		if (!isset(self::$resolvedInstance[$name])) {
			$container = Container::getInstance();

			self::$resolvedInstance[$name] = $container->make(Facade::getFacadeAccessor());
		}

		return self::$resolvedInstance[$name]->{$method}(...$args);
	}

	public static function shouldReceive()
	{
		$name = Facade::getFacadeAccessor();

		if (!isset(self::$resolvedInstance[$name]) || self::$resolvedInstance[$name] instanceof MockInterface) {
			$object = Container::getInstance()->make(Facade::getFacadeAccessor());
			self::$resolvedInstance[$name] = Mockery::mock($object);
		}

		return Facade::$resolvedInstance[$name]->shouldReceive(...func_get_args());
	}
}
<?php

namespace App\Container;

use Closure;
use Exceptions\BindingResolutionException;

class Container
{
	protected static $instance;

	protected array $bindings = [];

	protected array $instances = [];

	// singleton
	private function __construct()
	{
	}

	public static function getInstance()
	{
		if (is_null(Container::$instance)) {
			Container::$instance = new static();
		}

		return Container::$instance;
	}

	public function bind($abstract, $concrete = null, $shared = false)
	{
		$this->bindings[$abstract] = [
			'concrete' => $concrete,
			'shared' => $shared,
		];
	}

	public function singleton($abstract, $concrete = null)
	{
		$this->bind($abstract, $concrete, true);
	}

	public function instance($abstract, $instance)
	{
		$this->instances[$abstract] = $instance;
	}

	public function make($abstract)
	{
		// if this has already been resolved as a singleton
		if (isset($this->instances[$abstract])) {
			return $this->instances[$abstract];
		}

		// get registered concrete resolver for this type
		$concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

		// if the concrete is a closure, or did not get a resolver, attempt to instantiate
		if ($concrete instanceof Closure || $concrete == $abstract) {
			$object = $this->build($concrete);
		}
		// otherwise the concrete is referencing something else so recursively resolve until receiving a singleton instance, a closure, or run out of references
		else {
			$object = $this->make($concrete);
		}

		// if registered as a singleton store the instance so it can be returned
		if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
			$this->instances[$abstract] = $object;
		}

		return $object;
	}

	public function build($concrete)
	{
		if ($concrete instanceof Closure) {
			return $concrete($this);
		}

		try {
			$reflector = new \ReflectionClass($concrete);
		} catch (\ReflectionException $e) {
			throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
		}

		if (!$reflector->isInstantiable()) {
			throw new BindingResolutionException("Target [$concrete] is not instantiable.");
		}

		$constructor = $reflector->getConstructor();

		if (is_null($constructor)) {
			return new $concrete;
		}

		$dependencies = $constructor->getParameters();

		$instances = $this->resolveDependencies($dependencies);

		return $reflector->newInstanceArgs($instances);
	}

	protected function resolveDependencies($dependencies)
	{
		$results = [];

		foreach ($dependencies as $dependency) {
			$type = $dependency->getType();

			if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
				throw new BindingResolutionException("Unresolvable dependency resolving [$dependency] in class {$dependency->getDeclaringClass()->getName()}");
			}

			$results[] = $this->make($type->getName());
		}

		return $results;
	}
}
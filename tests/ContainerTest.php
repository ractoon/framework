<?php

namespace Tests;

use App\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
	/** @test */
	function container_is_a_singleton()
	{
		$instance1 = Container::getInstance();
		$instance2 = Container::getInstance();

		$this->assertSame($instance1, $instance2);

		$this->expectErrorMessage('Call to private');
		new Container();
	}

	/** @test */
	function class_resolutions_can_be_customized()
	{
		$container = Container::getInstance();

		$container->bind(CustomExample::class, function () {
			return new CustomExample('test');
		});

		$customExample = $container->make(CustomExample::class);
		$this->assertInstanceOf(CustomExample::class, $customExample);

		$anotherCustomExample = $container->make(CustomExample::class);
		$this->assertNotSame($customExample, $anotherCustomExample);
	}

	/** @test */
	function container_bindings_can_use_strings()
	{
		$container = Container::getInstance();

		$container->bind('example', fn () => new CustomExample('test'));

		$customExample = $container->make('example');

		$this->assertInstanceOf(CustomExample::class, $customExample);
	}

	/** @test */
	function interfaces_can_be_bound_to_an_instance()
	{
		$container = Container::getInstance();

		$container->bind(ExampleInterface::class, fn () => new CustomExample('test'));

		$customExample = $container->make(ExampleInterface::class);

		$this->assertInstanceOf(CustomExample::class, $customExample);
	}

	/** @test */
	function a_concrete_class_can_be_bound_to_an_interface()
	{
		$container = Container::getInstance();

		$container->bind(ExampleInterface::class, BasicExample::class);

		$example = $container->make(ExampleInterface::class);

		$this->assertInstanceOf(BasicExample::class, $example);
	}

	/** @test */
	function any_class_can_resolve()
	{
		$container = Container::getInstance();

		$example = $container->make(BasicExample::class);

		$this->assertInstanceOf(BasicExample::class, $example);
	}

	/** @test */
	function classes_can_resolve_recursively()
	{
		$container = Container::getInstance();

		$container->bind(ExampleInterface::class, CustomExample::class);
		$container->bind(CustomExample::class, fn () => new CustomExample('test'));

		$example = $container->make(ExampleInterface::class);

		$this->assertInstanceOf(CustomExample::class, $example);
	}

	/** @test */
	function can_bind_a_singleton()
	{
		$container = Container::getInstance();

		$container->singleton(CustomExample::class, fn () => new CustomExample('test'));

		$customExample1 = $container->make(CustomExample::class);
		$customExample2 = $container->make(CustomExample::class);

		$this->assertSame($customExample1, $customExample2);
		$this->assertInstanceOf(CustomExample::class, $customExample1);
	}

	/** @test */
	function a_singleton_can_be_bound_by_passing_the_instance()
	{
		$container = Container::getInstance();

		$instance = new BasicExample();
		$container->instance(BasicExample::class, $instance);
		$resolved = $container->make(BasicExample::class);

		$this->assertSame($instance, $resolved);
	}

	/** @test */
	function a_singleton_can_be_bound_by_classname()
	{
		$container = Container::getInstance();

		$container->singleton(BasicExample::class);

		$basicExample1 = $container->make(BasicExample::class);
		$basicExample2 = $container->make(BasicExample::class);

		$this->assertSame($basicExample1, $basicExample2);
		$this->assertInstanceOf(BasicExample::class, $basicExample1);
	}

	/** @test */
	function container_handles_dependency_injection()
	{
		$container = Container::getInstance();

		$example = $container->make(DependencyExample::class);

		$this->assertInstanceOf(DependencyExample::class, $example);
	}
}

interface ExampleInterface
{
	public function show($text);
}

class BasicExample implements ExampleInterface
{
	public function show($text)
	{
	}
}

class CustomExample implements ExampleInterface
{
	public function __construct($custom)
	{
	}

	public function show($text)
	{
	}
}

class DependencyExample implements ExampleInterface
{
	public function __construct(Api $api)
	{
	}

	public function show($text)
	{
	}
}

class Api
{
}
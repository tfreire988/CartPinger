<?php
/**
 * Lightweight service container.
 *
 * @package WhatsCom\Core
 */

declare(strict_types=1);

namespace WhatsCom\Core;

use InvalidArgumentException;

/**
 * Class Container
 */
final class Container {

	/** @var array<string, callable> */
	private array $bindings = array();

	/** @var array<string, object> */
	private array $instances = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $abstract Service identifier.
	 * @param callable $factory  Factory that returns the service instance.
	 */
	public function bind( string $abstract, callable $factory ): void {
		$this->bindings[ $abstract ] = $factory;
	}

	/**
	 * Register a shared (singleton) service.
	 *
	 * @param string   $abstract Service identifier.
	 * @param callable $factory  Factory that returns the service instance.
	 */
	public function singleton( string $abstract, callable $factory ): void {
		$this->bindings[ $abstract ] = function () use ( $abstract, $factory ): object {
			if ( ! isset( $this->instances[ $abstract ] ) ) {
				$instance = $factory( $this );
				if ( ! is_object( $instance ) ) {
					throw new InvalidArgumentException( "Factory for [{$abstract}] must return an object." );
				}
				$this->instances[ $abstract ] = $instance;
			}
			return $this->instances[ $abstract ];
		};
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $abstract Service identifier.
	 * @return object
	 * @throws InvalidArgumentException If the service is not registered.
	 */
	public function make( string $abstract ): object {
		if ( ! isset( $this->bindings[ $abstract ] ) ) {
			throw new InvalidArgumentException( "No binding registered for [{$abstract}]." );
		}

		$result = ( $this->bindings[ $abstract ] )( $this );

		if ( ! is_object( $result ) ) {
			throw new InvalidArgumentException( "Binding for [{$abstract}] must return an object." );
		}

		return $result;
	}

	/**
	 * Check whether a service is registered.
	 *
	 * @param string $abstract Service identifier.
	 */
	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] );
	}
}

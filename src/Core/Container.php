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

	/**
	 * Registered service factory closures.
	 *
	 * @var array<string, callable>
	 */
	private array $bindings = array();

	/**
	 * Resolved singleton instances.
	 *
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $service_id Service identifier.
	 * @param callable $factory    Factory that returns the service instance.
	 */
	public function bind( string $service_id, callable $factory ): void {
		$this->bindings[ $service_id ] = $factory;
	}

	/**
	 * Register a shared (singleton) service.
	 *
	 * @param string   $service_id Service identifier.
	 * @param callable $factory    Factory that returns the service instance.
	 */
	public function singleton( string $service_id, callable $factory ): void {
		$this->bindings[ $service_id ] = function () use ( $service_id, $factory ): object {
			if ( ! isset( $this->instances[ $service_id ] ) ) {
				$instance = $factory( $this );
				if ( ! is_object( $instance ) ) {
					throw new InvalidArgumentException( "Factory for [{$service_id}] must return an object." );
				}
				$this->instances[ $service_id ] = $instance;
			}
			return $this->instances[ $service_id ];
		};
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $service_id Service identifier.
	 * @return object
	 * @throws InvalidArgumentException If the service is not registered.
	 */
	public function make( string $service_id ): object {
		if ( ! isset( $this->bindings[ $service_id ] ) ) {
			throw new InvalidArgumentException( "No binding registered for [{$service_id}]." );
		}

		$result = ( $this->bindings[ $service_id ] )( $this );

		if ( ! is_object( $result ) ) {
			throw new InvalidArgumentException( "Binding for [{$service_id}] must return an object." );
		}

		return $result;
	}

	/**
	 * Check whether a service is registered.
	 *
	 * @param string $service_id Service identifier.
	 */
	public function has( string $service_id ): bool {
		return isset( $this->bindings[ $service_id ] );
	}
}

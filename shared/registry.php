<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

/**
 * A generic registry object that stores objects of type T.
 *
 * It contains a generation that is incremented every time the registry changes.
 *
 * Since the API of Registry is very simple,
 * it is expected that the implementation would almost never change,
 * so Registry implementations from any arbitrary copy of InfoAPI can be used.
 *
 * @template T
 */
interface Registry {
	/**
	 * Adds an object to this registry.
	 *
	 * @param T $object
	 */
	public function register($object) : void;

	/**
	 * Returns a number that increases every time the registry is mutated.
	 */
	public function getGeneration() : int;

	/**
	 * Returns all objects registered on this registry so far.
	 *
	 * @return T[] A linear list of Mapping objects.
	 */
	public function getAll() : array;
}

<?php

/*
 * This file is part of Pimple.
 *
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Pimple;

use Pimple\Exception\ExpectedInvokableException;
use Pimple\Exception\FrozenServiceException;
use Pimple\Exception\InvalidServiceIdentifierException;
use Pimple\Exception\UnknownIdentifierException;

/**
 * Container main class.
 *
 * @author Fabien Potencier
 */
class Container implements \ArrayAccess
{
    private $values = [];
    private $factories;
    private $protected;
    private $frozen = [];
    private $raw = [];
    private $keys = [];

    /**
     * Instantiates the container.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects
     */
    public function __construct(array $values = [])
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to define an object
     *
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (isset($this->frozen[$offset])) {
            throw new FrozenServiceException($offset);
        }

        $this->values[$offset] = $value;
        $this->keys[$offset] = true;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param string $offset The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!isset($this->keys[$offset])) {
            throw new UnknownIdentifierException($offset);
        }

        if (
            isset($this->raw[$offset])
            || !\is_object($this->values[$offset])
            || isset($this->protected[$this->values[$offset]])
            || !\method_exists($this->values[$offset], '__invoke')
        ) {
            return $this->values[$offset];
        }

        if (isset($this->factories[$this->values[$offset]])) {
            return $this->values[$offset]($this);
        }

        $raw = $this->values[$offset];
        $val = $this->values[$offset] = $raw($this);
        $this->raw[$offset] = $raw;

        $this->frozen[$offset] = true;

        return $val;
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $offset The unique identifier for the parameter or object
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->keys[$offset]);
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $offset The unique identifier for the parameter or object
     */
    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->keys[$offset])) {
            if (\is_object($this->values[$offset])) {
                unset($this->factories[$this->values[$offset]], $this->protected[$this->values[$offset]]);
            }

            unset($this->values[$offset], $this->frozen[$offset], $this->raw[$offset], $this->keys[$offset]);
        }
    }

    /**
     * Marks a callable as being a factory service.
     *
     * @param callable $callable A service definition to be used as a factory
     *
     * @return callable The passed callable
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     */
    public function factory($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Service definition is not a Closure or invokable object.');
        }

        $this->factories->attach($callable);

        return $callable;
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param callable $callable A callable to protect from being evaluated
     *
     * @return callable The passed callable
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     */
    public function protect($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Callable is not a Closure or invokable object.');
        }

        $this->protected->attach($callable);

        return $callable;
    }

    /**
     * Gets a parameter or the closure defining an object.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or the closure defining an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function raw($id)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return $this->values[$id];
    }

    /**
     * Extends an object definition.
     *
     * Useful when you want to extend an existing object definition,
     * without necessarily loading that object.
     *
     * @param string   $id       The unique identifier for the object
     * @param callable $callable A service definition to extend the original
     *
     * @return callable The wrapped callable
     *
     * @throws UnknownIdentifierException        If the identifier is not defined
     * @throws FrozenServiceException            If the service is frozen
     * @throws InvalidServiceIdentifierException If the identifier belongs to a parameter
     * @throws ExpectedInvokableException        If the extension callable is not a closure or an invokable object
     */
    public function extend($id, $callable)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        if (!\is_object($this->values[$id]) || !\method_exists($this->values[$id], '__invoke')) {
            throw new InvalidServiceIdentifierException($id);
        }

        if (isset($this->protected[$this->values[$id]])) {
            @\trigger_error(\sprintf('How Pimple behaves when extending protected closures will be fixed in Pimple 4. Are you sure "%s" should be protected?', $id), E_USER_DEPRECATED);
        }

        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this[$id] = $extended;
    }

    /**
     * Returns all defined value names.
     *
     * @return array An array of value names
     */
    public function keys()
    {
        return \array_keys($this->values);
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return static
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }
}

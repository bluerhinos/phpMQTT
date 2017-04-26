<?php

namespace Lightning;

use Lightning\Exception\TopicException;
use Lightning\Topic\VariableRoute;

class Topic {
	private $qos; /* The quality of service for the subscription */
	private $callable; /* Callable method when a message is recieved */
	private $route; /* Topic\Route object */

	function __construct($topic, $qos = 0, $callable) {
		if (!$topic) {
			throw new TopicException('Cannot initialize Topic without a topic to listen for.');
		}

		if (!is_callable($callable)) {
			throw new TopicException('Cannot initialize Topic without a callable.');
		}

		$this->route = new VariableRoute($topic);
		$this->qos = $qos;
		$this->callable = $callable;
	}

	/**
	 * Gets the Topic route.
	 * @return Topic\Route
	 */
	function getRoute() {
		return $this->route;
	}

	/**
	 * Returns the Topic callable.
	 * @return mixed
	 */
	function getCallable() {
		return $this->callable;
	}

	/**
	 * Returns the QOS level for the topic.
	 * @return int
	 */
	function getQOS() {
		return $this->qos;
	}
}
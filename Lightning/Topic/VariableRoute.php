<?php

namespace Lightning\Topic;

use Lightning\Exception\RouteException;
use stdClass;

class VariableRoute {
	const VARIABLE_SEPERATOR = '+';
	const VARIABLE_WILDCARD = '#';

	private $route;
	private $readable;
	private $wildcardIndex;
	private $attributes = array();

	function __construct($route) {
		$this->readable = $route;
		$this->route = $this->readableToMQTT($route);

		$routeParts = explode('/', $route);

		foreach ($routeParts as $index => $piece) {
			if (!$piece) {
				continue;
			}

			// This is an inline variable
			if (strpos($piece, self::VARIABLE_SEPERATOR) !== false) {
				$variableName = str_replace(self::VARIABLE_SEPERATOR, '', $piece);

				if (!$variableName) {
					continue;
				}

				if (isset($this->attributes[$variableName])) {
					throw new RouteException('Variable cannot be included twice within a single topic.', [
						'variable' => $variableName,
						'topic' => $this->readable,
					]);
				}

				$this->attributes[$variableName] = new stdClass;
				$this->attributes[$variableName]->index = $index;
			}

			// There is a wildcard variable
			if (strpos($piece, self::VARIABLE_WILDCARD) !== false) {
				// The wildcard is out of place and topic string is not valid per the MQTT specification
				if ($index != count($routeParts) - 1) {
					throw new RouteException('Topic wildcard can only be at the end of the topic.', [
						'topic' => $this->readable,
					]);
				}

				if (str_replace(self::VARIABLE_WILDCARD, '', $piece)) {
					throw new RouteException('Topic wildcard can only be at the end of the topic.', [
						'topic' => $this->readable,
					]);
				} 

				$this->wildcardIndex = $index;
			}
		}
	}

	/**
	 * Converts a readable route to an MQTT-formatted route.
	 * @param  string $route
	 * @return string
	 */
	private function readableToMQTT($route) {
		$routeParts = explode('/', $route);
		$finalRoute = '';

		foreach ($routeParts as $index => $piece) {
	        if (!$index && !$piece) {
	                continue;
	        }

	        if ($index) {
	        	$finalRoute .= "/";
	        }

	        if ($piece && strpos($piece, self::VARIABLE_SEPERATOR) !== false) {
	                $piece = self::VARIABLE_SEPERATOR;
	        }

	        $finalRoute .= $piece;
		}

		return $finalRoute;
	}

	/**
	 * Returns the MQTT-formatted route.
	 * @return string
	 */
	public function getMQTT() {
		return $this->route;
	}

	/**
	 * Returns the human readable route with inline variables.
	 * @return string
	 */
	public function getReadable() {
		return $this->readable;
	}

	/** 
	 * Gets all the attributes from the route.
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Returns whether the route has a wildcard.
	 * @return boolean
	 */
	public function hasWildcard() {
		return isset($this->wildcardIndex);
	}

	/**
	 * Returns the index of the wildcard when the route is exploded by '/'
	 * @return int
	 */
	public function getWildcardIndex() {
		return $this->wildcardIndex;
	}
}
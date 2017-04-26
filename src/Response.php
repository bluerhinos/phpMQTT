<?php

namespace phpMQTT;

use phpMQTT\Exception\ResponseException;

class Response {
	private $route; /* The full route with values */
	private $subscribedTopic; /* The originally subscribed topic */
	private $message; /* The message payload */
	private $received; /* The time the message was received */
	private $attributes = array();
	private $wildcard;

	/**
	 * Constructs a new subscription response.
	 * @param string         $route           the route that was called
	 * @param \phpMQTT\Topic $subscribedTopic the original topic that was subscribed to
	 * @param mixed          $message         the message that was recieved
	 */
	function __construct($route, \phpMQTT\Topic $subscribedTopic, $message = '') {
		if (!$subscribedTopic) {
			throw new ResponseException('A Topic is required to initialize a Response object.');
		}

		$this->route = $route;
		$this->subscribedTopic = $subscribedTopic;
		$this->message = $message;
		$this->received = time();
		$this->attributes = $this->mapAttributes();

		if ($this->subscribedTopic->getRoute()->hasWildcard()) {
			$index = $this->subscribedTopic->getRoute()->getWildcardIndex();
			$this->wildcard = $this->mapWildcard($index);
		}
	}

	/**
	 * Maps all the variable attributes from the subscribed topic to values in the response.
	 * @return array
	 */
	private function mapAttributes() {
		$attributes = [];
		$originalAttrs = $this->subscribedTopic->getRoute()->getAttributes();

		if (!count($originalAttrs)) {
			return $attributes;
		}

		foreach ($originalAttrs as $attr => $obj) {
			$routeParts = explode('/', $this->route);

			if (!isset($routeParts[$obj->index])) {
				continue;
			}

			$attributes[$attr] = $routeParts[$obj->index];
		}

		return $attributes;
	}

	/**
	 * Maps the wildcard path from the response route.
	 * @param  int $index the index of the wildcard from the VariableRoute when the route is exploded by '/'
	 * @return string
	 */
	private function mapWildcard($index) {
		$routeParts = explode('/', $this->route);

		if (!isset($routeParts[$index])) {
			return '';
		}

		$wildcardParts = array_slice($routeParts, $index);
		$wildcard = '';

		foreach ($wildcardParts as $idx => $piece) {
	        if (!$idx && !$piece) {
	                continue;
	        }

	        if ($idx) {
	        	$wildcard .= "/";
	        }

	        $wildcard .= $piece;
		}

		return $wildcard;
	}

	/**
	 * Gets an attribute by id.
	 * @param  string $key
	 * @return mixed
	 */
	public function attr($key) {
		return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
	}

	/**
	 * Returns whether the response has an attribute.
	 * @param  string  $key
	 * @return boolean
	 */
	public function hasAttr($key) {
		return isset($this->attributes[$key]);
	}

	/**
	 * Returns all the attributes in the response.
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Returns the wildcard path if one is available.
	 * @return string
	 */
	public function getWildcard() {
		return isset($this->wildcard) ? $this->wildcard : null;
	}

	/**
	 * Returns whether the response has a wildcard.
	 * @return boolean
	 */
	public function hasWildcard() {
		return isset($this->wildcard);
	}

	/**
	 * Returns the received time.
	 * @return int
	 */
	public function getReceived() {
		return $this->received;
	}

	/**
	 * Returns the message.
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Returns the full route.
	 * @return string
	 */
	public function getRoute() {
		return $this->route;
	}
}
<?php

namespace Lightning\Exception;

use Exception;

class TopicException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    /**
     * Custom string representation of the exception.
     * @return sting
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
<?php

namespace Lightning\Exception;

use Exception;

class RouteException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $data = '', $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        
        if ($data) {
        	if (is_array($data)) {
        		foreach ($data as $key => $value) {
        			$message .= " [$key]=$value";
        		}
        	} else {
        		$message .= ' '.$data;
        	}
        	
        }

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
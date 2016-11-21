<?php

class InvokeRequest {

    function createNode($key) {
        return sprintf('<%s>%s</%s>', $key, '%s', $key);
    }

    function createElement($key, $value) {
        return sprintf('<%s>%s</%s>', $key, $value, $key);
    }

    public function get($param) {
        $elem = [];
        foreach ($param as $key => $value) {
            if (!is_array($value)) {
                $elem[] = $this->createElement($key, $value);
            } else {
                $elem[] = sprintf($this->createNode($key), $this->get($value));
            }
        }
        return implode('', $elem);
    }

}

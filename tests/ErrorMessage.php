<?php


namespace iqb;


class ErrorMessage
{
    private $method;
    private $parameters;

    public function __construct(string $method, ...$parameters)
    {
        $this->method = $method;
        $this->parameters = $parameters;
    }


    public function __toString() : string
    {
        $return = 'Called: ' . $this->method . '(';

        foreach ($this->parameters as $i => $parameter) {
            if ($i > 0) {
                $return .= ', ';
            }

            $return .= \str_replace("\n", " ", \var_export($parameter, true));
        }

        $return .= ')';
        return $return;
    }
}
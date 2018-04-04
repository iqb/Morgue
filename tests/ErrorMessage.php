<?php


namespace iqb;

/**
 * Constructs an error message for failing asserts taking the arguments and method name of the surrounding method into consideration.
 */
class ErrorMessage
{
    private $message;
    private $parameters;
    private $showCallerArgs;
    private $caller;


    /**
     * @param string $message Primary message to show
     * @param array $parameters Parameters relevant to the message
     * @param bool $showCallerArgs Capture the method this message is constructed in and use the method name and parameters to make the error message more informative
     */
    public function __construct(string $message, array $parameters, bool $showCallerArgs = true)
    {
        $this->message = $message;
        $this->parameters = $parameters;
        $this->showCallerArgs = $showCallerArgs;
        $this->caller = \debug_backtrace(($showCallerArgs ? 0 : \DEBUG_BACKTRACE_IGNORE_ARGS), 2)[1];
    }


    public function __toString() : string
    {
        $testParams = [];
        $methodParams = [];

        if ($this->showCallerArgs) {
            $reflectionParameters = (new \ReflectionClass($this->caller['class']))
                ->getMethod($this->caller['function'])
                ->getParameters();

            foreach ($reflectionParameters as $i => $reflectionParameter) {
                $parameter = (isset($this->caller['args'][$i]) ? $this->caller['args'][$i] : $reflectionParameter->getDefaultValue());
                $methodParams[] = '$' . $reflectionParameter->getName() . ': '
                    . \str_replace("\n", " ", \var_export($parameter, true));
            }
        }

        foreach ($this->parameters as $i => $parameter) {
            $testParams[] = (\is_string($i) ? "\$$i: " : "#$i: ")
                . \str_replace("\n", " ", \var_export($parameter, true));
        }

        return $this->message . ' with (' . \implode(', ', $testParams) . ') in ' . $this->caller['class'] . '::' . $this->caller['function'] . '(' . \implode(', ', $methodParams) . ')';
    }
}

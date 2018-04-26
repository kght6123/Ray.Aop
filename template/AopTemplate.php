<?php
/**
 * CodeGenTemplate code-gen template
 *
 * Compiler takes only the statements in the method. Then create new inherit code with interceptors.
 *
 * @see http://paul-m-jones.com/archives/182
 * @see http://stackoverflow.com/questions/8343399/calling-a-function-with-explicit-parameters-vs-call-user-func-array
 * @see http://stackoverflow.com/questions/1796100/what-is-faster-many-ifs-or-else-if
 * @see http://stackoverflow.com/questions/2401478/why-is-faster-than-in-php
 */

class AopTemplate extends \Ray\Aop\FakeMock implements Ray\Aop\WeavedInterface
{
    /**
     * @var array
     *
     * [$methodName => [$interceptorA[]][]
     */
    public $bindings;

    /**
     * @var bool
     */
    private $isIntercepting = true;

    /**
     * Method Template
     *
     * @param mixed $a
     */
    public function templateMethod($a)
    {
        if ($this->isIntercepting === false) {
            $this->isIntercepting = true;

            // call original method
            return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
        }

        $this->isIntercepting = false;
        // invoke interceptor
        $result = (new \Ray\Aop\ReflectiveMethodInvocation($this, __FUNCTION__, func_get_args(), $this->bindings[__FUNCTION__]))->proceed();
        $this->isIntercepting = true;

        return $result;
    }
}

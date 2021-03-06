<?php
namespace Ray\Aop;

class FakeClass
{
    public $a = 0;

    public $msg = 'hello';

    public function __toString()
    {
        return 'toStringString';
    }

    public function add($n)
    {
        $this->a += $n;
    }

    public function getDouble($a)
    {
        return $a * 2;
    }

    public function getSub($a, $b)
    {
        return $a - $b;
    }

    /**
     * @param int $c
     *
     * @Log
     */
    public function getTriple(int $c): int
    {
        return $c * 3;
    }
}

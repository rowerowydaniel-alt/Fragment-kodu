<?php

class SampleTests {
    public function add($a, $b) {
        return $a + $b;
    }

    public function subtract($a, $b) {
        return $a - $b;
    }

    public function divideByZero($a, $b) {
        if ($b == 0) {
            throw new Exception("Division by zero");
        }
        return $a / $b;
    }

    public function checkString($str1, $str2) {
        return $str1 === $str2;
    }
}

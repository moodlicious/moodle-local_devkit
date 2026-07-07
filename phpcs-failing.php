<?php
// No copyright, no package tag, no docblock.

$GLOBALS['test'] = 'hello';  // Superglobal.

$x = array(1,2,3); // Long array, no space after comma.

if($x == $y){  // Missing space after if, before brace.
    echo 'wrong';
}

class Bad_Class {  // Missing class docblock, wrong naming.
    var $x;  // Var instead of visibility.

    function do_stuff($a,$b) {  // No docblock, missing space.
        return $a+$b;
    }
}

$x = FALSE;  // Should be lowercase.
$y = TRUE;
$z = NULL;

$long = "this is a very long line that exceeds the maximum line length limit for moodle coding standards and should trigger a warning from phpcs tooling";

if ($a == false) {  // Yoda condition expected.
    return;
}

function uses_print() {
    print "hello";  // Print discouraged, use echo.
}

$data = array("key"=>"value");  // Short array expected.

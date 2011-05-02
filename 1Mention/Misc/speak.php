#!/usr/bin/php -q
<?php
$b = $argv[1];
$a = "This is $b";
shell_exec(("say $a"));

?>
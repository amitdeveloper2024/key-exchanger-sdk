<?php

$number = rand(1, 100);
echo $number;
if ($number % 2 == 0) {
    echo ' is even';
} else {
    echo ' is odd';
}
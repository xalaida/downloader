<?php

if (! function_exists('dd')) {
    function dd(...$args)
    {
        var_dump(...$args);

        die;
    }
}

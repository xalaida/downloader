<?php

if (! function_exists('dd')) {
    function dd(...$args): void
    {
        var_dump(...$args);

        die;
    }
}

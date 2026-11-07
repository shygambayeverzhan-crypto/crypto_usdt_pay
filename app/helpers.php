<?php

if (! function_exists('make')) {
    /**
     * @template T
     * @param T $abstract
     * @return T
     */
    function make($abstract, array $parameters = [])
    {
        return app()->make($abstract, $parameters);
    }
}

if (! function_exists('is_local')) {
    function is_local(): bool
    {
        return app()->environment('local');
    }
}

if (! function_exists('is_production')) {
    function is_production(): bool
    {
        return app()->environment('production');
    }
}

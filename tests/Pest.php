<?php

use Maxiviper117\ResultFlow\Tests\Support\ConfigStub;

uses()->in(__DIR__);

if (! function_exists('config')) {
    /**
     * Minimal config helper used for tests to mimic Laravel's config() lookup.
     */
    function config($key = null, $default = null)
    {
        return ConfigStub::get((string) $key, $default);
    }
}

if (! function_exists('config_path')) {
    function config_path($path = '')
    {
        return '/tmp/' . ltrim($path, '/');
    }
}

beforeEach(function () {
    ConfigStub::reset();
});

<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

if (!defined('CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION')) {
    define('CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION', 'test');
}

$GLOBALS['ocellaris_registered_actions'] = array();
$GLOBALS['ocellaris_registered_filters'] = array();

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $GLOBALS['ocellaris_registered_actions'][] = array(
            'hook' => (string) $hook,
            'callback' => $callback,
            'priority' => (int) $priority,
            'accepted_args' => (int) $accepted_args,
        );

        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $GLOBALS['ocellaris_registered_filters'][] = array(
            'hook' => (string) $hook,
            'callback' => $callback,
            'priority' => (int) $priority,
            'accepted_args' => (int) $accepted_args,
        );

        return true;
    }
}

if (!function_exists('__return_true')) {
    function __return_true()
    {
        return true;
    }
}

if (!function_exists('__return_false')) {
    function __return_false()
    {
        return false;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint)
    {
        return abs((int) $maybeint);
    }
}

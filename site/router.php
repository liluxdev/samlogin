<?php

defined('_JEXEC') or die;

function SAMLoginBuildRoute(&$query) {
    $segments = array();
    if (isset($query['view'])) {
        $view = $query['view'];
        $segments[] = $view;
        unset($query['view']);
    }
    if (isset($query['task'])) {
        $task = $query['task'];
        $segments[] = $task;
        unset($query['task']);
    }
    if (isset($query['return'])) {
        $return = $query['return'];
        $segments[] = $return;
        unset($query['return']);
    }
    return $segments;
}

function SAMLoginParseRoute($segments) {
    $vars = array();

    if (base64_encode(base64_decode($segments[0], true)) === $segments[0]) {
        $vars['return'] = $segments[0];
        $vars['view'] = "login";
    } else {

        if ($segments[0] == 'login' || $segments[0] == 'discojuice') {
            $vars['view'] = $segments[0];
            $vars['task'] = $segments[1];
            if (isset($segments[2])) {
                $vars['return'] = $segments[2];
            } else {
                if (base64_encode(base64_decode($segments[1], true)) === $segments[1]) {
                    //it's the return URL not the task
                    $vars['task'] = "";
                    $vars['return'] = $segments[1];
                }
            }
        } else {
            $vars['task'] = $segments[0];
            if (isset($segments[1])) {

                $vars['return'] = $segments[1];
            }
        }
    }

    //print_r($vars);die();
    return $vars;
}

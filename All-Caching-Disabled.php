<?php
add_action('template_redirect', function () {
    if (is_page('tnt_reviews') || (is_page('mn_reviews'))
	   || (is_page('mom_dad_reviews')) || (is_page('sb_reviews')) ) { // page slug
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        if (!defined('DONOTCACHEDB'))   define('DONOTCACHEDB', true);
        if (!defined('DONOTMINIFY'))    define('DONOTMINIFY', true);
        if (function_exists('do_action')) {
            do_action('litespeed_control_set_nocache');
        }
    }
}, 0);

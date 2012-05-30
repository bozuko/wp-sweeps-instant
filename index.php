<?php
/*
Plugin Name: Sweeps - Instant Win
Plugin URI: http://bozuko.com
Description: This plugin extends the normal Sweeps plugin with additional bozuko instant win options and features
Version: 1.0
Author: Bozuko
Author URI: http://bozuko.com
License: GPLv2 or later
*/

#error_reporting(E_ALL);
if( !defined('SAVEQUERIES') ) define('SAVEQUERIES', true );

// add_action('plugins_loaded', 'plugin');
    
define( 'SWEEPS_IW_DIR', dirname(__FILE__) );
define( 'SWEEPS_IW_URL', plugins_url( '', __FILE__ ) );

define( 'SWEEPS_IW_TEMPLATE_DIR', SWEEPS_IW_DIR.'/template');
define( 'SWEEPS_IW_TEMPLATE_URL', SWEEPS_IW_URL.'/template');

Snap_Loader::register( 'SweepsInstant', SWEEPS_IW_DIR . '/plugin' );
Snap_Loader::register( 'Bozuko', SWEEPS_IW_DIR . '/lib/Bozuko' );
Snap::singleton('SweepsInstant');

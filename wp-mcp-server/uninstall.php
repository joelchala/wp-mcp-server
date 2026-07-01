<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('wpmcp_enabled');
delete_option('wpmcp_enable_db_tools');

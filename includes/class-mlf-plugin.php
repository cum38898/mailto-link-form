<?php

defined('ABSPATH') || exit;

final class MLF_Plugin
{
    private static ?MLF_Plugin $instance = null;

    private MLF_Admin $admin;

    private MLF_Shortcode $shortcode;

    private MLF_Submit $submit;

    public static function init(): MLF_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->admin = new MLF_Admin();
        $this->shortcode = new MLF_Shortcode();
        $this->submit = new MLF_Submit();
    }
}


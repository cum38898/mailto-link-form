<?php

defined('ABSPATH') || exit;

final class MALIFO_Plugin
{
    private static ?MALIFO_Plugin $instance = null;

    private MALIFO_Admin $admin;

    private MALIFO_Shortcode $shortcode;

    private MALIFO_Submit $submit;

    public static function init(): MALIFO_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->admin = new MALIFO_Admin();
        $this->shortcode = new MALIFO_Shortcode();
        $this->submit = new MALIFO_Submit();
    }
}

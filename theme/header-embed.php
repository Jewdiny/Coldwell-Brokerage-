<?php
/**
 * Bare header for the iframe-embeddable /events/?embed=1 view.
 *
 * No site nav or footer chrome — just the doctype, the theme's enqueued assets
 * (wp_head, so forms.js + the cbLegacy nonce still load and the form works), and
 * an open <body>. Loaded via get_header('embed'). See archive-cb_event.php.
 *
 * @package CB_Legacy_Luxury
 */
if (!defined('ABSPATH')) { exit; }
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap">
    <?php wp_head(); ?>
    <style>html,body{margin:0;padding:0;background:#f5f2e9;}</style>
</head>
<body <?php body_class('cb-embed'); ?>>
<?php wp_body_open(); ?>

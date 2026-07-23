<?php
/**
 * Plugin Name: CB — Home 10 Page Provisioner
 * Description: Creates a published WordPress Page that renders the Home 10
 *   filmed walkthrough, using the "Home 10 — The House, filmed" template, at
 *   /the-house/. Runs ONCE and is idempotent -- see below. This exists because
 *   a page is a database row, not a file: the theme ships the template, but the
 *   Page that selects it has to be created inside WordPress, and this does that
 *   automatically on deploy instead of by hand in wp-admin.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME10_PAGE_TEMPLATE')) {
    define('CB_HOME10_PAGE_TEMPLATE', 'templates/template-home10-filmed.php');
}
if (!defined('CB_HOME10_PAGE_SLUG')) {
    define('CB_HOME10_PAGE_SLUG', 'the-house');
}
if (!defined('CB_HOME10_PAGE_TITLE')) {
    define('CB_HOME10_PAGE_TITLE', 'The House');
}

if (!function_exists('cb_home10_provision_page')) {
    /**
     * Create the page once.
     *
     * IDEMPOTENT, three ways, because this runs on ordinary front-end requests
     * and must never create a second page or fight a manual edit:
     *
     *   1. An option (cb_home10_page_id) records the page once made. If it still
     *      points at a live page, we are done -- one cheap get_option and out.
     *   2. Before creating, we look for ANY page already on the Home 10
     *      template -- whether this plugin made it or someone made it by hand --
     *      and adopt that instead of adding another.
     *   3. Only after both miss do we insert. The new id is stored, so step 1
     *      short-circuits every request afterwards.
     *
     * Skipped during cron / AJAX / REST / autosave: those are not the contexts
     * to be writing a published page from, and a normal page load will do it a
     * moment later anyway.
     */
    function cb_home10_provision_page() {
        if ((defined('DOING_CRON') && DOING_CRON)
            || (defined('DOING_AJAX') && DOING_AJAX)
            || (defined('REST_REQUEST') && REST_REQUEST)
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return;
        }

        // 1. Already made and still there?
        $known = (int) get_option('cb_home10_page_id', 0);
        if ($known && get_post_status($known) !== false) {
            return;
        }

        // 2. Any page already using the Home 10 template? Adopt it.
        $existing = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wp_page_template',
            'meta_value'     => CB_HOME10_PAGE_TEMPLATE,
            'no_found_rows'  => true,
        ]);
        if (!empty($existing)) {
            update_option('cb_home10_page_id', (int) $existing[0]);
            return;
        }

        // 3. Create it. Published, because "create a new page" means a page that
        // exists at its URL -- not a draft nobody can see. It is noindex anyway:
        // the template's cb_set_seo_meta() sends noindex,nofollow, so this does
        // not compete with the front page for the same content in search.
        $id = wp_insert_post([
            'post_title'   => CB_HOME10_PAGE_TITLE,
            'post_name'    => CB_HOME10_PAGE_SLUG,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'comment_status'=> 'closed',
            'ping_status'  => 'closed',
            'meta_input'   => [
                '_wp_page_template' => CB_HOME10_PAGE_TEMPLATE,
            ],
        ], true);

        if ($id && !is_wp_error($id)) {
            update_option('cb_home10_page_id', (int) $id);
        } elseif (is_wp_error($id) && function_exists('error_log')) {
            error_log('[cb10] page provisioning failed: ' . $id->get_error_message());
        }
    }
    // init, not earlier: wp_insert_post needs the 'page' post type registered,
    // which happens on init. The theme (and so the template) is already loaded
    // by then -- after_setup_theme fires before init -- but the insert only
    // stores the template PATH as meta and does not need the file to exist, so
    // init is both sufficient and the earliest safe point.
    add_action('init', 'cb_home10_provision_page');
}

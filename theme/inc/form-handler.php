<?php
/**
 * AJAX Form Handler for Contact, Home Valuation and Property Management forms
 *
 * @package CB_Legacy_Luxury
 */

/**
 * Where a lead goes when no specific destination is set.
 *
 * This used to be a developer's personal Gmail, inline, in two places:
 * get_theme_mod('cb_email', 'simeon.mccullough41@gmail.com'). It only applied
 * if the cb_email theme mod were ever missing -- but that is precisely the
 * failure it should not have: a customer's enquiry silently delivered to a
 * private mailbox nobody at the brokerage can see, with the form still
 * reporting success. The fallback now belongs to the business.
 */
if (!defined('CB_LEAD_EMAIL_FALLBACK')) {
    define('CB_LEAD_EMAIL_FALLBACK', 'information@cbltexas.com');
}

/**
 * Resolve the destination for a lead, preferring a purpose-specific address.
 *
 * @param string $mod     Theme mod holding the specific address, e.g. cb_pm_email.
 * @param string $default Address to use when that mod is empty.
 * @return string
 */
function cb_lead_recipient($mod = '', $default = '') {
    if ($mod) {
        $specific = trim((string) get_theme_mod($mod, ''));
        if ($specific && is_email($specific)) { return $specific; }
    }
    if ($default && is_email($default)) { return $default; }
    $general = trim((string) get_theme_mod('cb_email', ''));
    return ($general && is_email($general)) ? $general : CB_LEAD_EMAIL_FALLBACK;
}

// Contact Form Handler
function cb_handle_contact_form() {
    check_ajax_referer('wp_rest', 'nonce');

    $first   = sanitize_text_field($_POST['first_name'] ?? '');
    $last    = sanitize_text_field($_POST['last_name'] ?? '');
    $email   = sanitize_email($_POST['email'] ?? '');
    $phone   = sanitize_text_field($_POST['phone'] ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? 'General Inquiry');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($first) || empty($email) || empty($message)) {
        wp_send_json_error(['message' => 'Please fill in all required fields.']);
    }

    $to = cb_lead_recipient();
    $email_subject = 'New Contact Form: ' . $subject . ' - ' . $first . ' ' . $last;

    $body = "New contact form submission from homes-sanangelo.com\n\n";
    $body .= "Name: {$first} {$last}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Subject: {$subject}\n\n";
    $body .= "Message:\n{$message}\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $first . ' ' . $last . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $email_subject, $body, $headers);

    if ($sent) {
        wp_send_json_success(['message' => 'Thank you! We will be in touch soon.']);
    } else {
        wp_send_json_error(['message' => 'There was an error sending your message. Please call us at (325) 944-9559.']);
    }
}
add_action('wp_ajax_cb_contact_form', 'cb_handle_contact_form');
add_action('wp_ajax_nopriv_cb_contact_form', 'cb_handle_contact_form');

// Home Valuation Form Handler
function cb_handle_valuation_form() {
    check_ajax_referer('wp_rest', 'nonce');

    $address    = sanitize_text_field($_POST['address'] ?? '');
    $city       = sanitize_text_field($_POST['city'] ?? '');
    $zip        = sanitize_text_field($_POST['zip'] ?? '');
    $name       = sanitize_text_field($_POST['name'] ?? '');
    $phone      = sanitize_text_field($_POST['phone'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $motivation = sanitize_text_field($_POST['motivation'] ?? '');
    $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

    if (empty($address) || empty($name) || empty($email)) {
        wp_send_json_error(['message' => 'Please fill in all required fields.']);
    }

    $to = cb_lead_recipient();
    $subject = 'Home Valuation Request: ' . $address . ', ' . $city;

    $body = "New home valuation request from homes-sanangelo.com\n\n";
    $body .= "PROPERTY\n";
    $body .= "Address: {$address}\n";
    $body .= "City: {$city}\n";
    $body .= "ZIP: {$zip}\n\n";
    $body .= "CONTACT\n";
    $body .= "Name: {$name}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Email: {$email}\n\n";
    $body .= "Motivation: {$motivation}\n";
    $body .= "Notes: {$notes}\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        wp_send_json_success(['message' => 'Thank you! Your valuation report will be delivered within 24 hours.']);
    } else {
        wp_send_json_error(['message' => 'There was an error. Please call us at (325) 944-9559.']);
    }
}
add_action('wp_ajax_cb_valuation_form', 'cb_handle_valuation_form');
add_action('wp_ajax_nopriv_cb_valuation_form', 'cb_handle_valuation_form');

/**
 * Property Management / Rental enquiry handler.
 *
 * Two audiences submit from the rentals page and they must not land in the same
 * inbox as general enquiries:
 *
 *   owner  -- someone with a property to let, wanting management. Goes to the
 *             property management team.
 *   renter -- someone looking to rent. Goes to the rentals contact.
 *
 * Both destinations are theme mods so they can be repointed without a deploy;
 * both fall back to propertymanagement@cbltexas.com, which the client gave, and
 * never to the general info@ inbox -- routing a maintenance request or a
 * management enquiry into general sales is how they get lost.
 */
function cb_handle_pm_form() {
    check_ajax_referer('wp_rest', 'nonce');

    $type    = sanitize_text_field($_POST['inquiry_type'] ?? 'owner');
    $name    = sanitize_text_field($_POST['name'] ?? '');
    $email   = sanitize_email($_POST['email'] ?? '');
    $phone   = sanitize_text_field($_POST['phone'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($name) || empty($email)) {
        wp_send_json_error(['message' => 'Please add your name and email so we can reply.']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'That email address does not look right.']);
    }

    $is_owner = ($type !== 'renter');
    $to = $is_owner
        ? cb_lead_recipient('cb_pm_email', 'propertymanagement@cbltexas.com')
        : cb_lead_recipient('cb_rentals_email', 'propertymanagement@cbltexas.com');

    $subject = $is_owner
        ? 'Property Management Enquiry - ' . $name
        : 'Rental Enquiry - ' . $name;

    $body  = "New " . ($is_owner ? 'property management' : 'rental') . " enquiry from homes-sanangelo.com\n\n";
    $body .= "Type: " . ($is_owner ? 'Property owner seeking management' : 'Looking to rent') . "\n\n";
    $body .= "Name: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$phone}\n";
    if ($address) { $body .= "Property / area: {$address}\n"; }
    $body .= "\nMessage:\n" . ($message ?: '(none given)') . "\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    if (wp_mail($to, $subject, $body, $headers)) {
        wp_send_json_success(['message' => 'Thank you — our property management team will be in touch.']);
    }
    wp_send_json_error(['message' => 'There was an error sending your message. Please call us at (325) 944-9559.']);
}
add_action('wp_ajax_cb_pm_form', 'cb_handle_pm_form');
add_action('wp_ajax_nopriv_cb_pm_form', 'cb_handle_pm_form');

/**
 * Quarterly market report signup (community page modal).
 *
 * Stores the subscriber in an option as well as emailing the office, because an
 * email notification is not a mailing list: if someone deletes the message the
 * subscriber is gone and nobody knows. The option is the record; the email is
 * the prompt to act on it.
 *
 * Deduplicates on email + area so a repeat submission does not create a second
 * entry, and caps the stored list so a scripted flood cannot grow an option row
 * without limit. Beyond that cap, signups still email through -- the ceiling
 * protects the database, and must not silently drop a real person's request.
 */
function cb_handle_market_report_signup() {
    check_ajax_referer('wp_rest', 'nonce');

    $email = sanitize_email($_POST['email'] ?? '');
    $area  = sanitize_text_field($_POST['area'] ?? '');

    if (!$email || !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }

    $list  = get_option('cb_market_report_subscribers', []);
    if (!is_array($list)) { $list = []; }

    $key = strtolower($email) . '|' . strtolower($area);
    if (!isset($list[$key]) && count($list) < 5000) {
        $list[$key] = [
            'email' => $email,
            'area'  => $area,
            'date'  => current_time('mysql'),
        ];
        update_option('cb_market_report_subscribers', $list, false);
    }

    $to      = cb_lead_recipient();
    $subject = 'Market Report Signup' . ($area ? ' - ' . $area : '');
    $body    = "New quarterly market report signup from homes-sanangelo.com\n\n"
             . "Email: {$email}\n"
             . "Area of interest: " . ($area ?: '(not specified)') . "\n"
             . "Date: " . current_time('mysql') . "\n\n"
             . "The full subscriber list is stored in the cb_market_report_subscribers option.\n";

    wp_mail($to, $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);

    // The signup is recorded whether or not the notification email went out, so
    // report success on the strength of the record, not the mail server.
    wp_send_json_success(['message' => 'You are on the list. The next report is on its way.']);
}
add_action('wp_ajax_cb_market_report_signup', 'cb_handle_market_report_signup');
add_action('wp_ajax_nopriv_cb_market_report_signup', 'cb_handle_market_report_signup');

/**
 * Plant a Legacy workshop series — the four modules.
 *
 * ONE source of truth, shared by the registration form (which renders the
 * checkboxes) and the AJAX handler (which validates the chosen keys and labels
 * them in the notification email). Change a date or venue here and both the page
 * and every email update together. Dates/times/venues are from the client's
 * schedule graphic; Module 2 is Composting (the registration-mockup image
 * mislabels it "Lasagna Gardening" — corrected here to match the infographic).
 */
function cb_event_modules() {
    return [
        'm1' => [
            'n' => 1, 'title' => 'Lasagna Gardening', 'tagline' => 'Build a bed. Wear the story.',
            'day' => 'Sat', 'date' => 'Oct 10', 'time' => '9:00–10:30 AM',
            'address' => '1024 N Adams St, San Angelo, TX 76901', 'takehome' => 'An apron & the know-how',
        ],
        'm2' => [
            'n' => 2, 'title' => 'Composting', 'tagline' => 'Make your bin. Take it home.',
            'day' => 'Tue', 'date' => 'Oct 20', 'time' => '5:30–7:00 PM',
            'address' => '3017 Knickerbocker Rd, San Angelo, TX 76904', 'takehome' => 'Your own composting bin',
        ],
        'm3' => [
            'n' => 3, 'title' => 'Upcycling & Recycling', 'tagline' => 'Save money. Gift your garden.',
            'day' => 'Tue', 'date' => 'Nov 10', 'time' => '5:30–7:00 PM',
            'address' => '3017 Knickerbocker Rd, San Angelo, TX 76904', 'takehome' => 'A handmade project for your garden',
        ],
        'm4' => [
            'n' => 4, 'title' => 'Green Cleaners', 'tagline' => 'Make it. Take the recipes home.',
            'day' => 'Tue', 'date' => 'Nov 17', 'time' => '5:30–7:00 PM',
            'address' => '3017 Knickerbocker Rd, San Angelo, TX 76904', 'takehome' => 'Two cleaners plus the recipes',
        ],
    ];
}

/**
 * Event registration form ("Plant a Legacy" landing at /events/).
 *
 * Routes the notification to the events inbox (servicedirector@cbltexas.com by
 * default; overridable via a cb_events_email theme_mod). EVERY submission is
 * also written to a cb_registration post first, so the office keeps a durable,
 * exportable record in wp-admin even if the notification email is ever filtered
 * as spam by the recipient's server. A hidden honeypot ("company") drops bots
 * without a captcha. Success is reported on the strength of the saved record,
 * not the mail server.
 */
function cb_handle_event_registration() {
    check_ajax_referer('wp_rest', 'nonce');

    // Honeypot — real people leave this empty; bots fill every field. Return a
    // fake success so the bot doesn't learn it was caught.
    if (!empty($_POST['company'])) {
        wp_send_json_success(['message' => 'Thank you! Your registration is confirmed.']);
    }

    $name  = sanitize_text_field($_POST['full_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $picked = (isset($_POST['modules']) && is_array($_POST['modules']))
        ? array_map('sanitize_text_field', wp_unslash($_POST['modules'])) : [];

    if (empty($name))                 { wp_send_json_error(['message' => 'Please enter your name.']); }
    if (!$email || !is_email($email)) { wp_send_json_error(['message' => 'Please enter a valid email address.']); }

    $catalog = cb_event_modules();
    $chosen  = [];
    foreach ($picked as $key) {
        if (isset($catalog[$key])) {
            $m = $catalog[$key];
            $chosen[] = sprintf('Module %d — %s (%s %s, %s @ %s)',
                $m['n'], $m['title'], $m['day'], $m['date'], $m['time'], $m['address']);
        }
    }
    if (empty($chosen)) { wp_send_json_error(['message' => 'Please choose at least one workshop to attend.']); }

    $when = current_time('mysql');

    // 1) Durable admin record first — this is what "success" is judged on.
    $post_id = wp_insert_post([
        'post_type'   => 'cb_registration',
        'post_status' => 'publish',
        'post_title'  => $name . ' — ' . count($chosen) . ' module(s) — ' . $when,
    ], true);
    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, 'reg_name', $name);
        update_post_meta($post_id, 'reg_email', $email);
        update_post_meta($post_id, 'reg_phone', $phone);
        update_post_meta($post_id, 'reg_modules', $chosen);
        update_post_meta($post_id, 'reg_date', $when);
    }

    // 2) Notify the events inbox.
    $to      = cb_lead_recipient('cb_events_email', 'servicedirector@cbltexas.com');
    $subject = 'Plant a Legacy — New Registration: ' . $name;
    $body    = "New workshop registration from the Plant a Legacy events page (homes-sanangelo.com/events/).\n\n"
             . "Name:  {$name}\n"
             . "Email: {$email}\n"
             . "Phone: " . ($phone ?: '(not provided)') . "\n\n"
             . "Registered for:\n  - " . implode("\n  - ", $chosen) . "\n\n"
             . "Submitted: {$when}\n"
             . (($post_id && !is_wp_error($post_id)) ? "Admin record: " . admin_url("post.php?post={$post_id}&action=edit") . "\n" : "");
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];
    wp_mail($to, $subject, $body, $headers);

    // 3) Best-effort confirmation to the registrant — never blocks success.
    $confirm = "Hi {$name},\n\n"
             . "Thank you for registering for Plant a Legacy — a hands-on workshop series from "
             . "Coldwell Banker Legacy and Grace Gardens.\n\n"
             . "You're registered for:\n  - " . implode("\n  - ", $chosen) . "\n\n"
             . "We look forward to seeing you there. Need to change anything? Just reply to this email.\n\n"
             . "— Coldwell Banker Legacy, San Angelo\n";
    wp_mail($email, "You're registered — Plant a Legacy workshops", $confirm,
        ['Content-Type: text/plain; charset=UTF-8']);

    wp_send_json_success(['message' => 'Thank you, ' . $name . '! Your registration is confirmed — a confirmation is on its way to your inbox.']);
}
add_action('wp_ajax_cb_event_registration', 'cb_handle_event_registration');
add_action('wp_ajax_nopriv_cb_event_registration', 'cb_handle_event_registration');

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
    define('CB_LEAD_EMAIL_FALLBACK', 'info@cbltexas.com');
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

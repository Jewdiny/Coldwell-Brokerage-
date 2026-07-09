<?php
/**
 * AJAX Form Handler for Contact and Home Valuation forms
 *
 * @package CB_Legacy_Luxury
 */

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

    $to = get_theme_mod('cb_email', 'simeon.mccullough41@gmail.com');
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

    $to = get_theme_mod('cb_email', 'simeon.mccullough41@gmail.com');
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

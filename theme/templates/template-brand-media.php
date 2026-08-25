<?php
/**
 * Template Name: Brand & Media
 *
 * Public brand-resource page hosting the two Coldwell Banker brand books --
 * the Brand Playbook and the Brand Identity Standards. The PDFs were imported
 * into the Media Library (attachment IDs 196 and 197) and are served from this
 * domain rather than linked to Google Drive, which showed non-signed-in
 * visitors a permission wall. If those files are ever re-uploaded, update the
 * two URLs below (or the client can swap the Media items and repoint them).
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

cb_set_seo_meta([
    'title'       => 'Brand & Media | Coldwell Banker Legacy San Angelo',
    'description' => 'Coldwell Banker brand resources — the 2025 Brand Playbook and Brand Identity Standards.',
    'canonical'   => get_permalink(),
]);

$cb_playbook_url = 'https://homes-sanangelo.com/wp-content/uploads/2026/08/cb-brand-playbook-2025.pdf';
$cb_identity_url = 'https://homes-sanangelo.com/wp-content/uploads/2026/08/cb-brand-identity-standards-2025.pdf';

$cb_doc_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3.5A1.5 1.5 0 0 1 6.5 2H14l5 5v13.5A1.5 1.5 0 0 1 17.5 22h-11A1.5 1.5 0 0 1 5 20.5z"/><path d="M14 2v5h5"/><path d="M8.5 13h7M8.5 16.5h5"/></svg>';

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero cb-page-hero--compact">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Brand &amp; Media</span>
        <h1 class="cb-reveal">Coldwell Banker Brand Resources</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:640px;margin:1.5rem auto 0;opacity:0.9;font-size:1.125rem;">
            The Coldwell Banker&reg; brand books &mdash; how the brand looks, sounds, and shows up.
        </p>
    </div>
</section>

<!-- Documents -->
<section class="cb-section">
    <div class="cb-container" style="max-width:960px;">
        <div class="cb-brand-docs">
            <article class="cb-brand-doc cb-reveal">
                <span class="cb-brand-doc__icon"><?php echo $cb_doc_icon; // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVG ?></span>
                <h2 class="cb-brand-doc__title">Brand Playbook</h2>
                <p class="cb-brand-doc__meta">PDF &middot; 26&nbsp;MB &middot; 2025</p>
                <p class="cb-brand-doc__desc">The 2025 Coldwell Banker Brand Playbook &mdash; the brand story and its refreshed look and voice, the new color palette and typography, marketing best practices, and Coldwell Banker Global Luxury&reg; guidance.</p>
                <a href="<?php echo esc_url($cb_playbook_url); ?>" target="_blank" rel="noopener" class="cb-btn cb-btn--primary">View the Playbook</a>
            </article>

            <article class="cb-brand-doc cb-reveal">
                <span class="cb-brand-doc__icon"><?php echo $cb_doc_icon; // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVG ?></span>
                <h2 class="cb-brand-doc__title">Brand Identity Standards</h2>
                <p class="cb-brand-doc__meta">PDF &middot; 48&nbsp;MB &middot; 2025</p>
                <p class="cb-brand-doc__desc">The 2025 Coldwell Banker Identity Standards &mdash; logo usage and variations, the refined color palette, typography, imagery guidelines, applications, and signage standards.</p>
                <a href="<?php echo esc_url($cb_identity_url); ?>" target="_blank" rel="noopener" class="cb-btn cb-btn--primary">View the Identity Standards</a>
            </article>
        </div>

        <p style="text-align:center;margin-top:2.5rem;color:var(--cb-text-muted);font-size:0.9375rem;">
            These are large files and open in a new tab. Right-click &rarr; &ldquo;Save link as&rdquo; to download.
        </p>
    </div>
</section>

</div>

<?php get_footer(); ?>

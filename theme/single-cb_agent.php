<?php
/**
 * Single Agent Template
 *
 * @package CB_Legacy_Luxury
 */

// Set up SEO + JSON-LD before get_header() so wp_head sees our filters.
if (have_posts()) {
    the_post();
    $cb_agent_id     = get_the_ID();
    $cb_agent_name   = get_the_title();
    $cb_agent_title  = get_post_meta($cb_agent_id, 'agent_title', true);
    $cb_agent_phone  = get_post_meta($cb_agent_id, 'agent_phone', true) ?: get_theme_mod('cb_phone', '(325) 944-9559');
    $cb_agent_email  = get_post_meta($cb_agent_id, 'agent_email', true);
    $cb_agent_desigs = get_post_meta($cb_agent_id, 'agent_designations', true);
    $cb_agent_photo  = get_the_post_thumbnail_url($cb_agent_id, 'cb-agent') ?: '';
    $cb_agent_bio    = wp_strip_all_tags(get_the_excerpt() ?: get_the_content());
    $cb_agent_url    = get_permalink($cb_agent_id);

    $cb_agent_seo_desc = $cb_agent_bio
        ? mb_substr(trim(preg_replace('/\s+/', ' ', $cb_agent_bio)), 0, 155)
        : trim("$cb_agent_name — $cb_agent_title at Coldwell Banker Legacy San Angelo. Call $cb_agent_phone.");

    cb_set_seo_meta([
        'title'       => "$cb_agent_name | San Angelo Real Estate Agent | Coldwell Banker Legacy",
        'description' => $cb_agent_seo_desc,
        'canonical'   => $cb_agent_url,
        'og_image'    => $cb_agent_photo,
        'og_type'     => 'profile',
    ]);

    // RealEstateAgent + BreadcrumbList JSON-LD
    add_action('wp_head', function () use ($cb_agent_id, $cb_agent_name, $cb_agent_title, $cb_agent_phone, $cb_agent_email, $cb_agent_photo, $cb_agent_bio, $cb_agent_url, $cb_agent_desigs) {
        $agent_schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'RealEstateAgent',
            '@id'         => $cb_agent_url . '#person',
            'name'        => $cb_agent_name,
            'url'         => $cb_agent_url,
            'jobTitle'    => $cb_agent_title ?: 'Real Estate Agent',
            'description' => mb_substr($cb_agent_bio, 0, 500),
            'telephone'   => preg_replace('/[^0-9+]/', '', $cb_agent_phone),
            'worksFor'    => [
                '@type' => 'RealEstateAgent',
                '@id'   => home_url('/#brokerage'),
                'name'  => 'Coldwell Banker Legacy',
                'url'   => home_url('/'),
            ],
            'areaServed' => [
                ['@type' => 'City',               'name' => 'San Angelo'],
                ['@type' => 'AdministrativeArea', 'name' => 'Concho Valley'],
            ],
        ];
        if ($cb_agent_email) { $agent_schema['email'] = $cb_agent_email; }
        if ($cb_agent_photo) { $agent_schema['image'] = $cb_agent_photo; }
        if ($cb_agent_desigs) {
            $agent_schema['hasCredential'] = $cb_agent_desigs;
        }

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',   'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Agents', 'item' => home_url('/our-team/')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $cb_agent_name],
            ],
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($agent_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    });

    rewind_posts();
}

get_header();

while (have_posts()) : the_post();
    $phone        = get_post_meta(get_the_ID(), 'agent_phone', true);
    $email_addr   = get_post_meta(get_the_ID(), 'agent_email', true);
    $title_text   = get_post_meta(get_the_ID(), 'agent_title', true);
    $designations = get_post_meta(get_the_ID(), 'agent_designations', true);
    $teams        = get_the_terms(get_the_ID(), 'agent_team');
    $specs        = get_the_terms(get_the_ID(), 'agent_specialization');
    $langs        = get_the_terms(get_the_ID(), 'agent_language');
?>

<div style="padding-top:var(--header-height);">

<!-- Agent Hero -->
<section class="cb-section cb-section--navy" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-agent-detail">
            <div class="cb-agent-detail__photo cb-reveal--left">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('cb-agent'); ?>
                <?php else : ?>
                    <div class="cb-agent-detail__placeholder">
                        <span><?php echo esc_html(mb_substr(get_the_title(), 0, 1)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="cb-agent-detail__info cb-reveal--right">
                <h1 style="color:var(--cb-white);margin-bottom:0.25rem;"><?php the_title(); ?></h1>
                <?php if ($title_text) : ?>
                    <p style="color:var(--cb-gold);font-size:1.125rem;font-weight:500;margin-bottom:1rem;"><?php echo esc_html($title_text); ?></p>
                <?php endif; ?>

                <?php if ($designations) : ?>
                    <div style="margin-bottom:1.5rem;">
                        <span class="cb-luxury-badge"><?php echo esc_html($designations); ?></span>
                    </div>
                <?php endif; ?>

                <div class="cb-agent-detail__meta">
                    <?php if ($teams && !is_wp_error($teams)) : ?>
                        <div class="cb-agent-detail__meta-item">
                            <strong style="color:var(--cb-gold-light);">Team:</strong>
                            <span><?php echo esc_html($teams[0]->name); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($specs && !is_wp_error($specs)) : ?>
                        <div class="cb-agent-detail__meta-item">
                            <strong style="color:var(--cb-gold-light);">Specialization:</strong>
                            <span><?php echo esc_html(implode(', ', wp_list_pluck($specs, 'name'))); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($langs && !is_wp_error($langs)) : ?>
                        <div class="cb-agent-detail__meta-item">
                            <strong style="color:var(--cb-gold-light);">Languages:</strong>
                            <span><?php echo esc_html(implode(', ', wp_list_pluck($langs, 'name'))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cb-agent-detail__actions" style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
                    <?php if ($phone) : ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="cb-btn cb-btn--primary">
                            <?php echo cb_get_svg_icon('phone'); ?> <?php echo esc_html($phone); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($email_addr) : ?>
                        <a href="mailto:<?php echo esc_attr($email_addr); ?>" class="cb-btn cb-btn--outline">
                            <?php echo cb_get_svg_icon('email'); ?> Send Email
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Agent Bio -->
<section class="cb-section">
    <div class="cb-container" style="max-width:800px;">
        <?php if (get_the_content()) : ?>
            <div class="cb-reveal">
                <h2 style="margin-bottom:1.5rem;">About <?php echo esc_html(explode(' ', get_the_title())[0]); ?></h2>
                <div class="cb-page-content" style="font-size:1.0625rem;line-height:1.8;color:var(--cb-text-muted);">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="cb-reveal" style="text-align:center;">
                <h2 style="margin-bottom:1rem;">About <?php the_title(); ?></h2>
                <p style="font-size:1.125rem;color:var(--cb-text-muted);line-height:1.8;">
                    <?php the_title(); ?> is an experienced real estate professional with Coldwell Banker Legacy in San Angelo, Texas.
                    <?php if ($specs && !is_wp_error($specs)) : ?>
                        Specializing in <?php echo esc_html(strtolower(implode(', ', wp_list_pluck($specs, 'name')))); ?> properties,
                    <?php endif; ?>
                    <?php echo esc_html(explode(' ', get_the_title())[0]); ?> is dedicated to helping clients navigate the San Angelo real estate market with expertise and care.
                </p>
                <p style="font-size:1.125rem;color:var(--cb-text-muted);line-height:1.8;margin-top:1rem;">
                    Whether you are buying your first home, selling a property, or looking for your next investment, <?php echo esc_html(explode(' ', get_the_title())[0]); ?> is here to guide you every step of the way.
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Active listings by this agent (auto-pulled from MLS) -->
<?php
$agent_full_name = get_the_title();
if ($agent_full_name) :
    $agent_name_sql = str_replace("'", "''", $agent_full_name);
    $listings_expr  = "StandardStatus Eq 'Active' And ListAgentName Eq '$agent_name_sql'";
?>
<section class="cb-section cb-section--offwhite" id="agent-listings" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Active Listings</span>
            <h2 class="cb-section__title"><?php echo esc_html(explode(' ', $agent_full_name)[0]); ?>'s Current Listings</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Live MLS properties currently represented by <?php echo esc_html($agent_full_name); ?>.</p>
        </div>
        <div class="cb-reveal">
            <?php echo CB_Spark_Shortcodes::render_listings([
                'expr'    => $listings_expr,
                'count'   => 6,
                'columns' => 3,
            ]); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Agent-specific testimonials (Testimonial Tree, filtered by agent email) -->
<?php
$agent_email_meta = get_post_meta(get_the_ID(), 'agent_email', true);
if ($agent_email_meta && is_email($agent_email_meta)) : ?>
<section class="cb-section" id="agent-testimonials">
    <div class="cb-container" style="max-width:960px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Client Reviews</span>
            <h2 class="cb-section__title">What <?php echo esc_html(explode(' ', $agent_full_name)[0]); ?>'s Clients Say</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Verified reviews from past clients &mdash; powered by Testimonial Tree.</p>
        </div>
        <div class="cb-reveal cb-tt-frame">
            <?php echo do_shortcode('[cb_testimonials type="rotator" email="' . esc_attr($agent_email_meta) . '"]'); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Work with <?php echo esc_html(explode(' ', get_the_title())[0]); ?></h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Ready to start your real estate journey? Get in touch today.
            </p>
            <div class="cb-reveal" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Call Now</a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg">Send a Message</a>
            </div>
        </div>
    </div>
</section>

</div>

<?php endwhile; ?>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Agent Directory
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Real Estate Agents | Coldwell Banker Legacy Team',
    'description' => 'Meet the Coldwell Banker Legacy team — 30+ experienced San Angelo real estate agents specializing in luxury homes, ranches, first-time buyers, and the entire Concho Valley.',
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero Banner -->
<section class="cb-page-hero">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Coldwell Banker Legacy</span>
        <h1 class="cb-reveal">Meet Our Team</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;opacity:0.9;font-size:1.125rem;">
            Our experienced agents know San Angelo inside and out. Find the perfect partner for your real estate journey.
        </p>
    </div>
</section>

<!-- Search / Filter Bar -->
<section class="cb-agent-filter" id="cb-agent-filter">
    <div class="cb-container">
        <div class="cb-agent-filter__bar cb-reveal">
            <input type="text" id="cb-agent-search" class="cb-agent-filter__input" placeholder="Search by agent name...">
            <select id="cb-agent-team-filter" class="cb-agent-filter__select">
                <option value="">All Teams</option>
                <?php
                $teams = get_terms(['taxonomy' => 'agent_team', 'hide_empty' => true]);
                if (!is_wp_error($teams)) :
                    foreach ($teams as $team) : ?>
                        <option value="<?php echo esc_attr($team->slug); ?>"><?php echo esc_html($team->name); ?></option>
                    <?php endforeach;
                endif; ?>
            </select>
            <select id="cb-agent-spec-filter" class="cb-agent-filter__select">
                <option value="">All Specializations</option>
                <?php
                $specs = get_terms(['taxonomy' => 'agent_specialization', 'hide_empty' => true]);
                if (!is_wp_error($specs)) :
                    foreach ($specs as $spec) : ?>
                        <option value="<?php echo esc_attr($spec->slug); ?>"><?php echo esc_html($spec->name); ?></option>
                    <?php endforeach;
                endif; ?>
            </select>
            <select id="cb-agent-lang-filter" class="cb-agent-filter__select">
                <option value="">All Languages</option>
                <?php
                $langs = get_terms(['taxonomy' => 'agent_language', 'hide_empty' => true]);
                if (!is_wp_error($langs)) :
                    foreach ($langs as $lang) : ?>
                        <option value="<?php echo esc_attr($lang->slug); ?>"><?php echo esc_html($lang->name); ?></option>
                    <?php endforeach;
                endif; ?>
            </select>
        </div>
    </div>
</section>

<!-- Agent Grid -->
<section class="cb-section" id="cb-agent-grid-section">
    <div class="cb-container">
        <div class="cb-agent-grid" id="cb-agent-grid">
            <?php
            $agents = new WP_Query([
                'post_type'      => 'cb_agent',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);

            if ($agents->have_posts()) :
                while ($agents->have_posts()) : $agents->the_post();
                    $phone = get_post_meta(get_the_ID(), 'agent_phone', true);
                    $email_addr = get_post_meta(get_the_ID(), 'agent_email', true);
                    $title_text = get_post_meta(get_the_ID(), 'agent_title', true);
                    $designations = get_post_meta(get_the_ID(), 'agent_designations', true);
            ?>
                <div class="cb-agent-card cb-reveal" data-name="<?php echo esc_attr(strtolower(get_the_title())); ?>">
                    <div class="cb-agent-card__image">
                        <div class="cb-agent-card__curtain"></div>
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('cb-agent'); ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-agent.jpg'); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="cb-agent-card__body">
                        <h3 class="cb-agent-card__name">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php if ($title_text) : ?>
                            <p class="cb-agent-card__title"><?php echo esc_html($title_text); ?></p>
                        <?php endif; ?>
                        <?php if ($designations) : ?>
                            <p style="font-size:0.75rem;color:var(--cb-gold);margin-bottom:0.5rem;"><?php echo esc_html($designations); ?></p>
                        <?php endif; ?>
                        <div class="cb-agent-card__contact">
                            <?php if ($phone) : ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo cb_get_svg_icon('phone'); ?> <?php echo esc_html($phone); ?></a>
                            <?php endif; ?>
                            <?php if ($email_addr) : ?>
                                <a href="mailto:<?php echo esc_attr($email_addr); ?>"><?php echo cb_get_svg_icon('email'); ?> Email</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                // Placeholder agents -- EMPTY-STATE ONLY. This branch runs solely
                // when the cb_agent query returns nothing, so these names are not
                // shipping content; they are what a site with no agents shows.
                // REALTOR® carries the registered mark here for the same reason it
                // does on the live agent titles: it is a collective membership mark
                // and NAR requires the ® wherever the term appears.
                $placeholder_agents = [
                    ['name' => 'Kenneth Wright', 'title' => 'Kenneth and Mandy Team'],
                    ['name' => 'Lance Powell', 'title' => 'Global Luxury Specialist'],
                    ['name' => 'Kriste Chiacchia', 'title' => 'GRI, New Door Team'],
                    ['name' => 'Jerry Delgado', 'title' => 'REALTOR®'],
                    ['name' => 'Jim Mundell', 'title' => 'Mundell Team'],
                    ['name' => 'Tammy Koonce', 'title' => 'Expect the Max Team'],
                    ['name' => 'Lacy B. Ellison', 'title' => 'New Door Team'],
                ];
                foreach ($placeholder_agents as $agent) :
            ?>
                <div class="cb-agent-card cb-reveal" data-name="<?php echo esc_attr(strtolower($agent['name'])); ?>">
                    <div class="cb-agent-card__image">
                        <div class="cb-agent-card__curtain"></div>
                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-agent.jpg'); ?>" alt="<?php echo esc_attr($agent['name']); ?>">
                    </div>
                    <div class="cb-agent-card__body">
                        <h3 class="cb-agent-card__name"><a href="#"><?php echo esc_html($agent['name']); ?></a></h3>
                        <p class="cb-agent-card__title"><?php echo esc_html($agent['title']); ?></p>
                        <div class="cb-agent-card__contact">
                            <a href="tel:3259449559"><?php echo cb_get_svg_icon('phone'); ?> (325) 944-9559</a>
                            <a href="#"><?php echo cb_get_svg_icon('email'); ?> Email</a>
                        </div>
                    </div>
                </div>
            <?php endforeach;
            endif; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Ready to Get Started?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Connect with one of our experienced agents today and take the first step toward your dream home.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg cb-reveal">Contact Us Today</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

</main><!-- #cb-main -->

<footer class="cb-footer">
    <div class="cb-container">
        <div class="cb-footer__grid">
            <!-- Brand Column -->
            <div class="cb-footer__col">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="cb-footer__logo">
                    <?php if (has_custom_logo()) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logo-white.png'); ?>" alt="<?php bloginfo('name'); ?>" style="height:45px;width:auto;">
                    <?php endif; ?>
                </a>
                <p class="cb-footer__brand-desc">
                    Your trusted real estate partner in San Angelo and the Concho Valley. Coldwell Banker Legacy is committed to helping you find your perfect home.
                </p>
                <div class="cb-footer__social">
                    <a href="https://www.facebook.com/ColdwellBankerLegacySanAngelo" target="_blank" rel="noopener" aria-label="Facebook"><?php echo cb_get_svg_icon('facebook'); ?></a>
                    <a href="https://www.instagram.com/cblegacysanangelotx/" target="_blank" rel="noopener" aria-label="Instagram"><?php echo cb_get_svg_icon('instagram'); ?></a>
                    <a href="https://www.linkedin.com/company/coldwell-banker-legacy-san-angelo-texas" target="_blank" rel="noopener" aria-label="LinkedIn"><?php echo cb_get_svg_icon('linkedin'); ?></a>
                    <a href="https://www.youtube.com/channel/UCLIHEWnmYxheqaIfoj2fe4w" target="_blank" rel="noopener" aria-label="YouTube"><?php echo cb_get_svg_icon('youtube'); ?></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="cb-footer__col">
                <h4 class="cb-footer__heading">Quick Links</h4>
                <ul class="cb-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/find-a-home/')); ?>">Find a Home</a></li>
                    <li><a href="<?php echo esc_url(home_url('/home-value/')); ?>">Home Valuation</a></li>
                    <li><a href="<?php echo esc_url(home_url('/our-team/')); ?>">Our Team</a></li>
                    <li><a href="<?php echo esc_url(home_url('/luxury/')); ?>">Luxury Market</a></li>
                    <li><a href="<?php echo esc_url(home_url('/market-report/')); ?>">Market Report</a></li>
                    <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
                </ul>
            </div>

            <!-- Communities -->
            <div class="cb-footer__col">
                <h4 class="cb-footer__heading">Communities</h4>
                <ul class="cb-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/communities/bentwood/')); ?>">Bentwood Homes</a></li>
                    <li><a href="<?php echo esc_url(home_url('/communities/lake-nasworthy/')); ?>">Lake Nasworthy</a></li>
                    <li><a href="<?php echo esc_url(home_url('/communities/college-hills/')); ?>">College Hills</a></li>
                    <li><a href="<?php echo esc_url(home_url('/communities/christoval/')); ?>">Christoval</a></li>
                    <li><a href="<?php echo esc_url(home_url('/communities/wall/')); ?>">Wall</a></li>
                    <li><a href="<?php echo esc_url(home_url('/communities/grape-creek/')); ?>">Grape Creek</a></li>
                </ul>
            </div>

            <!-- School Zones -->
            <div class="cb-footer__col">
                <h4 class="cb-footer__heading">School Zones</h4>
                <ul class="cb-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/schools/central-high-school/')); ?>">Central HS Zone</a></li>
                    <li><a href="<?php echo esc_url(home_url('/schools/lake-view-high-school/')); ?>">Lake View HS Zone</a></li>
                    <li><a href="<?php echo esc_url(home_url('/schools/wall-high-school/')); ?>">Wall HS Zone</a></li>
                    <li><a href="<?php echo esc_url(home_url('/schools/christoval-high-school/')); ?>">Christoval HS Zone</a></li>
                    <li><a href="<?php echo esc_url(home_url('/schools/grape-creek-high-school/')); ?>">Grape Creek HS Zone</a></li>
                    <li><a href="<?php echo esc_url(home_url('/schools/')); ?>">All School Zones</a></li>
                </ul>
            </div>

            <!-- Resources -->
            <div class="cb-footer__col">
                <h4 class="cb-footer__heading">Resources</h4>
                <ul class="cb-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/buyer-seller-resources/')); ?>">Buyer &amp; Seller Tips</a></li>
                    <li><a href="<?php echo esc_url(home_url('/recently-sold/')); ?>">Recently Sold</a></li>
                    <li><a href="<?php echo esc_url(home_url('/market-report/')); ?>">Market Report</a></li>
                    <li><a href="<?php echo esc_url(home_url('/rentals/')); ?>">Rentals</a></li>
                    <li><a href="<?php echo esc_url(home_url('/careers/')); ?>">Careers</a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact Us</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="cb-footer__col">
                <h4 class="cb-footer__heading">Contact</h4>
                <div class="cb-footer__contact-item">
                    <span class="cb-footer__contact-icon"><?php echo cb_get_svg_icon('map-pin'); ?></span>
                    <span><?php echo esc_html(get_theme_mod('cb_address', '3017 Knickerbocker, San Angelo, TX 76904')); ?></span>
                </div>
                <div class="cb-footer__contact-item">
                    <span class="cb-footer__contact-icon"><?php echo cb_get_svg_icon('phone'); ?></span>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cb_phone', '(325) 944-9559'))); ?>" style="color:inherit;">
                        <?php echo esc_html(get_theme_mod('cb_phone', '(325) 944-9559')); ?>
                    </a>
                </div>
                <div class="cb-footer__contact-item">
                    <span class="cb-footer__contact-icon"><?php echo cb_get_svg_icon('email'); ?></span>
                    <a href="mailto:<?php echo esc_attr(get_theme_mod('cb_email', 'info@cbltexas.com')); ?>" style="color:inherit;">
                        <?php echo esc_html(get_theme_mod('cb_email', 'info@cbltexas.com')); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="cb-footer__disclaimer">
            <p class="cb-footer__disclaimer-text">
                &copy; <?php echo date('Y'); ?> Coldwell Banker. All Rights Reserved. Coldwell Banker and the Coldwell Banker logos are trademarks of Coldwell Banker Real Estate LLC. The Coldwell Banker&reg; System is comprised of company owned offices which are owned by a subsidiary of Anywhere Advisors LLC and franchised offices which are independently owned and operated. The Coldwell Banker System fully supports the principles of the Fair Housing Act and the Equal Opportunity Act.
            </p>
            <p class="cb-footer__disclaimer-emphasis">
                <strong>Each office is independently owned and operated.</strong>
            </p>
            <p class="cb-footer__disclaimer-solicit">
                Not intended as a solicitation if your property is already listed by another broker.
            </p>
        </div>

        <div class="cb-footer__bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

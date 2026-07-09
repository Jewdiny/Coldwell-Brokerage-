<?php
/**
 * Spark API Shortcodes
 *
 * Registers [cb_listings] which queries the Spark API and renders the result
 * into our cb-property-card markup.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

class CB_Spark_Shortcodes {

    public static function register() {
        add_shortcode('cb_listings',      [__CLASS__, 'render_listings']);
        add_shortcode('cb_market_stats',  [__CLASS__, 'render_market_stats']);
        add_shortcode('cb_sold_listings', [__CLASS__, 'render_sold_listings']);
    }

    /**
     * [cb_sold_listings days="90" count="24"]
     * Recently sold (Closed) homes pulled from MLS — proof points for buyers.
     */
    public static function render_sold_listings($atts) {
        $atts = shortcode_atts([
            'days'    => 90,
            'count'   => 24,
            'columns' => 3,
            'city'    => 'San Angelo',
        ], $atts, 'cb_sold_listings');

        if (!class_exists('CB_Spark_Client')) { return ''; }
        $client   = new CB_Spark_Client();
        $listings = $client->get_sold_listings(intval($atts['days']), intval($atts['count']), $atts['city']);

        if (is_wp_error($listings) || empty($listings)) {
            return '<p class="cb-spark-empty" style="text-align:center;padding:3rem;color:var(--cb-text-muted);">No recent sales to display for this period.</p>';
        }

        $cols = max(1, min(4, intval($atts['columns'])));
        ob_start();
        ?>
        <div class="cb-property-grid cb-property-grid--cols-<?php echo $cols; ?>" data-cb-sold>
            <?php foreach ($listings as $l) :
                $sold_price = CB_Spark_Client::format_price($l['ClosePrice'] ?? $l['ListPrice'] ?? 0);
                $photo      = CB_Spark_Client::photo_url($l);
                $address    = $l['UnparsedAddress'] ?? '';
                $city       = $l['City'] ?? '';
                $state      = $l['StateOrProvince'] ?? '';
                $zip        = $l['PostalCode'] ?? '';
                $beds       = $l['BedsTotal'] ?? '';
                $baths      = $l['BathsTotal'] ?? '';
                $sqft       = $l['BuildingAreaTotal'] ?? '';
                $close      = $l['CloseDate'] ?? '';
                $close_disp = $close ? date('M j, Y', strtotime($close)) : '';
                $locale     = trim(implode(', ', array_filter([$city, trim("$state $zip")])), ', ');
            ?>
                <div class="cb-property-card cb-property-card--sold">
                    <div class="cb-property-card__image">
                        <?php if ($photo) : ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($address . ' (sold)'); ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="cb-property-card__badge cb-property-card__badge--sold">Sold</span>
                        <?php if ($sold_price) : ?>
                            <span class="cb-property-card__price"><?php echo esc_html($sold_price); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cb-property-card__body">
                        <div class="cb-property-card__header">
                            <span class="cb-property-card__icon"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><path d="M9 21V12h6v9"/></svg></span>
                            <div>
                                <div class="cb-property-card__address"><?php echo esc_html($address); ?></div>
                                <?php if ($locale) : ?><div class="cb-property-card__location"><?php echo esc_html($locale); ?><?php if ($close_disp) : ?> &middot; Sold <?php echo esc_html($close_disp); ?><?php endif; ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="cb-property-card__details">
                            <?php if ($beds && $beds !== '********') : ?><span class="cb-property-card__detail"><strong><?php echo esc_html($beds); ?></strong> Beds</span><?php endif; ?>
                            <?php if ($baths && $baths !== '********') : ?><span class="cb-property-card__detail"><strong><?php echo esc_html($baths); ?></strong> Baths</span><?php endif; ?>
                            <?php if ($sqft) : ?><span class="cb-property-card__detail"><strong><?php echo esc_html(number_format((float)$sqft)); ?></strong> Sq Ft</span><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="cb-spark-attribution">Sold data courtesy of the San Angelo Association of REALTORS&reg; MLS.</p>
        <?php
        return ob_get_clean();
    }

    /**
     * [cb_market_stats city="San Angelo"]
     * Auto-updating market snapshot pulled from the Spark API and cached 6 hours.
     */
    public static function render_market_stats($atts) {
        $atts = shortcode_atts([
            'city'      => 'San Angelo',
            'subdivision' => '',
            'show_buckets' => 'yes',
        ], $atts, 'cb_market_stats');

        if (!class_exists('CB_Spark_Client')) {
            return '<p class="cb-spark-error">Spark client not loaded.</p>';
        }

        $filter = $atts['subdivision']
            ? "SubdivisionName Eq '" . str_replace("'", "''", $atts['subdivision']) . "'"
            : "City Eq '" . str_replace("'", "''", $atts['city']) . "'";

        $client = new CB_Spark_Client();
        $stats  = $client->get_market_stats($filter);

        if (is_wp_error($stats)) {
            return '<p class="cb-spark-error">Market data temporarily unavailable.</p>';
        }

        $area = $atts['subdivision'] ?: $atts['city'];
        $month_year = date('F Y');
        ob_start();
        ?>
        <div class="cb-market-stats">
            <div class="cb-market-stats__header">
                <span class="cb-market-stats__subtitle">Live MLS Snapshot · <?php echo esc_html($month_year); ?></span>
                <h3 class="cb-market-stats__title"><?php echo esc_html($area); ?> Real Estate Market</h3>
            </div>

            <div class="cb-market-stats__grid">
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value"><?php echo number_format($stats['active_total'] ?? 0); ?></div>
                    <div class="cb-market-stat__label">Active Listings</div>
                </div>
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value"><?php echo !empty($stats['median_price']) ? CB_Spark_Client::format_price($stats['median_price']) : '—'; ?></div>
                    <div class="cb-market-stat__label">Median List Price</div>
                </div>
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value">$<?php echo number_format($stats['avg_price_per_sqft'] ?? 0); ?></div>
                    <div class="cb-market-stat__label">Avg Price / Sq Ft</div>
                </div>
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value"><?php echo number_format($stats['new_7_days'] ?? 0); ?></div>
                    <div class="cb-market-stat__label">New This Week</div>
                </div>
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value"><?php echo number_format($stats['new_30_days'] ?? 0); ?></div>
                    <div class="cb-market-stat__label">New This Month</div>
                </div>
                <div class="cb-market-stat">
                    <div class="cb-market-stat__value"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                    <div class="cb-market-stat__label">Pending Sales</div>
                </div>
            </div>

            <?php if ($atts['show_buckets'] === 'yes' && !empty($stats['active_total'])) : ?>
            <div class="cb-market-stats__buckets">
                <h4 class="cb-market-stats__bucket-title">Active Listings by Price</h4>
                <div class="cb-market-stats__bucket-grid">
                    <div class="cb-market-bucket"><span><?php echo number_format($stats['under_300k']); ?></span><label>Under $300K</label></div>
                    <div class="cb-market-bucket"><span><?php echo number_format($stats['mid_300_500k']); ?></span><label>$300K – $500K</label></div>
                    <div class="cb-market-bucket"><span><?php echo number_format($stats['luxury_500k_1m']); ?></span><label>$500K – $1M</label></div>
                    <div class="cb-market-bucket"><span><?php echo number_format($stats['over_1m']); ?></span><label>$1M+</label></div>
                </div>
            </div>
            <?php endif; ?>

            <p class="cb-market-stats__source">Data pulled live from the San Angelo Association of REALTORS&reg; MLS · Updated every 6 hours · Last update <?php echo esc_html($stats['generated_at'] ?? ''); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [cb_listings filter="luxury" count="6" columns="3"]
     *
     * Built-in filter presets:
     *   - "all"      : All Active listings in San Angelo
     *   - "luxury"   : Active in San Angelo, ListPrice >= 500000
     *   - "new"      : Active, last 14 days, San Angelo
     *   - "featured" : Top 3 highest priced Active in San Angelo
     *
     * Or pass `expr` directly for a raw Spark _filter string.
     */
    public static function render_listings($atts) {
        $atts = shortcode_atts([
            'filter'      => 'all',
            'expr'        => '',
            'count'       => 6,
            'columns'     => 3,
            'orderby'     => 'ListPrice desc',
            'min_price'   => 0,
            'max_price'   => 0,
            'city'        => 'San Angelo',
            'class'       => '',
        ], $atts, 'cb_listings');

        $expr = $atts['expr'] ?: self::build_filter($atts);

        if (!class_exists('CB_Spark_Client')) {
            return '<p class="cb-spark-error">Spark client not loaded.</p>';
        }

        $client   = new CB_Spark_Client();
        $listings = $client->get_listings([
            'filter'  => $expr,
            'limit'   => intval($atts['count']),
            'orderby' => $atts['orderby'],
        ]);

        if (is_wp_error($listings)) {
            if (current_user_can('manage_options')) {
                return '<p class="cb-spark-error" style="padding:1rem;background:#fee;border:1px solid #f88;color:#900;">Spark API error: ' . esc_html($listings->get_error_message()) . '</p>';
            }
            return '<p class="cb-spark-error">Listings temporarily unavailable.</p>';
        }

        if (empty($listings)) {
            return '<p class="cb-spark-empty" style="text-align:center;padding:3rem;color:var(--cb-text-muted);">No listings match these criteria right now. Check back soon.</p>';
        }

        $cols  = max(1, min(4, intval($atts['columns'])));
        $extra = $atts['class'] ? ' ' . esc_attr($atts['class']) : '';

        ob_start(); ?>
        <div class="cb-property-grid cb-property-grid--cols-<?php echo $cols; ?><?php echo $extra; ?>" data-cb-spark>
            <?php foreach ($listings as $listing) : self::render_card($listing); endforeach; ?>
        </div>
        <p class="cb-spark-attribution">Listings courtesy of the San Angelo Association of REALTORS&reg; MLS. Information deemed reliable but not guaranteed.</p>
        <?php
        return ob_get_clean();
    }

    /**
     * Build a Spark _filter string from the shortcode args.
     * PropertyType 'A' = Residential.
     */
    private static function build_filter($atts) {
        $parts   = ["StandardStatus Eq 'Active'", "PropertyType Eq 'A'"];
        $city    = $atts['city'] ? str_replace("'", "''", $atts['city']) : '';
        if ($city) { $parts[] = "City Eq '$city'"; }

        switch ($atts['filter']) {
            case 'luxury':
                $parts[] = 'ListPrice Ge 500000';
                break;
            case 'new':
                $parts[] = "OnMarketDate Ge days(-14)";
                break;
            case 'featured':
            case 'all':
            default:
                break;
        }

        if ($atts['min_price'] > 0) { $parts[] = 'ListPrice Ge ' . intval($atts['min_price']); }
        if ($atts['max_price'] > 0) { $parts[] = 'ListPrice Le ' . intval($atts['max_price']); }

        return implode(' And ', $parts);
    }

    /**
     * Render a single listing into our cb-property-card markup
     * (matches the existing structure used by static homepage cards).
     */
    private static function render_card($l) {
        $price_raw = (float)($l['ListPrice'] ?? 0);
        $price   = CB_Spark_Client::format_price($price_raw);
        $photo   = CB_Spark_Client::photo_url($l);
        $address = $l['UnparsedAddress'] ?? '';
        $city    = $l['City'] ?? '';
        $state   = $l['StateOrProvince'] ?? '';
        $zip     = $l['PostalCode'] ?? '';
        $beds    = $l['BedsTotal'] ?? '';
        $baths   = $l['BathsTotal'] ?? '';
        $sqft    = $l['BuildingAreaTotal'] ?? '';
        $status  = $l['StandardStatus'] ?? ($l['MlsStatus'] ?? 'Active');
        $url     = CB_Spark_Client::detail_url($l);
        $locale  = trim(implode(', ', array_filter([$city, trim("$state $zip")])), ', ');
        $lat     = (float)($l['Latitude'] ?? 0);
        $lng     = (float)($l['Longitude'] ?? 0);
        $listing_id = $l['ListingId'] ?? ($l['Id'] ?? '');

        $geo_attrs = '';
        if ($lat && $lng && $lat > 25 && $lat < 50 && $lng < -90 && $lng > -110) {
            $geo_attrs = sprintf(
                ' data-lat="%s" data-lng="%s"',
                esc_attr($lat),
                esc_attr($lng)
            );
        }
        $map_attrs = sprintf(
            ' data-listing-id="%s" data-price="%d" data-beds="%s" data-baths="%s"',
            esc_attr($listing_id),
            (int)$price_raw,
            esc_attr($beds),
            esc_attr($baths)
        );
        ?>
        <a href="<?php echo esc_url($url); ?>" class="cb-property-card cb-property-card--spark"<?php echo $geo_attrs . $map_attrs; ?>>
            <div class="cb-property-card__image">
                <?php if ($photo) : ?>
                    <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($address); ?>" loading="lazy">
                <?php endif; ?>
                <?php if ($status) : ?>
                    <span class="cb-property-card__badge"><?php echo esc_html($status); ?></span>
                <?php endif; ?>
                <?php if ($listing_id) : ?>
                    <span class="cb-fav" role="button" tabindex="0" aria-label="Save this home" aria-pressed="false" data-fav-id="<?php echo esc_attr($listing_id); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21l-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    </span>
                <?php endif; ?>
                <?php if ($price) : ?>
                    <span class="cb-property-card__price"><?php echo esc_html($price); ?></span>
                <?php endif; ?>
            </div>
            <div class="cb-property-card__body">
                <div class="cb-property-card__header">
                    <span class="cb-property-card__icon">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><path d="M9 21V12h6v9"/></svg>
                    </span>
                    <div>
                        <div class="cb-property-card__address"><?php echo esc_html($address ?: $locale); ?></div>
                        <?php if ($locale) : ?>
                            <div class="cb-property-card__location"><?php echo esc_html($locale); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cb-property-card__details">
                    <?php if ($beds !== '') : ?>
                        <span class="cb-property-card__detail"><strong><?php echo esc_html($beds); ?></strong> Beds</span>
                    <?php endif; ?>
                    <?php if ($baths !== '') : ?>
                        <span class="cb-property-card__detail"><strong><?php echo esc_html($baths); ?></strong> Baths</span>
                    <?php endif; ?>
                    <?php if ($sqft) : ?>
                        <span class="cb-property-card__detail"><strong><?php echo esc_html(number_format((float)$sqft)); ?></strong> Sq Ft</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php
    }
}

CB_Spark_Shortcodes::register();

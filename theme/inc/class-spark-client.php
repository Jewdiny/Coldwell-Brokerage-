<?php
/**
 * Spark API Client (Flexmls)
 *
 * Implements the Spark Platform "FBS WordPress" auth flow:
 *   1. POST /session with md5(secret + "ApiKey" + key) -> AuthToken
 *   2. Each subsequent request is individually signed with:
 *        md5(secret + "ApiKey" + key + "ServicePath/v1/<service>"
 *            + sorted(key+value concatenation of all params, including AuthToken))
 *   3. Send all params (incl. AuthToken + ApiSig) as query string.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

class CB_Spark_Client {

    const API_HOST        = 'https://api.flexmls.com';
    const API_VERSION     = 'v1';
    const TOKEN_TRANSIENT = 'cb_spark_token';
    const TOKEN_TTL       = 50 * MINUTE_IN_SECONDS;
    const LISTINGS_TTL    = 15 * MINUTE_IN_SECONDS;

    private $api_key;
    private $api_secret;

    public function __construct() {
        $this->api_key    = defined('CB_SPARK_KEY')    ? CB_SPARK_KEY    : get_option('cb_spark_key', '');
        $this->api_secret = defined('CB_SPARK_SECRET') ? CB_SPARK_SECRET : get_option('cb_spark_secret', '');
    }

    /**
     * Get a session AuthToken (cached in a transient).
     */
    public function get_token() {
        $cached = get_transient(self::TOKEN_TRANSIENT);
        if ($cached) { return $cached; }

        if (!$this->api_key || !$this->api_secret) {
            return new WP_Error('cb_spark_no_creds', 'Spark API credentials not configured.');
        }

        $sig  = md5($this->api_secret . 'ApiKey' . $this->api_key);
        $url  = self::API_HOST . '/' . self::API_VERSION . '/session'
              . '?ApiKey=' . rawurlencode($this->api_key) . '&ApiSig=' . $sig;
        $resp = wp_remote_post($url, ['timeout' => 15]);

        if (is_wp_error($resp)) { return $resp; }
        $body  = json_decode(wp_remote_retrieve_body($resp), true);
        $token = $body['D']['Results'][0]['AuthToken'] ?? null;
        if (!$token) {
            return new WP_Error('cb_spark_auth_failed', 'Spark API authentication failed.', $body);
        }

        set_transient(self::TOKEN_TRANSIENT, $token, self::TOKEN_TTL);
        return $token;
    }

    /**
     * Sign + send a GET request to a Spark service path.
     *
     * @param string $service e.g. "listings"
     * @param array  $params  Query params (without AuthToken / ApiSig)
     * @return array|WP_Error Decoded D body on success, WP_Error on failure.
     */
    public function get($service, $params = []) {
        $token = $this->get_token();
        if (is_wp_error($token)) { return $token; }

        $params['AuthToken'] = $token;
        ksort($params);

        $sec = $this->api_secret . 'ApiKey' . $this->api_key
             . 'ServicePath/' . self::API_VERSION . '/' . $service;
        foreach ($params as $k => $v) { $sec .= $k . $v; }

        $params['ApiSig'] = md5($sec);
        $url  = self::API_HOST . '/' . self::API_VERSION . '/' . $service . '?' . build_query($params);
        $resp = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($resp)) { return $resp; }
        $body = json_decode(wp_remote_retrieve_body($resp), true);

        // If the token expired, clear cache and retry once.
        if (!empty($body['D']['Code']) && $body['D']['Code'] == 1020) {
            delete_transient(self::TOKEN_TRANSIENT);
            return $this->get($service, array_diff_key($params, ['AuthToken' => 1, 'ApiSig' => 1]));
        }

        if (empty($body['D']['Success'])) {
            return new WP_Error('cb_spark_request_failed', $body['D']['Message'] ?? 'Spark request failed.', $body);
        }

        return $body['D'];
    }

    /**
     * Query listings.
     *
     * @param array $args {
     *   @type string $filter   Spark _filter expression (e.g. "City Eq 'San Angelo'")
     *   @type int    $limit    Max number of listings
     *   @type string $orderby  e.g. "ListPrice desc"
     *   @type bool   $bypass_cache  Force fresh API call
     * }
     * @return array|WP_Error
     */
    public function get_listings($args = []) {
        $args = wp_parse_args($args, [
            'filter'       => "StandardStatus Eq 'Active'",
            'limit'        => 12,
            'orderby'      => 'ListPrice desc',
            'bypass_cache' => false,
        ]);

        $cache_key = 'cb_spark_l_' . md5(wp_json_encode($args));
        if (!$args['bypass_cache']) {
            $cached = get_transient($cache_key);
            if ($cached !== false) { return $cached; }
        }

        $select = 'ListingId,ListPrice,UnparsedAddress,City,StateOrProvince,PostalCode,'
                . 'BedsTotal,BathsTotal,BuildingAreaTotal,LotSizeAcres,YearBuilt,'
                . 'StandardStatus,MlsStatus,PropertyType,PropertySubType,'
                . 'PublicRemarks,ListAgentName,ListOfficeName,ListingUpdateTimestamp,'
                . 'Latitude,Longitude';

        $params = [
            '_limit'   => intval($args['limit']),
            '_orderby' => $args['orderby'],
            '_select'  => $select,
            '_expand'  => 'PrimaryPhoto',
        ];
        if (!empty($args['filter'])) { $params['_filter'] = $args['filter']; }

        $data = $this->get('listings', $params);
        if (is_wp_error($data)) { return $data; }

        $listings = [];
        foreach (($data['Results'] ?? []) as $row) {
            $f       = $row['StandardFields'] ?? [];
            $f['Id'] = $row['Id'] ?? '';
            $listings[] = $f;
        }

        set_transient($cache_key, $listings, self::LISTINGS_TTL);
        return $listings;
    }

    /**
     * Fetch a single listing's full data + all photos, cached.
     *
     * @param string $listing_id MLS listing Id
     * @return array|WP_Error    StandardFields merged with Id; WP_Error on failure
     */
    public function get_listing_detail($listing_id) {
        $listing_id = preg_replace('/[^a-zA-Z0-9]/', '', $listing_id);
        if (!$listing_id) {
            return new WP_Error('cb_spark_bad_id', 'Invalid listing id.');
        }

        $cache_key = 'cb_spark_d_' . md5($listing_id);
        $cached    = get_transient($cache_key);
        if ($cached !== false) { return $cached; }

        $data = $this->get('listings/' . $listing_id, [
            '_expand' => 'Photos',
        ]);
        if (is_wp_error($data)) { return $data; }

        $row = $data['Results'][0] ?? null;
        if (!$row) {
            return new WP_Error('cb_spark_not_found', 'Listing not found.');
        }

        $f       = $row['StandardFields'] ?? [];
        $f['Id'] = $row['Id'] ?? $listing_id;

        set_transient($cache_key, $f, self::LISTINGS_TTL);
        return $f;
    }

    /**
     * Query recently sold (Closed) listings.
     *
     * @param int    $days_back  Look back this many days for CloseDate
     * @param int    $limit      Max listings to return
     * @param string $city
     * @return array|WP_Error
     */
    public function get_sold_listings($days_back = 90, $limit = 24, $city = 'San Angelo') {
        $cache_key = 'cb_spark_sold_' . md5($days_back . '|' . $limit . '|' . $city);
        $cached    = get_transient($cache_key);
        if ($cached !== false) { return $cached; }

        $city_filter = $city ? " And City Eq '" . str_replace("'", "''", $city) . "'" : '';
        $params = [
            '_filter'  => "StandardStatus Eq 'Closed' And CloseDate Ge days(-{$days_back})" . $city_filter,
            '_limit'   => intval($limit),
            '_orderby' => 'CloseDate desc',
            '_select'  => 'ListingId,ListPrice,ClosePrice,CloseDate,UnparsedAddress,City,StateOrProvince,PostalCode,'
                        . 'BedsTotal,BathsTotal,BuildingAreaTotal,StandardStatus,MlsStatus,'
                        . 'PropertyType,PropertySubType,SubdivisionName',
            '_expand'  => 'PrimaryPhoto',
        ];

        $data = $this->get('listings', $params);
        if (is_wp_error($data)) { return $data; }

        $listings = [];
        foreach (($data['Results'] ?? []) as $row) {
            $f       = $row['StandardFields'] ?? [];
            $f['Id'] = $row['Id'] ?? '';
            $listings[] = $f;
        }

        set_transient($cache_key, $listings, self::LISTINGS_TTL);
        return $listings;
    }

    /**
     * Aggregate market stats for a city or filter expression.
     * Cached for 6 hours since these numbers don't change minute-to-minute.
     *
     * @param string $base_filter SparkQL filter for "active in this market" (default San Angelo).
     * @return array|WP_Error
     */
    public function get_market_stats($base_filter = "City Eq 'San Angelo'") {
        $cache_key = 'cb_spark_mkt_' . md5($base_filter);
        $cached    = get_transient($cache_key);
        if ($cached !== false) { return $cached; }

        // Use Pagination.TotalRows on TotalRows-cheap queries for each bucket.
        // _limit=1 keeps payload tiny but still returns the total count.
        $buckets = [
            'active_total'   => "StandardStatus Eq 'Active' And $base_filter",
            'under_300k'     => "StandardStatus Eq 'Active' And $base_filter And ListPrice Lt 300000",
            'mid_300_500k'   => "StandardStatus Eq 'Active' And $base_filter And ListPrice Ge 300000 And ListPrice Lt 500000",
            'luxury_500k_1m' => "StandardStatus Eq 'Active' And $base_filter And ListPrice Ge 500000 And ListPrice Lt 1000000",
            'over_1m'        => "StandardStatus Eq 'Active' And $base_filter And ListPrice Ge 1000000",
            'new_7_days'     => "StandardStatus Eq 'Active' And $base_filter And OnMarketDate Ge days(-7)",
            'new_30_days'    => "StandardStatus Eq 'Active' And $base_filter And OnMarketDate Ge days(-30)",
            'pending'        => "StandardStatus Eq 'Pending' And $base_filter",
        ];

        $stats = [];
        foreach ($buckets as $key => $filter) {
            $data = $this->get('listings', [
                '_filter'     => $filter,
                '_select'     => 'Id',
                '_limit'      => 1,
                '_pagination' => 1,
            ]);
            if (is_wp_error($data)) {
                $stats[$key] = 0;
                continue;
            }
            $stats[$key] = (int) ($data['Pagination']['TotalRows'] ?? 0);
        }

        // Pull a 50-listing sample to compute approximate median + avg price.
        $sample = $this->get('listings', [
            '_filter'  => "StandardStatus Eq 'Active' And $base_filter",
            '_select'  => 'ListPrice,BedsTotal,BathsTotal,BuildingAreaTotal',
            '_orderby' => 'ListPrice',
            '_limit'   => 50,
        ]);
        if (!is_wp_error($sample) && !empty($sample['Results'])) {
            $prices  = [];
            $sqfts   = [];
            foreach ($sample['Results'] as $row) {
                $f = $row['StandardFields'] ?? [];
                if (!empty($f['ListPrice'])) { $prices[] = (float) $f['ListPrice']; }
                if (!empty($f['BuildingAreaTotal']) && !empty($f['ListPrice'])) {
                    $sqfts[] = (float) $f['ListPrice'] / max(1, (float) $f['BuildingAreaTotal']);
                }
            }
            if ($prices) {
                sort($prices);
                $n = count($prices);
                $stats['median_price'] = (int) ($n % 2 ? $prices[intdiv($n, 2)] : ($prices[$n/2 - 1] + $prices[$n/2]) / 2);
                $stats['avg_price']    = (int) (array_sum($prices) / $n);
                $stats['low_price']    = (int) $prices[0];
                $stats['high_price']   = (int) end($prices);
            }
            if ($sqfts) {
                $stats['avg_price_per_sqft'] = (int) (array_sum($sqfts) / count($sqfts));
            }
        }

        $stats['generated_at'] = current_time('mysql');
        set_transient($cache_key, $stats, 6 * HOUR_IN_SECONDS);
        return $stats;
    }

    /**
     * Format price for display.
     */
    public static function format_price($value) {
        $value = (float) $value;
        if ($value <= 0) { return ''; }
        if ($value >= 1000000) {
            return '$' . rtrim(rtrim(number_format($value / 1000000, 2), '0'), '.') . 'M';
        }
        return '$' . number_format($value);
    }

    /**
     * Pull the best photo URL out of a listing's Photos array
     * (populated when _expand=PrimaryPhoto is sent).
     */
    public static function photo_url($listing, $size = 'Uri800') {
        $photos = $listing['Photos'] ?? [];
        if (empty($photos[0])) { return ''; }
        $p = $photos[0];
        foreach ([$size, 'Uri800', 'Uri640', 'Uri300', 'UriThumb'] as $key) {
            if (!empty($p[$key])) { return $p[$key]; }
        }
        return '';
    }

    /**
     * All photo URLs in display order, picking the best size available per photo.
     *
     * @param array  $listing  StandardFields with Photos array (full _expand=Photos)
     * @param string $size     Preferred resolution (defaults to Uri1280 for galleries)
     * @return array           List of photo URL strings
     */
    public static function photo_urls($listing, $size = 'Uri1280') {
        $out = [];
        foreach (($listing['Photos'] ?? []) as $p) {
            foreach ([$size, 'Uri1024', 'Uri800', 'Uri640', 'Uri300'] as $k) {
                if (!empty($p[$k])) { $out[] = $p[$k]; break; }
            }
        }
        return $out;
    }

    /**
     * Public detail URL for a listing.
     * Accepts either a full listing array (preferred — produces SEO slug) or a bare ID.
     *
     * @param array|string $listing  Listing array or MLS Id string
     * @return string                Absolute URL on this site
     */
    public static function detail_url($listing) {
        if (is_array($listing)) {
            $id      = $listing['Id'] ?? '';
            $address = $listing['UnparsedAddress'] ?? '';
            if ($id && $address) {
                $slug = sanitize_title($address);
                if ($slug) {
                    return home_url('/listing/' . $slug . '-' . $id . '/');
                }
                return home_url('/listing/' . rawurlencode($id) . '/');
            }
            return home_url('/listing/' . rawurlencode((string) $id) . '/');
        }
        return home_url('/listing/' . rawurlencode((string) $listing) . '/');
    }
}

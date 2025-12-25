<?php
namespace MySeoTask\Rest\Controllers;

use MySeoTask\Telemetry\EventService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EventsController {

    /**
     * Whitelist event types accepted by the collector.
     */
    private static $allowed = [
        'flow_start', 'flow_complete',
        'task_show', 'task_start', 'task_complete', 'task_fail',
        'ui_start_button_show', 'ui_start_button_click',
        'ui_popup_show', 'ui_popup_close',
        'ui_navigator_show', 'ui_navigator_hide',
    ];

    /**
     * Page types accepted from the client (best-effort normalization).
     */
    private static $allowed_page_types = [
        'product', 'category', 'cart', 'checkout', 'search', 'post', 'generic',
    ];

    /**
     * REST permission callback.
     *
     * Harden /events endpoint without breaking guest tracking:
     * - Block obvious bots by User-Agent signature.
     * - Soft same-origin check (only blocks when Origin/Referer is present & mismatched).
     * - Rate limit by session_id (preferred) and by REMOTE_ADDR (fallback).
     */
    public static function permission_check( \WP_REST_Request $request ) {
        // 1) Block obvious bots.
        if ( self::is_bot_user_agent() ) {
            return new \WP_Error( 'myseotask_bot_blocked', 'Bot traffic not allowed', [ 'status' => 403 ] );
        }

        // 2) Soft same-origin check.
        $origin  = (string) $request->get_header( 'origin' );
        $referer = (string) $request->get_header( 'referer' );
        $site_host = parse_url( home_url(), PHP_URL_HOST );
        $site_host = $site_host ? strtolower( $site_host ) : '';

        $origin_host  = $origin ? strtolower( (string) parse_url( $origin, PHP_URL_HOST ) ) : '';
        $referer_host = $referer ? strtolower( (string) parse_url( $referer, PHP_URL_HOST ) ) : '';

        // Only enforce when the header exists and can be parsed.
        if ( $site_host ) {
            if ( $origin_host && $origin_host !== $site_host ) {
                return new \WP_Error( 'myseotask_origin_mismatch', 'Origin not allowed', [ 'status' => 403 ] );
            }
            if ( ! $origin_host && $referer_host && $referer_host !== $site_host ) {
                return new \WP_Error( 'myseotask_referer_mismatch', 'Referer not allowed', [ 'status' => 403 ] );
            }
        }

        // 3) Rate limiting.
        // Session-based limit (best) — avoids blocking NAT groups.
        $sid = $request->get_param( 'session_id' );
        $sid = is_string( $sid ) ? trim( $sid ) : '';
        if ( $sid !== '' ) {
            $res = self::rate_limit( 's', $sid, 120, 120 ); // 120 events / 120s / session
            if ( is_wp_error( $res ) ) return $res;
        }

        // IP-based limit (fallback) — use REMOTE_ADDR to avoid spoofing.
        $ip = self::get_client_ip();
        if ( $ip ) {
            $res = self::rate_limit( 'i', $ip, 300, 120 ); // 300 events / 120s / IP
            if ( is_wp_error( $res ) ) return $res;
        }

        return true;
    }

    public static function store_event( \WP_REST_Request $request ) {
        $event_type = sanitize_text_field( $request->get_param( 'event_type' ) );
        if ( ! in_array( $event_type, self::$allowed, true ) ) {
            return new \WP_Error( 'invalid_event', 'Event type not allowed', [ 'status' => 400 ] );
        }

        // Require a valid session id for meaningful telemetry.
        $session_id = $request->get_param( 'session_id' );
        $session_id = is_string( $session_id ) ? trim( $session_id ) : '';
        if ( $session_id === '' ) {
            return new \WP_Error( 'missing_session', 'session_id is required', [ 'status' => 400 ] );
        }
        if ( ! self::is_valid_session_id( $session_id ) ) {
            return new \WP_Error( 'invalid_session', 'session_id format invalid', [ 'status' => 400 ] );
        }

        // page_type normalization
        $page_type = sanitize_text_field( $request->get_param( 'page_type' ) );
        $page_type = $page_type ? strtolower( $page_type ) : '';
        if ( $page_type && ! in_array( $page_type, self::$allowed_page_types, true ) ) {
            // Unknown page_type: normalize to generic (do not error to keep telemetry continuity).
            $page_type = 'generic';
        }

        // task_id sanitation (optional)
        $task_id = sanitize_text_field( $request->get_param( 'task_id' ) );
        if ( $task_id && strlen( $task_id ) > 128 ) {
            $task_id = substr( $task_id, 0, 128 );
        }

        // duration_ms sanitation (optional)
        $duration_ms = $request->get_param( 'duration_ms' );
        $duration_ms = is_numeric( $duration_ms ) ? intval( $duration_ms ) : 0;
        if ( $duration_ms < 0 ) $duration_ms = 0;
        if ( $duration_ms > 3600000 ) $duration_ms = 3600000; // cap at 1 hour

        // reason_fail sanitation (optional)
        $reason_fail = sanitize_text_field( $request->get_param( 'reason_fail' ) );
        if ( $reason_fail && strlen( $reason_fail ) > 120 ) {
            $reason_fail = substr( $reason_fail, 0, 120 );
        }

        // meta sanitation (optional)
        $meta = $request->get_param( 'meta' );
        $meta = self::sanitize_meta( $meta );

        $payload = [
            'event_type'  => $event_type,
            'session_id'  => $session_id,
            'user_id'     => get_current_user_id() ?: null,
            'page_type'   => $page_type,
            'task_id'     => $task_id,
            'duration_ms' => $duration_ms ?: null,
            'reason_fail' => $reason_fail ?: null,
            'meta'        => $meta,
        ];

        EventService::record( $payload );

        return rest_ensure_response( [ 'ok' => true ] );
    }

    private static function is_valid_session_id( $sid ) {
        // Matches client generator: sess_<alnum>_<timestamp>, but keeps it flexible.
        if ( strlen( $sid ) > 128 ) return false;
        // Allow letters, digits, underscore, dash, dot.
        return (bool) preg_match( '/^[A-Za-z0-9_.-]{6,128}$/', $sid );
    }

    private static function is_bot_user_agent() {
        if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }
        $ua = strtolower( (string) $_SERVER['HTTP_USER_AGENT'] );
        $bot_signatures = [
            'googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot','sogou','exabot',
            'facebookexternalhit','facebot','ia_archiver','ahrefsbot','semrushbot','mj12bot',
            'bot/','crawler','spider','uptimerobot','pingdom','chrome-lighthouse',
        ];
        foreach ( $bot_signatures as $sig ) {
            if ( strpos( $ua, $sig ) !== false ) {
                return true;
            }
        }
        return false;
    }

    private static function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $ip = $ip ? trim( $ip ) : '';

        /**
         * Allow site owners to override IP detection (e.g., behind a trusted proxy/CDN).
         */
        $ip = apply_filters( 'myseotask_client_ip', $ip );

        if ( ! $ip ) return '';
        // Basic sanity check.
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) return '';
        return $ip;
    }

    /**
     * Simple transient-based sliding window rate limiter.
     */
    private static function rate_limit( $prefix, $identity, $limit, $window_seconds ) {
        $identity = (string) $identity;
        if ( $identity === '' ) return true;

        // Transient names must stay short.
        $key = 'mst_rl_' . $prefix . '_' . substr( md5( $identity ), 0, 16 );
        $now = time();

        $state = get_transient( $key );
        if ( ! is_array( $state ) || ! isset( $state['count'], $state['start'] ) ) {
            set_transient( $key, [ 'count' => 1, 'start' => $now ], $window_seconds );
            return true;
        }

        $start = intval( $state['start'] );
        $count = intval( $state['count'] );
        if ( $start <= 0 ) $start = $now;
        if ( $count < 0 ) $count = 0;

        if ( ( $start + $window_seconds ) <= $now ) {
            // Window expired — reset.
            set_transient( $key, [ 'count' => 1, 'start' => $now ], $window_seconds );
            return true;
        }

        if ( $count >= $limit ) {
            return new \WP_Error( 'myseotask_rate_limited', 'Too many events', [ 'status' => 429 ] );
        }

        $count++;
        $ttl = max( 1, ( $start + $window_seconds ) - $now );
        set_transient( $key, [ 'count' => $count, 'start' => $start ], $ttl );
        return true;
    }

    /**
     * Sanitize meta payload to keep DB clean and reduce XSS risk if rendered later.
     * - Scalars: string-sanitize.
     * - Arrays/objects: sanitize keys & scalar values, cap depth and total entries.
     */
    private static function sanitize_meta( $meta ) {
        if ( $meta === null ) return null;

        // Convert objects to arrays (best-effort)
        if ( is_object( $meta ) ) {
            $meta = json_decode( wp_json_encode( $meta ), true );
        }

        if ( is_scalar( $meta ) ) {
            $s = sanitize_text_field( (string) $meta );
            return $s !== '' ? $s : null;
        }

        if ( ! is_array( $meta ) ) {
            return null;
        }

        $out = [];
        $max_depth = 3;
        $max_items = 50;
        $items = 0;

        $sanitize_node = function( $node, $depth ) use ( &$sanitize_node, &$items, $max_depth, $max_items ) {
            if ( $items >= $max_items ) return null;

            if ( $node === null ) return null;
            if ( is_object( $node ) ) {
                $node = json_decode( wp_json_encode( $node ), true );
            }

            if ( is_scalar( $node ) ) {
                $items++;
                $s = sanitize_text_field( (string) $node );
                if ( strlen( $s ) > 500 ) $s = substr( $s, 0, 500 );
                return $s;
            }

            if ( ! is_array( $node ) ) {
                return null;
            }

            if ( $depth >= $max_depth ) {
                $items++;
                return '[truncated]';
            }

            $clean = [];
            foreach ( $node as $k => $v ) {
                if ( $items >= $max_items ) break;
                $key = is_string( $k ) ? sanitize_key( $k ) : (string) $k;
                if ( $key === '' ) $key = 'k' . $items;
                $clean[ $key ] = $sanitize_node( $v, $depth + 1 );
            }
            return $clean;
        };

        $out = $sanitize_node( $meta, 0 );

        // If still too large, rely on EventService (5000 chars cap) but try to avoid huge structures.
        $json = $out ? wp_json_encode( $out ) : '';
        if ( $json && strlen( $json ) > 4500 ) {
            return [ '_truncated' => true ];
        }

        return $out;
    }
}

<?php
namespace MySeoTask\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Config {

    const OPTION_KEY = 'myseotask_settings';
    const TASK_STATE_OPTION_KEY = 'myseotask_tasks_state';
    const TASK_GUIDES_OPTION_KEY = 'myseotask_task_guides';

    protected static $defaults = [
        'enabled'    => true,
        'debug_mode' => false,
        'telemetry'  => [
            'retention_days' => 30,
        ],
        'ui' => [
            'start_button' => [
                'text'                 => 'Bắt đầu nhiệm vụ',
                'dom_id'               => '',
                'scroll_threshold'     => 0.5,
                'delay_ms'             => 1000,
                'only_if_eligible'     => true,
                'max_show_per_session' => 3,
            ],
            'gate' => [
                'enabled'               => true,
                'min_seconds'           => 18,
                'min_interactions'      => 6,
                'min_scroll_px'         => 600,
                'min_depth_percent'     => 12,
                'min_pause_ms'          => 2800,
                'anti_fast_scroll_mode' => 'alert',
            ],
            'task_popup' => [
                'enabled'            => true,
                'auto_close_ms'      => 3000,
                'show_close_button'  => true,
                'show_on'            => [ 'flow_start' ], // flow_start, task_change
                'once_per_session'   => true,
                'cooldown_minutes'   => 0,
            ],
        ],
        'disabledTaskIds' => [],
        'task_guides'     => [], // map task_id => html/text hướng dẫn
    ];

    public static function init() { /* noop */ }

    public static function defaults() {
        return self::$defaults;
    }

    public static function all() {
        $saved = get_option( self::OPTION_KEY, [] );
        $merged = self::merge_recursive( self::$defaults, $saved );

        // Tính disabledTaskIds từ option tasks_state
        $merged['disabledTaskIds'] = self::compute_disabled_task_ids();

        // Lấy hướng dẫn nhiệm vụ từ option riêng
        $guides = get_option( self::TASK_GUIDES_OPTION_KEY, [] );
        if ( ! is_array( $guides ) ) $guides = [];
        $merged['task_guides'] = $guides;

        return $merged;
    }

    public static function get( $key = null, $default = null ) {
        $all = self::all();
        if ( $key === null ) return $all;
        $path = explode( '.', $key );
        $val = $all;
        foreach ( $path as $k ) {
            if ( is_array( $val ) && array_key_exists( $k, $val ) ) {
                $val = $val[ $k ];
            } else {
                return $default;
            }
        }
        return $val;
    }

    protected static function merge_recursive( $defaults, $saved ) {
        foreach ( $saved as $k => $v ) {
            if ( is_array( $v ) && isset( $defaults[ $k ] ) && is_array( $defaults[ $k ] ) ) {
                $defaults[ $k ] = self::merge_recursive( $defaults[ $k ], $v );
            } else {
                $defaults[ $k ] = $v;
            }
        }
        return $defaults;
    }

    protected static function compute_disabled_task_ids() {
        $opt = get_option( self::TASK_STATE_OPTION_KEY, null );
        if ( empty( $opt ) || ! is_array( $opt ) ) {
            return [];
        }
        $disabled = [];
        foreach ( $opt as $taskId => $enabledFlag ) {
            if ( ! $enabledFlag ) {
                $disabled[] = $taskId;
            }
        }
        return $disabled;
    }
}
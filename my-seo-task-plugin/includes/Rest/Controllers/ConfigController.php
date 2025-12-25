<?php
namespace MySeoTask\Rest\Controllers;

use MySeoTask\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConfigController {

    /**
     * GET /wp-json/myseotask/v1/config
     *
     * Trả về cấu hình client theo đúng nguồn dữ liệu hiện tại (Config::all()).
     */
    public static function get_config( \WP_REST_Request $request ) {
        $cfg = Config::all();

        // (Optional) Bổ sung một ít meta runtime cho client nếu cần debug.
        $cfg['_meta'] = [
            'generated_at' => time(),
        ];

        return rest_ensure_response( $cfg );
    }
}

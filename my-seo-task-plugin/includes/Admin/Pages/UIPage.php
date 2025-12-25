<?php
namespace MySeoTask\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UIPage {

    const OPTION_KEY = 'myseotask_settings';

    public static function register_hooks() {
        add_action( 'admin_post_myseotask_save_ui', [ __CLASS__, 'handle_save' ] );
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Không có quyền', 'my-seo-task' ) );
        }
        check_admin_referer( 'myseotask_ui_nonce' );

        // Lấy option hiện có
        $opt = get_option( self::OPTION_KEY, [] );

        // Start button
        $sb = [
            'text'                 => sanitize_text_field( wp_unslash( $_POST['start_button_text'] ?? 'Bắt đầu nhiệm vụ' ) ),
            'dom_id'               => sanitize_text_field( wp_unslash( $_POST['start_button_dom_id'] ?? '' ) ),
            'scroll_threshold'     => max( 0, min( 1, floatval( wp_unslash( $_POST['start_button_scroll_threshold'] ?? 0.5 ) ) ) ),
            'delay_ms'             => max( 0, intval( wp_unslash( $_POST['start_button_delay_ms'] ?? 1000 ) ) ),
            'only_if_eligible'     => isset( $_POST['start_button_only_if_eligible'] ) ? (bool) $_POST['start_button_only_if_eligible'] : false,
            'max_show_per_session' => max( 0, intval( wp_unslash( $_POST['start_button_max_show'] ?? 3 ) ) ),
        ];

        // Gate
        $gate = [
            'enabled'               => isset( $_POST['gate_enabled'] ) ? (bool) $_POST['gate_enabled'] : false,
            'min_seconds'           => max( 0, intval( wp_unslash( $_POST['gate_min_seconds'] ?? 18 ) ) ),
            'min_interactions'      => max( 0, intval( wp_unslash( $_POST['gate_min_interactions'] ?? 6 ) ) ),
            'min_scroll_px'         => max( 0, intval( wp_unslash( $_POST['gate_min_scroll_px'] ?? 600 ) ) ),
            'min_depth_percent'     => max( 0, min( 100, intval( wp_unslash( $_POST['gate_min_depth_percent'] ?? 12 ) ) ) ),
            'min_pause_ms'          => max( 0, intval( wp_unslash( $_POST['gate_min_pause_ms'] ?? 2800 ) ) ),
            'anti_fast_scroll_mode' => sanitize_text_field( wp_unslash( $_POST['gate_anti_fast_scroll_mode'] ?? 'alert' ) ),
        ];
        if ( ! in_array( $gate['anti_fast_scroll_mode'], [ 'alert', 'none', 'strict' ], true ) ) {
            $gate['anti_fast_scroll_mode'] = 'alert';
        }

        // Task popup
        $tp_show_on = isset( $_POST['task_popup_show_on'] ) && is_array( $_POST['task_popup_show_on'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['task_popup_show_on'] ) )
            : [];
        $tp_show_on = array_values( array_intersect( $tp_show_on, [ 'flow_start', 'task_change' ] ) );
        if ( empty( $tp_show_on ) ) {
            $tp_show_on = [ 'flow_start' ];
        }

        $tp = [
            'enabled'           => isset( $_POST['task_popup_enabled'] ) ? (bool) $_POST['task_popup_enabled'] : false,
            'auto_close_ms'     => max( 500, intval( wp_unslash( $_POST['task_popup_auto_close_ms'] ?? 3000 ) ) ),
            'show_close_button' => isset( $_POST['task_popup_show_close_button'] ) ? (bool) $_POST['task_popup_show_close_button'] : false,
            'show_on'           => $tp_show_on,
            'once_per_session'  => isset( $_POST['task_popup_once_per_session'] ) ? (bool) $_POST['task_popup_once_per_session'] : false,
            'cooldown_minutes'  => max( 0, intval( wp_unslash( $_POST['task_popup_cooldown_minutes'] ?? 0 ) ) ),
        ];

        $opt['ui']['start_button'] = $sb;
        $opt['ui']['gate']         = $gate;
        $opt['ui']['task_popup']   = $tp;

        update_option( self::OPTION_KEY, $opt );

        wp_safe_redirect( add_query_arg( [ 'page' => 'myseotask-ui', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $opt  = get_option( self::OPTION_KEY, [] );
        $sb   = $opt['ui']['start_button'] ?? [];
        $gate = $opt['ui']['gate'] ?? [];
        $tp   = $opt['ui']['task_popup'] ?? [];
        $tp_show_on = $tp['show_on'] ?? [ 'flow_start' ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MySeoTask - UI/UX', 'my-seo-task' ); ?></h1>
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cài đặt UI/UX.', 'my-seo-task' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'myseotask_ui_nonce' ); ?>
                <input type="hidden" name="action" value="myseotask_save_ui" />

                <h2><?php esc_html_e( 'Nút bắt đầu', 'my-seo-task' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( 'Text hiển thị', 'my-seo-task' ); ?></th>
                        <td><input type="text" name="start_button_text" class="regular-text" value="<?php echo esc_attr( $sb['text'] ?? 'Bắt đầu nhiệm vụ' ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'DOM ID', 'my-seo-task' ); ?></th>
                        <td><input type="text" name="start_button_dom_id" class="regular-text" value="<?php echo esc_attr( $sb['dom_id'] ?? '' ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Ngưỡng cuộn (0-1)', 'my-seo-task' ); ?></th>
                        <td><input type="number" step="0.01" min="0" max="1" name="start_button_scroll_threshold" value="<?php echo esc_attr( $sb['scroll_threshold'] ?? 0.5 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Độ trễ hiển thị (ms)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="start_button_delay_ms" value="<?php echo esc_attr( $sb['delay_ms'] ?? 1000 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Chỉ hiện khi đủ điều kiện', 'my-seo-task' ); ?></th>
                        <td><label><input type="checkbox" name="start_button_only_if_eligible" value="1" <?php checked( ! empty( $sb['only_if_eligible'] ) ); ?> /> <?php esc_html_e( 'Ẩn nút nếu trang không đủ điều kiện', 'my-seo-task' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Số lần hiển thị mỗi phiên', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="start_button_max_show" value="<?php echo esc_attr( $sb['max_show_per_session'] ?? 3 ); ?>" /></td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Ngưỡng Gate (điều kiện hiển thị điều hướng)', 'my-seo-task' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( 'Bật Gate', 'my-seo-task' ); ?></th>
                        <td><label><input type="checkbox" name="gate_enabled" value="1" <?php checked( ! empty( $gate['enabled'] ) ); ?> /> <?php esc_html_e( 'Yêu cầu tương tác trước khi hiện navigator', 'my-seo-task' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Thời gian tối thiểu (giây)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="gate_min_seconds" value="<?php echo esc_attr( $gate['min_seconds'] ?? 18 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Số tương tác tối thiểu', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="gate_min_interactions" value="<?php echo esc_attr( $gate['min_interactions'] ?? 6 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Scroll tối thiểu (px)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="gate_min_scroll_px" value="<?php echo esc_attr( $gate['min_scroll_px'] ?? 600 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Độ sâu tối thiểu (%)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" max="100" name="gate_min_depth_percent" value="<?php echo esc_attr( $gate['min_depth_percent'] ?? 12 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Khoảng dừng tối thiểu (ms)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="gate_min_pause_ms" value="<?php echo esc_attr( $gate['min_pause_ms'] ?? 2800 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Chế độ chống cuộn nhanh', 'my-seo-task' ); ?></th>
                        <td>
                            <select name="gate_anti_fast_scroll_mode">
                                <?php
                                $modes = [ 'alert' => 'Cảnh báo', 'none' => 'Tắt', 'strict' => 'Nghiêm ngặt' ];
                                $current = $gate['anti_fast_scroll_mode'] ?? 'alert';
                                foreach ( $modes as $val => $label ) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr( $val ),
                                        selected( $current, $val, false ),
                                        esc_html( $label )
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Task Popup (hướng dẫn nhanh)', 'my-seo-task' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( 'Bật popup', 'my-seo-task' ); ?></th>
                        <td><label><input type="checkbox" name="task_popup_enabled" value="1" <?php checked( ! empty( $tp['enabled'] ) ); ?> /> <?php esc_html_e( 'Hiển thị popup hướng dẫn', 'my-seo-task' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Tự đóng sau (ms)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="500" name="task_popup_auto_close_ms" value="<?php echo esc_attr( $tp['auto_close_ms'] ?? 3000 ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Nút đóng', 'my-seo-task' ); ?></th>
                        <td><label><input type="checkbox" name="task_popup_show_close_button" value="1" <?php checked( ! empty( $tp['show_close_button'] ) ); ?> /> <?php esc_html_e( 'Hiện nút đóng trên popup', 'my-seo-task' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Hiện khi', 'my-seo-task' ); ?></th>
                        <td>
                            <label><input type="checkbox" name="task_popup_show_on[]" value="flow_start" <?php checked( in_array( 'flow_start', $tp_show_on, true ) ); ?> /> <?php esc_html_e( 'Bắt đầu flow', 'my-seo-task' ); ?></label><br/>
                            <label><input type="checkbox" name="task_popup_show_on[]" value="task_change" <?php checked( in_array( 'task_change', $tp_show_on, true ) ); ?> /> <?php esc_html_e( 'Đổi nhiệm vụ', 'my-seo-task' ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Chỉ một lần mỗi phiên', 'my-seo-task' ); ?></th>
                        <td><label><input type="checkbox" name="task_popup_once_per_session" value="1" <?php checked( ! empty( $tp['once_per_session'] ) ); ?> /> <?php esc_html_e( 'Không lặp lại trong cùng session', 'my-seo-task' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Cooldown (phút)', 'my-seo-task' ); ?></th>
                        <td><input type="number" min="0" name="task_popup_cooldown_minutes" value="<?php echo esc_attr( $tp['cooldown_minutes'] ?? 0 ); ?>" /></td>
                    </tr>
                </table>

                <?php submit_button( __( 'Lưu UI/UX', 'my-seo-task' ) ); ?>
            </form>
        </div>
        <?php
    }
}
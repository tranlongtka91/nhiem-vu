<?php
namespace MySeoTask\Admin\Pages;

use MySeoTask\Admin\Pages\TasksPage;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GuidesPage {

    const OPTION_KEY = 'myseotask_task_guides';

    public static function register_hooks() {
        add_action( 'admin_post_myseotask_save_guides', [ __CLASS__, 'handle_save' ] );
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Không có quyền', 'my-seo-task' ) );
        }
        check_admin_referer( 'myseotask_guides_nonce' );

        $guides = [];

        // Ghi per-task
        $input_task = $_POST['task_guide'] ?? [];
        if ( is_array( $input_task ) ) {
            foreach ( $input_task as $taskId => $content ) {
                $taskId = sanitize_text_field( $taskId );
                if ( $taskId === '' ) continue;
                $guides[ $taskId ] = wp_kses_post( $content );
            }
        }

        // Ghi per-type (key: type::<type>)
        $input_type = $_POST['task_guide_type'] ?? [];
        if ( is_array( $input_type ) ) {
            foreach ( $input_type as $type => $content ) {
                $type = sanitize_text_field( $type );
                if ( $type === '' ) continue;
                $guides[ 'type::' . $type ] = wp_kses_post( $content );
            }
        }

        update_option( self::OPTION_KEY, $guides );

        wp_safe_redirect( add_query_arg( [ 'page' => 'myseotask-guides', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Nạp scripts editor
        wp_enqueue_editor();

        $guides = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $guides ) ) $guides = [];

        $tasks = TasksPage::tasks_catalog();

        // Gom type duy nhất
        $types = [];
        foreach ( $tasks as $t ) {
            $types[ $t['type'] ] = true;
        }
        ksort( $types );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MySeoTask - Hướng dẫn nhiệm vụ', 'my-seo-task' ); ?></h1>
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu hướng dẫn.', 'my-seo-task' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'myseotask_guides_nonce' ); ?>
                <input type="hidden" name="action" value="myseotask_save_guides" />

                <h2><?php esc_html_e( 'Hướng dẫn theo loại nhiệm vụ (dùng chung cho tất cả task cùng loại)', 'my-seo-task' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Điền một lần cho mỗi loại; các task cùng loại sẽ dùng nội dung này nếu không có hướng dẫn riêng.', 'my-seo-task' ); ?></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 220px;"><?php esc_html_e( 'Loại nhiệm vụ', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Nội dung hướng dẫn (trình soạn thảo)', 'my-seo-task' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $types as $type => $_ ) :
                            $content = $guides[ 'type::' . $type ] ?? '';
                            $editor_id = 'task_guide_type_' . sanitize_html_class( $type );
                            ob_start();
                            wp_editor(
                                $content,
                                $editor_id,
                                [
                                    'textarea_name' => 'task_guide_type[' . esc_attr( $type ) . ']',
                                    'textarea_rows' => 6,
                                    'media_buttons' => false,
                                    'teeny'         => false,
                                    'quicktags'     => true,
                                ]
                            );
                            $editor_html = ob_get_clean();
                            ?>
                            <tr>
                                <td><code><?php echo esc_html( $type ); ?></code></td>
                                <td><?php echo $editor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e( 'Hướng dẫn theo từng task (tuỳ chọn, ưu tiên hơn hướng dẫn theo loại)', 'my-seo-task' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Nếu để trống, sẽ dùng hướng dẫn chung theo loại nhiệm vụ.', 'my-seo-task' ); ?></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php esc_html_e( 'Mã nhiệm vụ', 'my-seo-task' ); ?></th>
                            <th style="width: 220px;"><?php esc_html_e( 'Nhãn', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Nội dung hướng dẫn (trình soạn thảo)', 'my-seo-task' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tasks as $t ) :
                            $id = $t['id'];
                            $label = $t['label'];
                            $content = $guides[ $id ] ?? '';
                            $editor_id = 'task_guide_' . sanitize_html_class( $id );
                            ob_start();
                            wp_editor(
                                $content,
                                $editor_id,
                                [
                                    'textarea_name' => 'task_guide[' . esc_attr( $id ) . ']',
                                    'textarea_rows' => 4,
                                    'media_buttons' => false,
                                    'teeny'         => false,
                                    'quicktags'     => true,
                                ]
                            );
                            $editor_html = ob_get_clean();
                            ?>
                            <tr>
                                <td><code><?php echo esc_html( $id ); ?></code></td>
                                <td><?php echo esc_html( $label ); ?></td>
                                <td><?php echo $editor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button( __( 'Lưu hướng dẫn', 'my-seo-task' ) ); ?>
            </form>
        </div>
        <?php
    }
}
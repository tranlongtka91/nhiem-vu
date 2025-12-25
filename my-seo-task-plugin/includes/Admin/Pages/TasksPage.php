<?php
namespace MySeoTask\Admin\Pages;

use MySeoTask\Core\TasksCatalog;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TasksPage {

    const OPTION_KEY = 'myseotask_tasks_state';

    public static function register_hooks() {
        add_action( 'admin_post_myseotask_save_tasks', [ __CLASS__, 'handle_save' ] );
    }

    /** Backward-compatible helper used by other pages (Guides/Analytics). */
    public static function tasks_catalog() {
        return TasksCatalog::flat();
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bạn không có quyền.', 'my-seo-task' ) );
        }
        check_admin_referer( 'myseotask_tasks_nonce' );

        $enabled_ids = isset( $_POST['tasks'] ) ? (array) $_POST['tasks'] : [];
        $enabled_ids = array_values( array_filter( array_map( 'sanitize_text_field', $enabled_ids ) ) );

        $states = [];
        foreach ( TasksCatalog::flat() as $task ) {
            $id = isset( $task['id'] ) ? (string) $task['id'] : '';
            if ( ! $id ) continue;
            $states[ $id ] = in_array( $id, $enabled_ids, true );
        }

        update_option( self::OPTION_KEY, $states );

        wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=myseotask-tasks' ) ) );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $states = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $states ) ) $states = [];

        $tasks = TasksCatalog::flat();
        usort( $tasks, function( $a, $b ) {
            $pa = isset( $a['page_type'] ) ? (string) $a['page_type'] : '';
            $pb = isset( $b['page_type'] ) ? (string) $b['page_type'] : '';
            if ( $pa === $pb ) {
                return strcmp( (string) ($a['id'] ?? ''), (string) ($b['id'] ?? '') );
            }
            return strcmp( $pa, $pb );
        } );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MySeoTask - Nhiệm vụ', 'my-seo-task' ); ?></h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Đã lưu danh sách nhiệm vụ.', 'my-seo-task' ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'myseotask_tasks_nonce' ); ?>
                <input type="hidden" name="action" value="myseotask_save_tasks" />

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width:110px;"><?php esc_html_e( 'Bật', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Task ID', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Nhãn', 'my-seo-task' ); ?></th>
                            <th><?php esc_html_e( 'Page type', 'my-seo-task' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tasks as $task ) :
                            $id      = (string) ( $task['id'] ?? '' );
                            $enabled = array_key_exists( $id, $states ) ? (bool) $states[ $id ] : true;
                            ?>
                            <tr>
                                <td>
                                    <label>
                                        <input type="checkbox" name="tasks[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $enabled ); ?> />
                                        <?php echo $enabled ? esc_html__( 'Bật', 'my-seo-task' ) : esc_html__( 'Tắt', 'my-seo-task' ); ?>
                                    </label>
                                </td>
                                <td><code><?php echo esc_html( $id ); ?></code></td>
                                <td><?php echo esc_html( (string) ( $task['type'] ?? '' ) ); ?></td>
                                <td><?php echo esc_html( (string) ( $task['label'] ?? $task['title'] ?? '' ) ); ?></td>
                                <td><?php echo esc_html( (string) ( $task['page_type'] ?? '' ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button( __( 'Lưu nhiệm vụ', 'my-seo-task' ) ); ?>
            </form>
        </div>
        <?php
    }
}

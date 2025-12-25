<?php
namespace MySeoTask\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical task catalog for both Admin and Front-end.
 * Single source of truth to avoid drift between PHP and JS.
 */
class TasksCatalog {

    /**
     * Registry grouped by page_type (matching client pageType values).
     *
     * Each task includes:
     * - id (string)
     * - type (string)
     * - title (string)
     * - label (string) short admin label
     * - config (array) validator config
     */
    public static function registry() {
        return [
            'generic' => [
                [ 'id' => 'gen_collect_diamond_1', 'type' => 'collect_diamond', 'label' => 'Nhặt kim cương', 'title' => 'Thu thập 2–3 viên kim cương trong nội dung', 'config' => [ 'minDiamonds' => 2, 'maxDiamonds' => 3 ] ],
                [ 'id' => 'gen_click_internal', 'type' => 'click_internal_link', 'label' => 'Nhấp link nội bộ', 'title' => 'Click vào 1 link nội bộ bất kỳ', 'config' => [ 'important' => false ] ],
                [ 'id' => 'gen_click_content_image', 'type' => 'click_content_image', 'label' => 'Nhấp ảnh nội dung', 'title' => 'Click vào 1 ảnh trong nội dung', 'config' => [ 'important' => false ] ],
                [ 'id' => 'gen_click_content_image_find_diamond', 'type' => 'click_content_image_find_diamond', 'label' => 'Nhấp ảnh & tìm kim cương', 'title' => 'Click vào 1 ảnh và tìm kim cương trên ảnh', 'config' => [ 'important' => false ] ],
            ],
            'post' => [
                [ 'id' => 'post_collect_diamond', 'type' => 'collect_diamond', 'label' => 'Nhặt kim cương', 'title' => 'Thu thập 2–3 viên kim cương trong bài viết', 'config' => [ 'minDiamonds' => 2, 'maxDiamonds' => 3 ] ],
                [ 'id' => 'post_click_related', 'type' => 'click_internal_link', 'label' => 'Nhấp bài liên quan', 'title' => 'Click vào 1 bài viết liên quan trong trang', 'config' => [ 'important' => true ] ],
                [ 'id' => 'post_click_content_image', 'type' => 'click_content_image', 'label' => 'Nhấp ảnh nội dung', 'title' => 'Click vào 1 ảnh trong bài viết', 'config' => [ 'important' => false ] ],
                [ 'id' => 'post_click_content_image_find_diamond', 'type' => 'click_content_image_find_diamond', 'label' => 'Nhấp ảnh & tìm kim cương', 'title' => 'Click vào 1 ảnh và tìm kim cương trên ảnh', 'config' => [ 'important' => false ] ],
            ],
            'product' => [
                [ 'id' => 'prod_collect_diamond', 'type' => 'collect_diamond', 'label' => 'Nhặt kim cương', 'title' => 'Thu thập 2–3 viên kim cương gần vùng mô tả sản phẩm', 'config' => [ 'minDiamonds' => 2, 'maxDiamonds' => 3 ] ],
                [ 'id' => 'prod_click_internal', 'type' => 'click_internal_link', 'label' => 'Nhấp sp/bài liên quan', 'title' => 'Click vào 1 sản phẩm/bài viết liên quan', 'config' => [ 'important' => true ] ],
                [ 'id' => 'prod_click_content_image', 'type' => 'click_content_image', 'label' => 'Nhấp ảnh nội dung', 'title' => 'Click vào 1 ảnh trong nội dung sản phẩm', 'config' => [ 'important' => false ] ],
                [ 'id' => 'prod_click_content_image_find_diamond', 'type' => 'click_content_image_find_diamond', 'label' => 'Nhấp ảnh & tìm kim cương', 'title' => 'Click vào 1 ảnh và tìm kim cương trên ảnh', 'config' => [ 'important' => false ] ],
                [ 'id' => 'prod_click_add_to_cart', 'type' => 'click_add_to_cart', 'label' => 'Thêm vào giỏ', 'title' => 'Thêm sản phẩm vào giỏ hàng', 'config' => [ 'important' => true ] ],
            ],
            'category' => [
                [ 'id' => 'cat_collect_diamond', 'type' => 'collect_diamond', 'label' => 'Nhặt kim cương', 'title' => 'Thu thập 2–3 viên kim cương trong danh sách', 'config' => [ 'minDiamonds' => 2, 'maxDiamonds' => 3 ] ],
                [ 'id' => 'cat_click_content_image', 'type' => 'click_content_image', 'label' => 'Nhấp ảnh nội dung', 'title' => 'Click vào 1 ảnh trong nội dung trang danh mục', 'config' => [ 'important' => false ] ],
                [ 'id' => 'cat_click_content_image_find_diamond', 'type' => 'click_content_image_find_diamond', 'label' => 'Nhấp ảnh & tìm kim cương', 'title' => 'Click vào 1 ảnh và tìm kim cương trên ảnh', 'config' => [ 'important' => false ] ],
                [ 'id' => 'cat_click_pagination', 'type' => 'click_pagination', 'label' => 'Nhấp phân trang', 'title' => 'Click nút phân trang trong danh mục sản phẩm', 'config' => [ 'important' => false ] ],
                [ 'id' => 'cat_click_add_to_cart', 'type' => 'click_add_to_cart', 'label' => 'Thêm vào giỏ', 'title' => 'Thêm sản phẩm vào giỏ hàng từ danh mục', 'config' => [ 'important' => true ] ],
            ],
            'search' => [
                [ 'id' => 'search_click_result', 'type' => 'click_internal_link', 'label' => 'Nhấp kết quả', 'title' => 'Click vào 1 kết quả tìm kiếm', 'config' => [ 'important' => true ] ],
            ],
            'cart' => [
                [ 'id' => 'cart_scroll_50', 'type' => 'scroll_to_percent', 'label' => 'Cuộn xem giỏ', 'title' => 'Cuộn xuống để xem hết danh sách trong giỏ', 'config' => [ 'percent' => 50 ] ],
            ],
            'checkout' => [
                [ 'id' => 'chk_scroll_50', 'type' => 'scroll_to_percent', 'label' => 'Cuộn kiểm tra', 'title' => 'Cuộn xuống để kiểm tra thông tin đơn hàng', 'config' => [ 'percent' => 50 ] ],
            ],
        ];
    }

    /** Flatten list for Admin tables. */
    public static function flat() {
        $out = [];
        foreach ( self::registry() as $page_type => $tasks ) {
            foreach ( (array) $tasks as $t ) {
                $t['page_type'] = $page_type;
                $out[] = $t;
            }
        }
        return $out;
    }

    /** Map id => task */
    public static function by_id() {
        $map = [];
        foreach ( self::flat() as $t ) {
            if ( ! empty( $t['id'] ) ) {
                $map[ (string) $t['id'] ] = $t;
            }
        }
        return $map;
    }
}

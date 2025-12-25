(function () {
    if (!window.MySeoTask) window.MySeoTask = {};

    function isPlainObject(x) {
        return x && typeof x === 'object' && !Array.isArray(x);
    }

    function normalizeTask(t) {
        if (!t || typeof t !== 'object') return null;
        if (!t.id || !t.type || !t.title) return null;

        const task = {
            id: String(t.id),
            type: String(t.type),
            title: String(t.title),
            config: isPlainObject(t.config) ? t.config : {},
        };

        if (t.label) task.label = String(t.label);
        if (t.page_type) task.page_type = String(t.page_type);

        return task;
    }

    function normalizeRegistry(reg) {
        if (!isPlainObject(reg)) return null;
        const out = {};
        Object.keys(reg).forEach((k) => {
            const arr = Array.isArray(reg[k]) ? reg[k] : [];
            const norm = arr.map(normalizeTask).filter(Boolean);
            if (norm.length) out[k] = norm;
        });
        return Object.keys(out).length ? out : null;
    }

    function getRegistryFromConfig() {
        try {
            const cfg = window.__MYSEOTASK_CONFIG__ || {};
            const reg = cfg.tasks_registry || cfg.tasksRegistry || cfg.taskRegistry;
            return normalizeRegistry(reg);
        } catch (e) {
            return null;
        }
    }

    // Fallback registry: should rarely be used because PHP injects tasks_registry.
    const FALLBACK_REGISTRY = {
        generic: [
            { id: 'gen_collect_diamond_1', type: 'collect_diamond', config: { minDiamonds: 2, maxDiamonds: 3 }, title: 'Thu thập 2–3 viên kim cương trong nội dung' },
            { id: 'gen_click_internal', type: 'click_internal_link', config: { important: false }, title: 'Click vào 1 link nội bộ bất kỳ' },
            { id: 'gen_click_content_image', type: 'click_content_image', config: { important: false }, title: 'Click vào 1 ảnh trong nội dung' },
            { id: 'gen_click_content_image_find_diamond', type: 'click_content_image_find_diamond', config: { important: false }, title: 'Click vào 1 ảnh và tìm kim cương trên ảnh' },
        ],
        post: [
            { id: 'post_collect_diamond', type: 'collect_diamond', config: { minDiamonds: 2, maxDiamonds: 3 }, title: 'Thu thập 2–3 viên kim cương trong bài viết' },
            { id: 'post_click_related', type: 'click_internal_link', config: { important: true }, title: 'Click vào 1 bài viết liên quan trong trang' },
            { id: 'post_click_content_image', type: 'click_content_image', config: { important: false }, title: 'Click vào 1 ảnh trong bài viết' },
            { id: 'post_click_content_image_find_diamond', type: 'click_content_image_find_diamond', config: { important: false }, title: 'Click vào 1 ảnh và tìm kim cương trên ảnh' },
        ],
        product: [
            { id: 'prod_collect_diamond', type: 'collect_diamond', config: { minDiamonds: 2, maxDiamonds: 3 }, title: 'Thu thập 2–3 viên kim cương gần vùng mô tả sản phẩm' },
            { id: 'prod_click_internal', type: 'click_internal_link', config: { important: true }, title: 'Click vào 1 sản phẩm/bài viết liên quan' },
            { id: 'prod_click_content_image', type: 'click_content_image', config: { important: false }, title: 'Click vào 1 ảnh trong nội dung sản phẩm' },
            { id: 'prod_click_content_image_find_diamond', type: 'click_content_image_find_diamond', config: { important: false }, title: 'Click vào 1 ảnh và tìm kim cương trên ảnh' },
            { id: 'prod_click_add_to_cart', type: 'click_add_to_cart', config: { important: true }, title: 'Thêm sản phẩm vào giỏ hàng' },
        ],
        category: [
            { id: 'cat_collect_diamond', type: 'collect_diamond', config: { minDiamonds: 2, maxDiamonds: 3 }, title: 'Thu thập 2–3 viên kim cương trong danh sách' },
            { id: 'cat_click_content_image', type: 'click_content_image', config: { important: false }, title: 'Click vào 1 ảnh trong nội dung trang danh mục' },
            { id: 'cat_click_content_image_find_diamond', type: 'click_content_image_find_diamond', config: { important: false }, title: 'Click vào 1 ảnh và tìm kim cương trên ảnh' },
            { id: 'cat_click_pagination', type: 'click_pagination', config: { important: false }, title: 'Click nút phân trang trong danh mục sản phẩm' },
            { id: 'cat_click_add_to_cart', type: 'click_add_to_cart', config: { important: true }, title: 'Thêm sản phẩm vào giỏ hàng từ danh mục' },
        ],
        search: [
            { id: 'search_click_result', type: 'click_internal_link', config: { important: true }, title: 'Click vào 1 kết quả tìm kiếm' },
        ],
        cart: [
            { id: 'cart_scroll_50', type: 'scroll_to_percent', config: { percent: 50 }, title: 'Cuộn xuống để xem hết danh sách trong giỏ' },
        ],
        checkout: [
            { id: 'chk_scroll_50', type: 'scroll_to_percent', config: { percent: 50 }, title: 'Cuộn xuống để kiểm tra thông tin đơn hàng' },
        ],
    };

    const REGISTRY = getRegistryFromConfig() || FALLBACK_REGISTRY;

    function getTasksForPageType(pageType) {
        if (pageType && REGISTRY[pageType] && REGISTRY[pageType].length > 0) return REGISTRY[pageType];
        return REGISTRY.generic || [];
    }

    window.MySeoTask.TaskRegistry = { getTasksForPageType, REGISTRY };
})();

(function () {
    if (!window.MySeoTask) window.MySeoTask = {};

    const cfgRoot = window.__MYSEOTASK_CONFIG__ || {};
    const uiCfg = (cfgRoot.ui || {}).task_popup || {};
    const guides = cfgRoot.task_guides || {}; // map taskId => html; type-level: key 'type::<type>'

    const ENABLED = uiCfg.enabled !== false;
    const AUTO_CLOSE_MS = (typeof uiCfg.auto_close_ms === 'number') ? Math.max(500, uiCfg.auto_close_ms) : 3000;
    const SHOW_CLOSE = uiCfg.show_close_button !== false; // default true
    const SHOW_ON = Array.isArray(uiCfg.show_on) && uiCfg.show_on.length ? uiCfg.show_on : ['flow_start'];
    const ONCE_PER_SESSION = uiCfg.once_per_session !== false; // default true
    const COOLDOWN_MS = (typeof uiCfg.cooldown_minutes === 'number' ? uiCfg.cooldown_minutes : 0) * 60 * 1000;

    const SESSION_KEY = 'MySeoTask_TaskPopup_Shown';
    const LAST_SHOWN_KEY = 'MySeoTask_TaskPopup_LastShown';

    let currentPopup = null;
    let autoTimer = 0;

    function getSessionId() {
        const SM = window.MySeoTask.SessionManager;
        return SM && typeof SM.getSessionId === 'function' ? SM.getSessionId() : null;
    }
    function getPageType() { return window.MySeoTask.pageType || 'generic'; }

    function sendUiEvent(eventType, payload) {
        try {
            const body = Object.assign({}, payload || {}, { event_type: eventType });
            fetch('/wp-json/myseotask/v1/events', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            }).catch(() => {});
        } catch (e) {
            // silent
        }
    }

    function now() { return Date.now(); }

    function alreadyShownThisSession() {
        try { return sessionStorage.getItem(SESSION_KEY) === '1'; } catch (e) { return false; }
    }
    function markShown() {
        try { sessionStorage.setItem(SESSION_KEY, '1'); } catch (e) {}
        try { sessionStorage.setItem(LAST_SHOWN_KEY, String(now())); } catch (e) {}
    }
    function withinCooldown() {
        if (COOLDOWN_MS <= 0) return false;
        try {
            const last = parseInt(sessionStorage.getItem(LAST_SHOWN_KEY) || '0', 10);
            if (!last) return false;
            return (now() - last) < COOLDOWN_MS;
        } catch (e) { return false; }
    }

    function pickGuideHtml(task) {
        if (!task || !task.id) return '';
        if (guides[task.id]) return String(guides[task.id]); // per-task
        const typeKey = 'type::' + (task.type || '');
        if (guides[typeKey]) return String(guides[typeKey]); // per-type
        return '';
    }

    function shouldShow(reason, task) {
        if (!ENABLED) return false;
        if (!task || !task.id) return false;
        const html = pickGuideHtml(task);
        if (!html) return false; // không có nội dung -> không show
        if (SHOW_ON.indexOf(reason) === -1) return false;
        if (ONCE_PER_SESSION && alreadyShownThisSession()) return false;
        if (withinCooldown()) return false;
        return true;
    }

    function closePopup() {
        if (autoTimer) {
            clearTimeout(autoTimer);
            autoTimer = 0;
        }
        if (currentPopup && currentPopup.parentNode) {
            // UI event: popup_close
            sendUiEvent('ui_popup_close', {
                session_id: getSessionId(),
                page_type: getPageType(),
                task_id: currentPopup.__taskId || null,
            });
            currentPopup.parentNode.removeChild(currentPopup);
        }
        currentPopup = null;
    }

    function buildPopup(htmlContent, taskId) {
        const wrap = document.createElement('div');
        wrap.className = 'myseotask-task-popup';
        wrap.__taskId = taskId || null;
        wrap.innerHTML = `
<div class="myseotask-task-popup-backdrop"></div>
<div class="myseotask-task-popup-card">
  <div class="myseotask-task-popup-title">Hướng dẫn nhiệm vụ</div>
  <div class="myseotask-task-popup-body">${htmlContent}</div>
  <div class="myseotask-task-popup-actions">
    ${SHOW_CLOSE ? '<button type="button" class="myseotask-task-popup-close">Đã hiểu</button>' : ''}
  </div>
</div>
        `.trim();

        const styleId = 'myseotask-task-popup-style';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
.myseotask-task-popup { position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; pointer-events: none; }
.myseotask-task-popup-backdrop { position:absolute; inset:0; background: rgba(0,0,0,0.4); }
.myseotask-task-popup-card {
  position: relative; z-index:1; pointer-events:auto;
  width: min(92vw, 460px); background:#fff; border-radius: 14px;
  box-shadow: 0 18px 38px rgba(0,0,0,0.18);
  padding: 18px 16px 14px; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
.myseotask-task-popup-title { font-size: 17px; font-weight: 800; margin: 0 0 8px; }
.myseotask-task-popup-body { font-size: 14px; color: #111; line-height: 1.5; }
.myseotask-task-popup-body p { margin: 0 0 8px; }
.myseotask-task-popup-actions { margin-top: 12px; text-align: right; }
.myseotask-task-popup-close {
  border: none; border-radius: 10px; padding: 10px 14px;
  background: #0ea5e9; color: #fff; font-weight: 700; cursor: pointer;
}
.myseotask-task-popup-close:hover { background: #0284c7; }
            `.trim();
            document.head.appendChild(style);
        }

        document.body.appendChild(wrap);
        return wrap;
    }

    function show(reason, task) {
        if (!shouldShow(reason, task)) return;

        // Đảm bảo không bị chồng popup: đóng cái trước nếu có
        closePopup();

        let html = pickGuideHtml(task);
        // loại bỏ script tag phòng ngừa
        html = html.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');

        currentPopup = buildPopup(html, task.id);

        const closeBtn = currentPopup.querySelector('.myseotask-task-popup-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closePopup, { once: true });
        }

        // UI event: popup_show
        sendUiEvent('ui_popup_show', {
            session_id: getSessionId(),
            page_type: getPageType(),
            task_id: task.id || null,
        });

        markShown();
        autoTimer = setTimeout(closePopup, AUTO_CLOSE_MS);
    }

    window.MySeoTask.TaskGuidePopup = { show, close: closePopup };
})();
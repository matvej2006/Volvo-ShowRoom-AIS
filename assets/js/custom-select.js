/**
 * Кастомные выпадающие списки для всех <select>.
 */
(function () {
    'use strict';

    if (typeof document === 'undefined') {
        return;
    }

    var openCloser = null;
    var uid = 0;
    var requiredByForm = new WeakMap();
    var formListeners = new WeakSet();

    function enhance(select) {
        if (!select || select.dataset.csReady === '1' || select.multiple) {
            return;
        }
        if (select.closest && select.closest('.cs')) {
            return;
        }

        select.dataset.csReady = '1';

        var ctx = 'cs';
        if (select.classList.contains('a-select')) {
            ctx += ' cs--admin';
        } else if (select.closest && select.closest('.field')) {
            ctx += ' cs--field';
        }

        var wrap = document.createElement('div');
        wrap.className = ctx;

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var wasRequired = select.required;
        select.classList.add('cs-native');
        select.setAttribute('tabindex', '-1');
        if (wasRequired) {
            select.required = false;
        }

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cs-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (wasRequired) {
            trigger.setAttribute('aria-required', 'true');
        }

        var valueEl = document.createElement('span');
        valueEl.className = 'cs-value';

        var arrow = document.createElement('span');
        arrow.className = 'cs-arrow';
        arrow.setAttribute('aria-hidden', 'true');

        trigger.appendChild(valueEl);
        trigger.appendChild(arrow);
        wrap.appendChild(trigger);

        if (select.id) {
            var triggerId = select.id + '-trigger';
            trigger.id = triggerId;

            var labels = document.getElementsByTagName('label');
            for (var li = 0; li < labels.length; li++) {
                if (labels[li].htmlFor === select.id) {
                    labels[li].htmlFor = triggerId;
                }
            }
        }

        var menu = document.createElement('div');
        menu.className = 'cs-menu';
        menu.setAttribute('role', 'listbox');
        var listId = 'cs-menu-' + (++uid);
        menu.id = listId;
        trigger.setAttribute('aria-controls', listId);
        document.body.appendChild(menu);

        var items = [];
        var activeIndex = -1;
        var isOpen = false;

        function build() {
            menu.innerHTML = '';
            items = [];

            for (var i = 0; i < select.options.length; i++) {
                var opt = select.options[i];
                if (opt.hidden) {
                    continue;
                }

                var el = document.createElement('div');
                el.className = 'cs-option';
                el.setAttribute('role', 'option');
                el.id = listId + '-o' + i;
                el.dataset.index = String(i);

                if (opt.disabled) {
                    el.classList.add('is-disabled');
                    el.setAttribute('aria-disabled', 'true');
                }

                var label = document.createElement('span');
                label.className = 'cs-option-label';
                label.textContent = opt.textContent.trim();

                var check = document.createElement('span');
                check.className = 'cs-check';
                check.textContent = '\u2713';

                el.appendChild(label);
                el.appendChild(check);
                menu.appendChild(el);

                items.push({ el: el, index: i, disabled: opt.disabled });
            }
        }

        function syncTrigger() {
            var opt = select.options[select.selectedIndex];
            var text = opt ? opt.textContent.trim() : '';
            valueEl.textContent = text;
            wrap.classList.toggle('is-placeholder', !opt || opt.value === '' || text === '');
            trigger.classList.remove('cs-invalid');

            for (var i = 0; i < items.length; i++) {
                if (items[i].index === select.selectedIndex) {
                    items[i].el.setAttribute('aria-selected', 'true');
                } else {
                    items[i].el.removeAttribute('aria-selected');
                }
            }
        }

        function setActive(i) {
            if (!items[i]) {
                return;
            }
            activeIndex = i;
            for (var k = 0; k < items.length; k++) {
                items[k].el.classList.toggle('is-active', k === i);
            }
            trigger.setAttribute('aria-activedescendant', items[i].el.id);

            var el = items[i].el;
            var er = el.getBoundingClientRect();
            var mr = menu.getBoundingClientRect();
            if (er.top < mr.top) {
                menu.scrollTop -= (mr.top - er.top);
            } else if (er.bottom > mr.bottom) {
                menu.scrollTop += (er.bottom - mr.bottom);
            }
        }

        function firstEnabled(from, delta) {
            if (items.length === 0) {
                return -1;
            }
            var i = from;
            for (var n = 0; n < items.length; n++) {
                i = (i + delta + items.length) % items.length;
                if (!items[i].disabled) {
                    return i;
                }
            }
            return -1;
        }

        function startIndex() {
            for (var i = 0; i < items.length; i++) {
                if (items[i].index === select.selectedIndex && !items[i].disabled) {
                    return i;
                }
            }
            return firstEnabled(-1, 1);
        }

        function positionMenu() {
            if (!isOpen) {
                return;
            }
            var r = trigger.getBoundingClientRect();
            var gap = 6;

            menu.style.width = r.width + 'px';
            menu.style.left = r.left + 'px';
            menu.style.top = '-9999px';

            var mh = menu.offsetHeight;
            var below = window.innerHeight - r.bottom;
            var above = r.top;
            var top;

            if (below < mh + gap && above > below) {
                top = r.top - mh - gap;
            } else {
                top = r.bottom + gap;
            }
            if (top < 8) {
                top = 8;
            }
            menu.style.top = top + 'px';
        }

        function onDocDown(e) {
            if (wrap.contains(e.target) || menu.contains(e.target)) {
                return;
            }
            closeMenu();
        }

        function openMenu() {
            if (isOpen || select.disabled) {
                return;
            }
            if (openCloser) {
                openCloser();
            }
            isOpen = true;
            openCloser = closeMenu;

            trigger.setAttribute('aria-expanded', 'true');
            menu.classList.add('cs-open');
            positionMenu();

            var start = startIndex();
            if (start >= 0) {
                setActive(start);
            }

            window.addEventListener('scroll', positionMenu, true);
            window.addEventListener('resize', positionMenu);
            document.addEventListener('mousedown', onDocDown, true);
        }

        function closeMenu() {
            if (!isOpen) {
                return;
            }
            isOpen = false;
            if (openCloser === closeMenu) {
                openCloser = null;
            }
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-activedescendant');
            menu.classList.remove('cs-open');
            activeIndex = -1;

            window.removeEventListener('scroll', positionMenu, true);
            window.removeEventListener('resize', positionMenu);
            document.removeEventListener('mousedown', onDocDown, true);
        }

        function choose(i) {
            if (!items[i] || items[i].disabled) {
                return;
            }
            select.selectedIndex = items[i].index;
            syncTrigger();
            closeMenu();
            trigger.focus();

            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        trigger.addEventListener('keydown', function (e) {
            var key = e.key;
            if (select.disabled) {
                return;
            }

            if (!isOpen) {
                if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Enter' || key === ' ' || key === 'Spacebar') {
                    e.preventDefault();
                    openMenu();
                    if (key === 'ArrowUp') {
                        var last = firstEnabled(0, -1);
                        if (last >= 0) {
                            setActive(last);
                        }
                    }
                }
                return;
            }

            if (key === 'ArrowDown') {
                e.preventDefault();
                var down = firstEnabled(activeIndex, 1);
                if (down >= 0) { setActive(down); }
            } else if (key === 'ArrowUp') {
                e.preventDefault();
                var up = firstEnabled(activeIndex, -1);
                if (up >= 0) { setActive(up); }
            } else if (key === 'Home') {
                e.preventDefault();
                setActive(firstEnabled(-1, 1));
            } else if (key === 'End') {
                e.preventDefault();
                setActive(firstEnabled(0, -1));
            } else if (key === 'Enter' || key === ' ' || key === 'Spacebar') {
                e.preventDefault();
                if (activeIndex >= 0) { choose(activeIndex); }
            } else if (key === 'Escape') {
                e.preventDefault();
                closeMenu();
                trigger.focus();
            } else if (key === 'Tab') {
                closeMenu();
            }
        });

        menu.addEventListener('click', function (e) {
            var el = e.target.closest ? e.target.closest('.cs-option') : null;
            if (!el) {
                return;
            }
            var nativeIndex = Number(el.dataset.index);
            for (var k = 0; k < items.length; k++) {
                if (items[k].index === nativeIndex) {
                    choose(k);
                    break;
                }
            }
        });

        menu.addEventListener('mouseover', function (e) {
            var el = e.target.closest ? e.target.closest('.cs-option') : null;
            if (!el) {
                return;
            }
            var nativeIndex = Number(el.dataset.index);
            for (var k = 0; k < items.length; k++) {
                if (items[k].index === nativeIndex && !items[k].disabled) {
                    setActive(k);
                    break;
                }
            }
        });

        select.addEventListener('change', syncTrigger);

        if (select.form) {
            if (wasRequired) {
                var list = requiredByForm.get(select.form) || [];
                list.push({ select: select, trigger: trigger, open: openMenu });
                requiredByForm.set(select.form, list);
            }

            if (!formListeners.has(select.form)) {
                formListeners.add(select.form);
                select.form.addEventListener('submit', function (e) {
                    var required = requiredByForm.get(select.form) || [];
                    var empty = null;
                    for (var i = 0; i < required.length; i++) {
                        if (required[i].select.value === '') {
                            empty = required[i];
                            break;
                        }
                    }
                    if (empty) {
                        e.preventDefault();
                        empty.trigger.classList.add('cs-invalid');
                        empty.trigger.focus();
                        empty.open();
                    }
                });
            }

            select.form.addEventListener('reset', function () {
                window.setTimeout(syncTrigger, 0);
            });
        }

        build();
        syncTrigger();
    }

    function initAll() {
        var selects = document.querySelectorAll('select');
        for (var i = 0; i < selects.length; i++) {
            enhance(selects[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();

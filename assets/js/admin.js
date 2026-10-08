jQuery(function ($) {
    const $table = $('.cs-ahm-table');
    const $count = $('#cs-ahm-count');
    const $toggles = $table.find('.cs-ahm-toggle');
    const cfg = window.csAhm || {};
    const i18n = cfg.i18n || {};

    const L = {
        forAll: i18n.forAll || 'برای همه',
        show: i18n.show || 'نمایش',
        roleWord: i18n.roleWord || 'نقش',
        userWord: i18n.userWord || 'کاربر',
        noUsers: i18n.noUsers || 'کاربری پیدا نشد'
    };

    const panelOf = slug => $table.find('.cs-ahm-roles-row[data-slug="' + slug + '"]');
    const rowOf = slug => $table.find('.cs-ahm-row[data-slug="' + slug + '"]');

    function updateCount() {
        $count.text($toggles.filter(':checked').length);
    }

    function selectedUserIds(slug) {
        return panelOf(slug).find('.cs-ahm-user-chip')
            .map(function () { return String($(this).attr('data-user-id')); })
            .get();
    }

    /**
     * متن وضعیت ستون «وضعیت» — باید دقیقاً با منطق سرور یکی باشه:
     *   خاموش → نمایش | «همه» یا (هیچ نقش و کاربری) → برای همه | وگرنه «N نقش • M کاربر»
     */
    function refreshStatus(slug) {
        const $row = rowOf(slug);
        const $panel = panelOf(slug);
        const $status = $row.find('.cs-ahm-status');

        if (!$row.find('.cs-ahm-toggle').is(':checked')) {
            $status.attr('class', 'cs-ahm-status cs-ahm-status-none').text(L.show);
            return;
        }

        const allChecked = $panel.find('.cs-ahm-role-all-check').is(':checked');
        const nRoles = $panel.find('.cs-ahm-role-check:checked').length;
        const nUsers = $panel.find('.cs-ahm-user-chip').length;

        if (allChecked || (nRoles === 0 && nUsers === 0)) {
            $status.attr('class', 'cs-ahm-status cs-ahm-status-all').text(L.forAll);
            return;
        }

        const parts = [];
        if (nRoles > 0) parts.push(nRoles + ' ' + L.roleWord);
        if (nUsers > 0) parts.push(nUsers + ' ' + L.userWord);
        $status.attr('class', 'cs-ahm-status cs-ahm-status-roles').text(parts.join(' • '));
    }

    /**
     * قفل «همه نقش‌ها»: چک‌باکس نقش‌ها غیرفعال می‌شن (ارسال نمی‌شن و کلید 'all'
     * از خودِ چک‌باکس همه ارسال می‌شه) و بخش کاربرها قفل می‌شه، چون «برای همه»
     * شامل همهٔ کاربرها هم هست.
     */
    function applyAllLock(slug) {
        const $panel = panelOf(slug);
        const allChecked = $panel.find('.cs-ahm-role-all-check').is(':checked');

        $panel.find('.cs-ahm-role-check').prop('disabled', allChecked);
        $panel.find('.cs-ahm-users-section').toggleClass('cs-ahm-locked', allChecked);
        $panel.find('.cs-ahm-user-search-input, .cs-ahm-user-chip-remove, .cs-ahm-user-chip-input')
            .prop('disabled', allChecked);
        if (allChecked) {
            $panel.find('.cs-ahm-user-results').hide();
        }
    }

    /**
     * فعال/غیرفعال کل پنل بر اساس سوییچ ردیف.
     * انتخاب‌ها پاک نمی‌شن؛ فقط غیرفعال می‌شن تا ارسال نشن (و با فعال‌شدن دوباره برگردن).
     */
    function applyRowLock(slug, on) {
        const $panel = panelOf(slug);
        $panel.find('input').prop('disabled', !on);
        if (on) {
            applyAllLock(slug);
        } else {
            $panel.find('.cs-ahm-user-results').hide();
        }
    }

    // ---- دکمه‌های نوار ابزار ----

    $('#cs-ahm-select-all').on('click', function () {
        $toggles.not(':disabled').prop('checked', true).trigger('change');
    });

    $('#cs-ahm-deselect-all').on('click', function () {
        $toggles.not(':disabled').prop('checked', false).trigger('change');
    });

    $('#cs-ahm-expand-roles').on('click', function () {
        $table.find('.cs-ahm-roles-row').show();
        $table.find('.cs-ahm-row').addClass('cs-ahm-expanded');
    });

    $('#cs-ahm-collapse-roles').on('click', function () {
        $table.find('.cs-ahm-roles-row').hide();
        $table.find('.cs-ahm-row').removeClass('cs-ahm-expanded');
        $table.find('.cs-ahm-user-results').hide();
    });

    $table.on('click', '.cs-ahm-expand-single', function () {
        const $btn = $(this);
        const slug = $btn.closest('.cs-ahm-row').data('slug');
        const $row = $btn.closest('.cs-ahm-row');
        const $rolesRow = panelOf(slug);

        $rolesRow.toggle();
        $row.toggleClass('cs-ahm-expanded');
        if (!$rolesRow.is(':visible')) {
            $rolesRow.find('.cs-ahm-user-results').hide();
        }
    });

    // ---- سوییچ اصلی ردیف ----

    $toggles.on('change', function () {
        const $row = $(this).closest('.cs-ahm-row');
        const slug = $row.data('slug');
        const $panel = panelOf(slug);
        const $expandBtn = $row.find('.cs-ahm-expand-single');
        const on = $(this).is(':checked');

        if (on) {
            $expandBtn.prop('disabled', false);
            applyRowLock(slug, true);
        } else {
            $expandBtn.prop('disabled', true);
            $panel.hide();
            $row.removeClass('cs-ahm-expanded');
            applyRowLock(slug, false);
        }

        refreshStatus(slug);
        updateCount();
    });

    // ---- چک‌باکس «همه نقش‌ها» ----

    $table.on('change', '.cs-ahm-role-all-check', function () {
        const slug = $(this).data('slug');
        applyAllLock(slug);
        refreshStatus(slug);
    });

    // ---- چک‌باکس تک‌نقش ----

    $table.on('change', '.cs-ahm-role-check', function () {
        const slug = $(this).data('slug');
        const $panel = panelOf(slug);
        const total = $panel.find('.cs-ahm-role-check').length;
        const checkedCount = $panel.find('.cs-ahm-role-check:checked').length;

        // اگر همهٔ نقش‌ها تیک خوردن، «همه» فعال می‌شه (قفل اعمال می‌شه)
        $panel.find('.cs-ahm-role-all-check').prop('checked', total > 0 && checkedCount === total);
        applyAllLock(slug);
        refreshStatus(slug);
    });

    // ---- جستجوی کاربر (ajax) ----

    const searchSeq = {};

    function renderResults(slug, users) {
        const $results = panelOf(slug).find('.cs-ahm-user-results');
        const selected = selectedUserIds(slug);
        const items = (users || []).filter(u => selected.indexOf(String(u.id)) === -1);

        $results.empty();

        if (!items.length) {
            $results.append($('<div class="cs-ahm-user-result-empty"></div>').text(L.noUsers));
        } else {
            items.forEach(u => {
                const $item = $('<button type="button" class="cs-ahm-user-result"></button>');
                $item.attr('data-id', String(u.id));
                $item.attr('data-name', u.name || '');
                $item.attr('data-login', u.login || '');
                $item.append($('<span class="cs-ahm-user-result-name"></span>').text(u.name || ''));

                const meta = [];
                if (u.login && u.login !== u.name) meta.push('@' + u.login);
                if (u.role) meta.push(u.role);
                if (meta.length) {
                    $item.append($('<span class="cs-ahm-user-result-meta"></span>').text(meta.join(' · ')));
                }
                $results.append($item);
            });
        }

        $results.show();
    }

    function runSearch(slug, q) {
        if (!cfg.ajaxUrl) return;

        searchSeq[slug] = (searchSeq[slug] || 0) + 1;
        const seq = searchSeq[slug];
        const $results = panelOf(slug).find('.cs-ahm-user-results');

        $results.show().addClass('cs-ahm-loading');

        $.getJSON(cfg.ajaxUrl, { action: 'cs_ahm_search_users', nonce: cfg.nonce, q: q || '' })
            .done(function (res) {
                if (searchSeq[slug] !== seq) return; // پاسخ قدیمی‌تر — نادیده بگیر
                $results.removeClass('cs-ahm-loading');
                renderResults(slug, res && res.success ? res.data : []);
            })
            .fail(function () {
                if (searchSeq[slug] !== seq) return;
                $results.removeClass('cs-ahm-loading').hide();
            });
    }

    $table.on('input search focus', '.cs-ahm-user-search-input', function () {
        const $input = $(this);
        const slug = $input.data('slug');
        const q = String($input.val() || '');

        clearTimeout($input.data('cs-ahm-timer'));
        $input.data('cs-ahm-timer', setTimeout(function () {
            runSearch(slug, q);
        }, 300));
    });

    $table.on('click', '.cs-ahm-user-result', function () {
        const $item = $(this);
        const slug = $item.closest('.cs-ahm-user-results').data('slug');

        addUserChip(slug, {
            id: $item.attr('data-id'),
            name: $item.attr('data-name'),
            login: $item.attr('data-login')
        });

        const $input = panelOf(slug).find('.cs-ahm-user-search-input');
        $input.val('');
        panelOf(slug).find('.cs-ahm-user-results').empty().hide();
        refreshStatus(slug);
    });

    function addUserChip(slug, user) {
        if (selectedUserIds(slug).indexOf(String(user.id)) !== -1) return;

        const index = rowOf(slug).data('index');
        const $sel = panelOf(slug).find('.cs-ahm-users-selected');
        const locked = panelOf(slug).find('.cs-ahm-users-section').hasClass('cs-ahm-locked');

        const $chip = $('<div class="cs-ahm-user-chip"></div>').attr('data-user-id', String(user.id));
        $chip.append(
            $('<input type="hidden" class="cs-ahm-user-chip-input">')
                .attr('name', 'cs_ahm_hidden_menus[' + index + '][users][]')
                .val(String(user.id))
                .prop('disabled', locked)
        );

        const $name = $('<span class="cs-ahm-user-chip-name"></span>').text(user.name || '');
        if (user.login) $name.attr('title', user.login);
        $chip.append($name);

        $chip.append(
            $('<button type="button" class="cs-ahm-user-chip-remove"></button>')
                .html('&times;')
                .attr('aria-label', 'حذف کاربر')
                .prop('disabled', locked)
        );

        $sel.append($chip).show();
    }

    $table.on('click', '.cs-ahm-user-chip-remove', function () {
        const slug = $(this).closest('.cs-ahm-roles-row').data('slug');
        const $sel = $(this).closest('.cs-ahm-users-selected');

        $(this).closest('.cs-ahm-user-chip').remove();
        if ($sel.find('.cs-ahm-user-chip').length === 0) $sel.hide();
        refreshStatus(slug);
    });

    // بستن لیست نتایج با کلیک بیرون
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cs-ahm-user-search').length) {
            $table.find('.cs-ahm-user-results').hide();
        }
    });

    // وضعیت اولیهٔ سرور رو بازتولید کن (شمارنده + قفل‌ها)
    $toggles.each(function () {
        const slug = $(this).closest('.cs-ahm-row').data('slug');
        if ($(this).is(':checked')) applyAllLock(slug);
    });

    updateCount();
});

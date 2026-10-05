jQuery(function ($) {
    const $table = $('.cs-ahm-table');
    const $count = $('#cs-ahm-count');
    const $toggles = $table.find('.cs-ahm-toggle');

    function updateCount() {
        const n = $toggles.filter(':checked').length;
        $count.text(n);
    }

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
    });

    $table.on('click', '.cs-ahm-expand-single', function () {
        const $btn = $(this);
        const slug = $btn.closest('.cs-ahm-row').data('slug');
        const $row = $btn.closest('.cs-ahm-row');
        const $rolesRow = $table.find('.cs-ahm-roles-row[data-slug="' + slug + '"]');

        $rolesRow.toggle();
        $row.toggleClass('cs-ahm-expanded');
    });

    $toggles.on('change', function () {
        const $row = $(this).closest('.cs-ahm-row');
        const slug = $row.data('slug');
        const $rolesRow = $table.find('.cs-ahm-roles-row[data-slug="' + slug + '"]');
        const $expandBtn = $row.find('.cs-ahm-expand-single');
        const $status = $row.find('.cs-ahm-status');

        if ($(this).is(':checked')) {
            $expandBtn.prop('disabled', false);

            const anyRole = $rolesRow.find('.cs-ahm-role-check:checked').length;
            if (anyRole === 0) {
                $status.attr('class', 'cs-ahm-status cs-ahm-status-all').text('برای همه');
            } else {
                $status.attr('class', 'cs-ahm-status cs-ahm-status-roles').text(anyRole + ' نقش');
            }
        } else {
            $expandBtn.prop('disabled', true);
            $rolesRow.hide();
            $row.removeClass('cs-ahm-expanded');

            $rolesRow.find('.cs-ahm-role-check, .cs-ahm-role-all-check').prop('checked', false).prop('disabled', false);
            $status.attr('class', 'cs-ahm-status cs-ahm-status-none').text('نمایش');
        }

        updateCount();
    });

    $table.on('change', '.cs-ahm-role-all-check', function () {
        const slug = $(this).data('slug');
        const $panel = $table.find('.cs-ahm-roles-row[data-slug="' + slug + '"]');
        const $roleChecks = $panel.find('.cs-ahm-role-check');
        const $status = $table.find('.cs-ahm-row[data-slug="' + slug + '"] .cs-ahm-status');

        if ($(this).is(':checked')) {
            $roleChecks.prop('checked', true).prop('disabled', true);
            $status.attr('class', 'cs-ahm-status cs-ahm-status-all').text('برای همه');
        } else {
            $roleChecks.prop('checked', false).prop('disabled', false);
            $status.attr('class', 'cs-ahm-status cs-ahm-status-all').text('برای همه');
        }
    });

    $table.on('change', '.cs-ahm-role-check', function () {
        const slug = $(this).data('slug');
        const $panel = $table.find('.cs-ahm-roles-row[data-slug="' + slug + '"]');
        const $status = $table.find('.cs-ahm-row[data-slug="' + slug + '"] .cs-ahm-status');
        const anyRole = $panel.find('.cs-ahm-role-check:checked').length;
        const total = $panel.find('.cs-ahm-role-check').length;
        const $allCheck = $panel.find('.cs-ahm-role-all-check');

        if (anyRole === total && total > 0) {
            $allCheck.prop('checked', true);
        } else {
            $allCheck.prop('checked', false);
        }

        if (anyRole === 0) {
            $status.attr('class', 'cs-ahm-status cs-ahm-status-all').text('برای همه');
        } else {
            $status.attr('class', 'cs-ahm-status cs-ahm-status-roles').text(anyRole + ' نقش');
        }
    });

    updateCount();
});
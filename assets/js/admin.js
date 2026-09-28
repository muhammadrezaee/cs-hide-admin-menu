jQuery(function ($) {
    const $count = $('#cs-ahm-count');
    const $checks = $('.cs-ahm-table input[type="checkbox"]');

    function updateCount() {
        const n = $checks.filter(':checked').length;
        $count.text(n);
    }

    $checks.on('change', updateCount);

    $('#cs-ahm-select-all').on('click', function () {
        $checks.not(':disabled').prop('checked', true).trigger('change');
    });

    $('#cs-ahm-deselect-all').on('click', function () {
        $checks.not(':disabled').prop('checked', false).trigger('change');
    });
});
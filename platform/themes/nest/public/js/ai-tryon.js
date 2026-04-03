(function ($) {
    'use strict';

    function showStatus($status, type, text) {
        $status
            .removeClass('d-none alert alert-success alert-danger alert-warning')
            .addClass('alert');

        if (type === 'success') {
            $status.addClass('alert-success');
        } else if (type === 'warning') {
            $status.addClass('alert-warning');
        } else {
            $status.addClass('alert-danger');
        }

        $status.text(text);
    }

    function renderImages($container, urls) {
        $container.empty();

        (urls || []).forEach(function (url) {
            var $col = $('<div class="col-12 col-md-4"></div>');
            var $img = $('<img class="img-fluid border-radius-10" alt="AI try-on result">');
            $img.attr('src', url);
            $col.append($img);
            $container.append($col);
        });
    }

    $(document).on('click', '.js-ai-tryon-button', function (e) {
        e.preventDefault();

        var url = $(this).data('url');
        var $modal = $('#ai-tryon-modal');
        var $form = $('#ai-tryon-form');

        $form.data('url', url);
        $form[0].reset();
        $('.ai-tryon-results').empty();
        $('.ai-tryon-status').addClass('d-none').text('');

        $modal.modal('show');
    });

    $(document).on('submit', '#ai-tryon-form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var url = $form.data('url');
        var $submit = $('#ai-tryon-submit');
        var $status = $('.ai-tryon-status');
        var $results = $('.ai-tryon-results');

        if (!url) {
            showStatus($status, 'error', 'Missing try-on endpoint.');
            return;
        }

        var formData = new FormData($form[0]);

        $submit.prop('disabled', true);
        showStatus($status, 'warning', 'Generating… this may take up to a minute.');
        $results.empty();

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json',
            },
        })
            .done(function (res) {
                var urls = (res && res.data && res.data.images) ? res.data.images : [];

                if (!urls.length) {
                    showStatus($status, 'error', 'No images returned. Please try again.');
                    return;
                }

                showStatus($status, 'success', 'Done.');
                renderImages($results, urls);
            })
            .fail(function (xhr) {
                var message =
                    (xhr && xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.error && xhr.responseJSON.error.message))) ||
                    (xhr && xhr.responseText) ||
                    'Request failed. Please try again.';

                showStatus($status, 'error', message);
            })
            .always(function () {
                $submit.prop('disabled', false);
            });
    });
})(jQuery);

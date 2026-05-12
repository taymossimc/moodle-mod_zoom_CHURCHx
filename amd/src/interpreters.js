// This file is part of the Zoom YT plugin for Moodle.
//
// @copyright  2026 TUCC
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Interpreter editor: "Add row" inputs + table of saved interpreters with delete buttons.
 *
 * Used for both spoken-language interpretation and sign-language interpretation.
 * Each instance reads its configuration (labels, language options, source code) from
 * a data-config attribute on the root div, and writes the current rows as JSON into
 * the matching hidden input named in data-target.
 *
 * @module mod_zoomyt/interpreters
 */
define(['jquery'], function($) {

    /**
     * Render an option list for a language dropdown.
     *
     * @param {Object} langs map of code -> label
     * @param {string} selected
     * @param {string} placeholder
     * @return {string}
     */
    function renderOptions(langs, selected, placeholder) {
        var html = '<option value="">' + escapeHtml(placeholder) + '</option>';
        Object.keys(langs).forEach(function(code) {
            var sel = (code === selected) ? ' selected' : '';
            html += '<option value="' + escapeHtml(code) + '"' + sel + '>' +
                escapeHtml(langs[code]) + '</option>';
        });
        return html;
    }

    /**
     * HTML-escape a string.
     *
     * @param {string} s
     * @return {string}
     */
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function(c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    /**
     * Serialize all interpreter rows in this block to its hidden JSON field.
     *
     * @param {JQuery} $root
     */
    function serialize($root) {
        var target = $root.data('target');
        var rows = [];
        $root.find('.zoomyt-interp-row').each(function() {
            var email = $(this).find('.zoomyt-interp-email').text().trim();
            var lang = $(this).data('lang');
            if (email && lang) {
                rows.push({email: email, language: String(lang)});
            }
        });
        $('input[name="' + target + '"]').val(JSON.stringify(rows));
    }

    /**
     * Render the table body from the current rows array.
     *
     * @param {JQuery} $root
     * @param {Array} rows
     * @param {Object} config
     */
    function renderTable($root, rows, config) {
        var $tbody = $root.find('tbody.zoomyt-interp-body');
        $tbody.empty();
        if (!rows.length) {
            $tbody.append(
                '<tr class="zoomyt-interp-empty"><td colspan="3" class="text-muted text-center py-2">' +
                escapeHtml(config.empty) + '</td></tr>'
            );
            return;
        }
        rows.forEach(function(r, i) {
            var label = config.all_languages[r.language] || r.language;
            var $tr = $('<tr class="zoomyt-interp-row" data-index="' + i + '" data-lang="' +
                escapeHtml(r.language) + '"></tr>');
            $tr.append('<td class="zoomyt-interp-email">' + escapeHtml(r.email) + '</td>');
            $tr.append('<td>' + escapeHtml(label) + '</td>');
            $tr.append(
                '<td class="text-right"><button type="button" class="btn btn-link text-danger zoomyt-interp-del" ' +
                'title="' + escapeHtml(config.remove) + '" aria-label="' + escapeHtml(config.remove) + '">' +
                '<i class="icon fa fa-trash fa-fw" aria-hidden="true"></i></button></td>'
            );
            $tbody.append($tr);
        });
    }

    /**
     * Toggle visibility of the editor block based on its associated enable checkbox.
     *
     * @param {JQuery} $root
     * @param {JQuery} $checkbox
     */
    function applyVisibility($root, $checkbox) {
        if ($checkbox.is(':checked')) {
            $root.find('.zoomyt-interp-inner').show();
        } else {
            $root.find('.zoomyt-interp-inner').hide();
        }
    }

    /**
     * Initialise a single interpreter block.
     *
     * @param {HTMLElement} el
     */
    function initBlock(el) {
        var $root = $(el);
        var config;
        try {
            config = JSON.parse($root.attr('data-config'));
        } catch (e) {
            return;
        }

        var target = $root.data('target');
        var enableName = $root.data('enable');
        var initial = [];
        try {
            initial = JSON.parse($('input[name="' + target + '"]').val() || '[]');
            if (!Array.isArray(initial)) {
                initial = [];
            }
        } catch (e) {
            initial = [];
        }

        var langOptions = renderOptions(config.languages, '', config.placeholder_lang);
        var html =
            '<div class="zoomyt-interp-inner">' +
                '<div class="form-row align-items-end mb-2 zoomyt-interp-add-row">' +
                    '<div class="col-md-6">' +
                        '<label class="small text-muted mb-1">' + escapeHtml(config.header_email) + '</label>' +
                        '<input type="email" class="form-control zoomyt-interp-new-email" ' +
                        'placeholder="' + escapeHtml(config.placeholder_email) + '"/>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="small text-muted mb-1">' + escapeHtml(config.header_language) + '</label>' +
                        '<select class="form-control zoomyt-interp-new-lang">' + langOptions + '</select>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<button type="button" class="btn btn-primary btn-block zoomyt-interp-add">' +
                        '<i class="icon fa fa-plus fa-fw" aria-hidden="true"></i> ' +
                        escapeHtml(config.add) + '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="zoomyt-interp-error alert alert-danger py-1 px-2 mb-2" style="display:none;"></div>' +
                '<table class="generaltable mt-2 mb-0" style="width: 100%;">' +
                    '<thead><tr>' +
                        '<th scope="col">' + escapeHtml(config.header_email) + '</th>' +
                        '<th scope="col">' + escapeHtml(config.header_language) + '</th>' +
                        '<th scope="col" class="text-right">' + escapeHtml(config.header_actions) + '</th>' +
                    '</tr></thead>' +
                    '<tbody class="zoomyt-interp-body"></tbody>' +
                '</table>' +
            '</div>';

        $root.html(html);

        var rows = initial.slice();
        renderTable($root, rows, config);
        serialize($root);

        // Bind checkbox visibility toggle.
        var $cb = $('input[name="' + enableName + '"]').last();
        if ($cb.length) {
            applyVisibility($root, $cb);
            $cb.on('change', function() {
                applyVisibility($root, $cb);
            });
        }

        // Add new interpreter.
        $root.on('click', '.zoomyt-interp-add', function() {
            var $err = $root.find('.zoomyt-interp-error');
            var $emailInput = $root.find('.zoomyt-interp-new-email');
            var $langSelect = $root.find('.zoomyt-interp-new-lang');
            var email = $.trim($emailInput.val());
            var lang = $langSelect.val();

            $err.hide().text('');

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $err.text(config.err_email).show();
                $emailInput.focus();
                return;
            }
            if (!lang) {
                $err.text(config.err_lang).show();
                $langSelect.focus();
                return;
            }
            var dup = rows.some(function(r) {
                return r.email.toLowerCase() === email.toLowerCase();
            });
            if (dup) {
                $err.text(config.err_duplicate).show();
                $emailInput.focus();
                return;
            }

            rows.push({email: email, language: lang});
            renderTable($root, rows, config);
            serialize($root);
            $emailInput.val('');
            $langSelect.val('');
            $emailInput.focus();
        });

        // Allow Enter in the email field to trigger add.
        $root.on('keydown', '.zoomyt-interp-new-email, .zoomyt-interp-new-lang', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $root.find('.zoomyt-interp-add').trigger('click');
            }
        });

        // Delete interpreter.
        $root.on('click', '.zoomyt-interp-del', function() {
            var idx = $(this).closest('tr').data('index');
            if (typeof idx === 'number' && idx >= 0 && idx < rows.length) {
                rows.splice(idx, 1);
                renderTable($root, rows, config);
                serialize($root);
            }
        });

        // Final safety: re-serialize on form submit.
        $root.closest('form').on('submit', function() {
            serialize($root);
        });
    }

    return {
        init: function() {
            $('.zoomyt-interp-block').each(function() {
                initBlock(this);
            });
        }
    };
});

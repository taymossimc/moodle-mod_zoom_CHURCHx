// This file is part of the Zoom YT plugin for Moodle.
//
// @copyright  2026 TUCC
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Interpreter editor: "Add row" inputs + table of saved interpreters with delete buttons.
 *
 * Two modes:
 *   - mode "pair": email + From-language + To-language (used for spoken interpretation).
 *   - mode "single": email + one language (used for sign-language interpretation).
 *
 * Configuration arrives via the root element's data-config attribute and the current
 * row list is read/written as JSON to the hidden input named in data-target.
 *
 * @module mod_zoomyt/interpreters
 */
define(['jquery'], function($) {

    /**
     * HTML-escape a string for safe insertion into markup.
     *
     * @param {string} s
     * @return {string}
     */
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    /**
     * Render an <option> list for a language dropdown.
     *
     * @param {Object} langs map of code -> label
     * @param {string} selected currently-selected code
     * @param {string} placeholder placeholder label for the empty option
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
     * Serialize current rows in this block back to the hidden JSON input.
     *
     * @param {JQuery} $root
     * @param {Array} rows current state in memory
     */
    function serialize($root, rows) {
        var target = $root.data('target');
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
        var isPair = (config.mode === 'pair');
        var colCount = isPair ? 4 : 3;
        $tbody.empty();
        if (!rows.length) {
            $tbody.append(
                '<tr class="zoomyt-interp-empty"><td colspan="' + colCount + '" ' +
                'class="text-muted text-center py-2">' +
                escapeHtml(config.empty) + '</td></tr>'
            );
            return;
        }
        rows.forEach(function(r, i) {
            var $tr = $('<tr class="zoomyt-interp-row" data-index="' + i + '"></tr>');
            $tr.append('<td>' + escapeHtml(r.email) + '</td>');
            if (isPair) {
                var fromLabel = config.all_languages[r.lang_from] || r.lang_from || '';
                var toLabel = config.all_languages[r.lang_to] || r.lang_to || '';
                $tr.append('<td>' + escapeHtml(fromLabel) + '</td>');
                $tr.append('<td>' + escapeHtml(toLabel) + '</td>');
            } else {
                var label = config.all_languages[r.language] || r.language || '';
                $tr.append('<td>' + escapeHtml(label) + '</td>');
            }
            $tr.append(
                '<td class="text-right"><button type="button" class="btn btn-link text-danger zoomyt-interp-del" ' +
                'title="' + escapeHtml(config.remove) + '" aria-label="' + escapeHtml(config.remove) + '">' +
                '<i class="icon fa fa-trash fa-fw" aria-hidden="true"></i></button></td>'
            );
            $tbody.append($tr);
        });
    }

    /**
     * Show/hide the editor based on the matching enable checkbox.
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
     * Build the input row markup for the configured mode.
     *
     * @param {Object} config
     * @return {string}
     */
    function buildAddRow(config) {
        if (config.mode === 'pair') {
            return '' +
                '<div class="form-row align-items-end mb-2 zoomyt-interp-add-row">' +
                    '<div class="col-md-4">' +
                        '<label class="small text-muted mb-1">' + escapeHtml(config.header_email) + '</label>' +
                        '<input type="email" class="form-control zoomyt-interp-new-email" ' +
                        'placeholder="' + escapeHtml(config.placeholder_email) + '"/>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<label class="small text-muted mb-1">' + escapeHtml(config.header_lang_from) + '</label>' +
                        '<select class="form-control zoomyt-interp-new-from">' +
                        renderOptions(config.languages, '', config.placeholder_lang) + '</select>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<label class="small text-muted mb-1">' + escapeHtml(config.header_lang_to) + '</label>' +
                        '<select class="form-control zoomyt-interp-new-to">' +
                        renderOptions(config.languages, '', config.placeholder_lang) + '</select>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<button type="button" class="btn btn-primary btn-block zoomyt-interp-add">' +
                        '<i class="icon fa fa-plus fa-fw" aria-hidden="true"></i> ' +
                        escapeHtml(config.add) + '</button>' +
                    '</div>' +
                '</div>';
        }
        return '' +
            '<div class="form-row align-items-end mb-2 zoomyt-interp-add-row">' +
                '<div class="col-md-6">' +
                    '<label class="small text-muted mb-1">' + escapeHtml(config.header_email) + '</label>' +
                    '<input type="email" class="form-control zoomyt-interp-new-email" ' +
                    'placeholder="' + escapeHtml(config.placeholder_email) + '"/>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<label class="small text-muted mb-1">' + escapeHtml(config.header_language) + '</label>' +
                    '<select class="form-control zoomyt-interp-new-lang">' +
                    renderOptions(config.languages, '', config.placeholder_lang) + '</select>' +
                '</div>' +
                '<div class="col-md-2">' +
                    '<button type="button" class="btn btn-primary btn-block zoomyt-interp-add">' +
                    '<i class="icon fa fa-plus fa-fw" aria-hidden="true"></i> ' +
                    escapeHtml(config.add) + '</button>' +
                '</div>' +
            '</div>';
    }

    /**
     * Build the table header row.
     *
     * @param {Object} config
     * @return {string}
     */
    function buildTableHead(config) {
        if (config.mode === 'pair') {
            return '<thead><tr>' +
                '<th scope="col">' + escapeHtml(config.header_email) + '</th>' +
                '<th scope="col">' + escapeHtml(config.header_lang_from) + '</th>' +
                '<th scope="col">' + escapeHtml(config.header_lang_to) + '</th>' +
                '<th scope="col" class="text-right">' + escapeHtml(config.header_actions) + '</th>' +
                '</tr></thead>';
        }
        return '<thead><tr>' +
            '<th scope="col">' + escapeHtml(config.header_email) + '</th>' +
            '<th scope="col">' + escapeHtml(config.header_language) + '</th>' +
            '<th scope="col" class="text-right">' + escapeHtml(config.header_actions) + '</th>' +
            '</tr></thead>';
    }

    /**
     * Initialise a single editor block.
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
        if (config.mode !== 'pair' && config.mode !== 'single') {
            config.mode = 'single';
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

        var html =
            '<div class="zoomyt-interp-inner">' +
                buildAddRow(config) +
                '<div class="zoomyt-interp-error alert alert-danger py-1 px-2 mb-2" style="display:none;"></div>' +
                '<table class="generaltable mt-2 mb-0" style="width: 100%;">' +
                    buildTableHead(config) +
                    '<tbody class="zoomyt-interp-body"></tbody>' +
                '</table>' +
            '</div>';

        $root.html(html);

        var rows = initial.slice();
        renderTable($root, rows, config);
        serialize($root, rows);

        // Bind checkbox visibility toggle (advcheckbox renders a hidden + a real checkbox; use the visible one).
        var $cb = $('input[name="' + enableName + '"][type="checkbox"]');
        if (!$cb.length) {
            $cb = $('input[name="' + enableName + '"]').last();
        }
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
            var email = $.trim($emailInput.val());
            $err.hide().text('');

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $err.text(config.err_email).show();
                $emailInput.focus();
                return;
            }

            var newRow;
            if (config.mode === 'pair') {
                var $fromSel = $root.find('.zoomyt-interp-new-from');
                var $toSel = $root.find('.zoomyt-interp-new-to');
                var fromVal = $fromSel.val();
                var toVal = $toSel.val();
                if (!fromVal || !toVal) {
                    $err.text(config.err_lang).show();
                    (!fromVal ? $fromSel : $toSel).focus();
                    return;
                }
                if (fromVal === toVal) {
                    $err.text(config.err_same).show();
                    $toSel.focus();
                    return;
                }
                newRow = {email: email, lang_from: fromVal, lang_to: toVal};
            } else {
                var $langSel = $root.find('.zoomyt-interp-new-lang');
                var lang = $langSel.val();
                if (!lang) {
                    $err.text(config.err_lang).show();
                    $langSel.focus();
                    return;
                }
                newRow = {email: email, language: lang};
            }

            var dup = rows.some(function(r) {
                return r.email.toLowerCase() === email.toLowerCase();
            });
            if (dup) {
                $err.text(config.err_duplicate).show();
                $emailInput.focus();
                return;
            }

            rows.push(newRow);
            renderTable($root, rows, config);
            serialize($root, rows);
            $emailInput.val('');
            $root.find('.zoomyt-interp-new-lang, .zoomyt-interp-new-from, .zoomyt-interp-new-to').val('');
            $emailInput.focus();
        });

        // Enter key in any add-row input triggers Add.
        $root.on('keydown',
            '.zoomyt-interp-new-email, .zoomyt-interp-new-lang, .zoomyt-interp-new-from, .zoomyt-interp-new-to',
            function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $root.find('.zoomyt-interp-add').trigger('click');
                }
            }
        );

        // Delete interpreter.
        $root.on('click', '.zoomyt-interp-del', function() {
            var idx = $(this).closest('tr').data('index');
            if (typeof idx === 'number' && idx >= 0 && idx < rows.length) {
                rows.splice(idx, 1);
                renderTable($root, rows, config);
                serialize($root, rows);
            }
        });

        // Safety: re-serialize on form submit.
        $root.closest('form').on('submit', function() {
            serialize($root, rows);
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

// This file is part of the Zoom YT plugin for Moodle.
//
// @copyright  2026 TUCC
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Custom session dates editor for recurring meetings (local Moodle schedule).
 *
 * @module mod_zoomyt/customdates
 */
define(['jquery'], function($) {
    var HIDDENSELECTOR = 'input[name="custom_occurrences_json"]';

    /**
     * Format unix timestamp for datetime-local input (local timezone).
     *
     * @param {number} ts
     * @return {string}
     */
    function tsToDatetimeLocal(ts) {
        var d = new Date(ts * 1000);
        var pad = function(n) {
            return (n < 10 ? '0' : '') + n;
        };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
            'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    /**
     * @param {JQuery} $tr
     * @return {Object|null}
     */
    function parseRow($tr) {
        var inp = $tr.find('.zoomyt-cd-time').val();
        var duration = parseInt($tr.find('.zoomyt-cd-duration').val(), 10);
        if (!duration || duration < 1) {
            duration = 60;
        }
        if (!inp) {
            return null;
        }
        var t = Date.parse(inp);
        if (isNaN(t)) {
            return null;
        }
        return {
            start_time: Math.floor(t / 1000),
            duration: duration
        };
    }

    /**
     * @param {JQuery} $root
     */
    function serialize($root) {
        var rows = [];
        $root.find('tbody tr').each(function() {
            var r = parseRow($(this));
            if (r) {
                rows.push(r);
            }
        });
        $(HIDDENSELECTOR).val(JSON.stringify(rows));
    }

    /**
     * @param {JQuery} $root
     * @param {Object} labels
     * @param {Object|null} data
     */
    function addRow($root, labels, data) {
        var $tbody = $root.find('tbody');
        var startVal = '';
        if (data && data.start_time) {
            startVal = tsToDatetimeLocal(data.start_time);
        }
        var dur = (data && data.duration) ? data.duration : 60;
        var $tr = $('<tr></tr>');
        $tr.append(
            '<td style="width: 240px;"><label class="accesshide">' + labels.when + '</label>' +
            '<input type="datetime-local" class="form-control zoomyt-cd-time" ' +
            'style="max-width: 230px;" value="' + startVal + '" step="300"/></td>'
        );
        $tr.append(
            '<td style="width: 140px;"><label class="accesshide">' + labels.duration + '</label>' +
            '<div class="input-group" style="max-width: 130px;">' +
            '<input type="number" class="form-control zoomyt-cd-duration" min="1" max="9000" value="' + dur + '"/>' +
            '<span class="input-group-append"><span class="input-group-text">min</span></span>' +
            '</div></td>'
        );
        $tr.append(
            '<td><button type="button" class="btn btn-link text-danger zoomyt-cd-del" title="' + labels.remove + '">' +
            '<i class="icon fa fa-trash fa-fw" aria-hidden="true"></i></button></td>'
        );
        $tbody.append($tr);
    }

    return {
        init: function() {
            var $root = $('#zoomyt-custom-dates-root');
            if (!$root.length) {
                return;
            }
            var labels = {add: 'Add', remove: 'Remove', when: 'When', duration: 'Duration', timezone: ''};
            try {
                var raw = $root.attr('data-labels');
                if (raw) {
                    labels = $.extend(labels, JSON.parse(raw));
                }
            } catch (e) {
                // Keep defaults.
            }

            var $form = $root.closest('form');
            var tzNotice = labels.timezone ?
                '<div class="text-muted small mb-2">' + labels.timezone + '</div>' : '';
            var table = tzNotice +
                '<table class="generaltable" style="width: auto;">' +
                '<thead><tr><th scope="col">' + labels.when + '</th>' +
                '<th scope="col">' + labels.duration + '</th><th scope="col"></th></tr></thead>' +
                '<tbody></tbody></table>' +
                '<button type="button" class="btn btn-secondary mt-2 zoomyt-cd-add">' +
                '<i class="icon fa fa-plus fa-fw" aria-hidden="true"></i> ' + labels.add + '</button>';
            $root.append(table);

            var initial = [];
            try {
                initial = JSON.parse($(HIDDENSELECTOR).val() || '[]');
            } catch (e2) {
                initial = [];
            }
            if (initial.length === 0) {
                addRow($root, labels, null);
            } else {
                initial.forEach(function(r) {
                    addRow($root, labels, r);
                });
            }
            serialize($root);

            $root.on('click', '.zoomyt-cd-add', function() {
                addRow($root, labels, null);
                serialize($root);
            });
            $root.on('click', '.zoomyt-cd-del', function() {
                $(this).closest('tr').remove();
                if ($root.find('tbody tr').length === 0) {
                    addRow($root, labels, null);
                }
                serialize($root);
            });
            $root.on('change input', '.zoomyt-cd-time, .zoomyt-cd-duration', function() {
                serialize($root);
            });
            $form.on('submit', function() {
                serialize($root);
            });
        }
    };
});

<style>
    .ajax-ac-wrap { position: relative; }
    .ajax-ac-results {
        position: fixed;
        z-index: 1100;
        background: #fff;
        border: 1px solid #ced4da;
        max-height: 240px;
        overflow-y: auto;
        box-shadow: 0 6px 14px rgba(0,0,0,.12);
        display: none;
        min-width: 160px;
    }
    .ajax-ac-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        line-height: 1.25;
    }
    .ajax-ac-item:last-child { border-bottom: none; }
    .ajax-ac-item:hover, .ajax-ac-item.is-active { background: #f8f9fa; }
    .ajax-ac-muted { color: #6c757d; }
    table.ajax-ac-table,
    table.ajax-ac-table tbody,
    table.ajax-ac-table tr,
    table.ajax-ac-table td {
        overflow: visible;
    }
    table.ajax-ac-table td:has(.ajax-ac-wrap) {
        position: relative;
    }
</style>

<script>
    (function(){
        if (window.__ajaxAutocompleteInit) return;
        window.__ajaxAutocompleteInit = true;

        window.ajaxAutocompleteI18n = window.ajaxAutocompleteI18n || {
            noResults: @json(__('messages.Aucun résultat')),
            searchError: @json(__('messages.ajax_autocomplete_search_error'))
        };

        function esc(s){
            return (s ?? '').toString()
                .replace(/&/g,'&amp;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        }

        function debounce(fn, delay){
            var t;
            return function(){
                var ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function(){ fn.apply(ctx, args); }, delay);
            };
        }

        function displayText(item){
            return item.text || item.label || item.name || '';
        }

        function repositionResults($input, $results){
            if (!$input || !$input.length || !$results || !$results.length) return;
            var el = $input[0];
            if (!el || !el.getBoundingClientRect) return;
            var rect = el.getBoundingClientRect();
            $results.css({
                position: 'fixed',
                top: rect.bottom + 'px',
                left: rect.left + 'px',
                width: Math.max(rect.width, 160) + 'px',
                right: 'auto'
            });
        }

        if (!window.__ajaxAcGlobalReposition) {
            window.__ajaxAcGlobalReposition = true;
            $(window).on('scroll.ajaxAcGlobal resize.ajaxAcGlobal', function(){
                $('.ajax-ac-results:visible').each(function(){
                    var $results = $(this);
                    var $input = $results.closest('.ajax-ac-wrap').find('input').filter(function(){
                        var type = (this.type || '').toLowerCase();
                        return type === 'text' || type === 'search' || type === '';
                    }).first();
                    if ($input.length) {
                        repositionResults($input, $results);
                    }
                });
            });
        }

        function bindAutocompleteCore(opts){
            var $input = opts.$input;
            var $hidden = opts.$hidden || null;
            var minChars = opts.minChars ?? 1;
            var onSelect = opts.onSelect || function(){};
            var renderItem = opts.renderItem || function(item){
                return '<div><strong>' + esc(displayText(item)) + '</strong></div>';
            };
            var runSearch = opts.runSearch;
            var validateBeforeSearch = opts.validateBeforeSearch || null;

            if (!$input || !$input.length) return null;

            var $wrap = $input.closest('.ajax-ac-wrap');
            if (!$wrap.length){
                $input.wrap('<div class="ajax-ac-wrap"></div>');
                $wrap = $input.closest('.ajax-ac-wrap');
            }
            var $table = $wrap.closest('table');
            if ($table.length) {
                $table.addClass('ajax-ac-table');
            }

            var $results = $wrap.find('.ajax-ac-results');
            if (!$results.length){
                $results = $('<div class="ajax-ac-results" role="listbox"></div>');
                $wrap.append($results);
            }

            var activeIndex = -1;
            var lastResults = [];

            function close(){
                $results.hide().empty();
                activeIndex = -1;
                lastResults = [];
            }

            function showResults(html, results){
                lastResults = results || [];
                activeIndex = -1;
                $results.html(html).show();
                repositionResults($input, $results);
            }

            function setActive(idx){
                activeIndex = idx;
                $results.find('.ajax-ac-item').removeClass('is-active');
                if (activeIndex >= 0){
                    var $it = $results.find('.ajax-ac-item').eq(activeIndex);
                    $it.addClass('is-active');
                    var top = $it.position().top;
                    var h = $it.outerHeight();
                    var boxH = $results.innerHeight();
                    if (top < 0) $results.scrollTop($results.scrollTop() + top);
                    else if (top + h > boxH) $results.scrollTop($results.scrollTop() + (top + h - boxH));
                }
            }

            function choose(item){
                $input.val(displayText(item));
                if ($hidden && $hidden.length){
                    $hidden.val(item.id ?? '');
                }
                onSelect(item);
                close();
            }

            var doSearch = debounce(function(){
                if (typeof validateBeforeSearch === 'function' && validateBeforeSearch() === false) {
                    close();
                    return;
                }

                var q = ($input.val() || '').trim();
                if ($hidden && $hidden.length) $hidden.val('');
                if (q.length < minChars){ close(); return; }

                runSearch(q, {
                    close: close,
                    showResults: showResults,
                    choose: choose,
                    esc: esc,
                    renderItem: renderItem,
                    i18n: window.ajaxAutocompleteI18n
                });
            }, 200);

            $input.on('input', doSearch);

            $input.on('focus', function(){
                var q = ($input.val() || '').trim();
                if (q.length >= minChars) {
                    doSearch();
                }
            });

            $input.on('keydown', function(e){
                if (!$results.is(':visible')) return;
                var count = $results.find('.ajax-ac-item[data-idx]').length;
                if (!count) return;

                if (e.key === 'ArrowDown'){
                    e.preventDefault();
                    setActive(activeIndex + 1 >= count ? 0 : activeIndex + 1);
                } else if (e.key === 'ArrowUp'){
                    e.preventDefault();
                    setActive(activeIndex - 1 < 0 ? count - 1 : activeIndex - 1);
                } else if (e.key === 'Enter'){
                    if (activeIndex >= 0 && lastResults[activeIndex]){
                        e.preventDefault();
                        choose(lastResults[activeIndex]);
                    }
                } else if (e.key === 'Escape'){
                    close();
                }
            });

            $wrap.on('mousedown', '.ajax-ac-item[data-idx]', function(e){
                e.preventDefault();
                var idx = parseInt($(this).data('idx'), 10);
                if (!isNaN(idx) && lastResults[idx]){
                    choose(lastResults[idx]);
                }
            });

            $input.on('blur', function(){
                setTimeout(close, 150);
            });

            $(document).on('click', function(ev){
                if (!$.contains($wrap[0], ev.target)) close();
            });

            return { close: close };
        }

        /**
         * Attach ajax autocomplete to an input.
         */
        window.attachAjaxAutocomplete = function(opts){
            var $input = opts.$input;
            var $hidden = opts.$hidden || null;
            var url = opts.url;
            var extraParams = opts.extraParams || null;
            var filterResults = opts.filterResults || null;
            var validateBeforeSearch = opts.validateBeforeSearch || null;
            var onSelect = opts.onSelect || function(){};
            var renderItem = opts.renderItem || function(item){
                return '<div><strong>' + esc(displayText(item)) + '</strong></div>';
            };

            return bindAutocompleteCore({
                $input: $input,
                $hidden: $hidden,
                minChars: opts.minChars ?? 1,
                onSelect: onSelect,
                renderItem: renderItem,
                validateBeforeSearch: validateBeforeSearch,
                runSearch: function(q, ctx){
                    var params = { q: q };
                    if (extraParams) {
                        var add = (typeof extraParams === 'function') ? (extraParams() || {}) : (extraParams || {});
                        Object.keys(add).forEach(function(k){ params[k] = add[k]; });
                    }

                    $.get(url, params, function(data){
                        var results = (data && data.results) ? data.results : [];
                        if (typeof filterResults === 'function') {
                            results = filterResults(results) || [];
                        }
                        if (!results.length){
                            ctx.showResults('<div class="ajax-ac-item ajax-ac-muted">' + ctx.esc(ctx.i18n.noResults) + '</div>', []);
                            return;
                        }
                        var html = '';
                        results.forEach(function(item, idx){
                            html += '<div class="ajax-ac-item" data-idx="'+idx+'">' + ctx.renderItem(item) + '</div>';
                        });
                        ctx.showResults(html, results);
                    }).fail(function(){
                        ctx.showResults('<div class="ajax-ac-item ajax-ac-muted">' + ctx.esc(ctx.i18n.searchError) + '</div>', []);
                    });
                }
            });
        };

        /**
         * Attach local (in-memory) autocomplete — same UI/behavior as AJAX variant.
         */
        window.attachLocalAutocomplete = function(opts){
            var items = opts.items || [];
            var getItems = opts.getItems || null;
            var filterResults = opts.filterResults || null;
            var onSelect = opts.onSelect || function(){};
            var renderItem = opts.renderItem || function(item){
                return '<div><strong>' + esc(displayText(item)) + '</strong></div>';
            };

            function filterItems(q){
                var source = (typeof getItems === 'function') ? (getItems() || []) : items;
                q = (q || '').toLowerCase().trim();
                var results = source.filter(function(item){
                    var hay = ((displayText(item)) + ' ' + (item.code || '') + ' ' + (item.name || '')).toLowerCase();
                    return hay.indexOf(q) !== -1;
                }).slice(0, opts.limit || 30);
                if (typeof filterResults === 'function') {
                    results = filterResults(results) || [];
                }
                return results;
            }

            return bindAutocompleteCore({
                $input: opts.$input,
                $hidden: opts.$hidden || null,
                minChars: opts.minChars ?? 1,
                onSelect: onSelect,
                renderItem: renderItem,
                validateBeforeSearch: opts.validateBeforeSearch || null,
                runSearch: function(q, ctx){
                    var results = filterItems(q);
                    if (!results.length){
                        ctx.showResults('<div class="ajax-ac-item ajax-ac-muted">' + ctx.esc(ctx.i18n.noResults) + '</div>', []);
                        return;
                    }
                    var html = '';
                    results.forEach(function(item, idx){
                        html += '<div class="ajax-ac-item" data-idx="'+idx+'">' + ctx.renderItem(item) + '</div>';
                    });
                    ctx.showResults(html, results);
                }
            });
        };
    })();
</script>

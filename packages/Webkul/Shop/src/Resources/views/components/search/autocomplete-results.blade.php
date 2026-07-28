{{--
    Sibling dropdown for a `data-search-autocomplete` input. Deliberately
    plain JS, not a Vue component: the desktop search input is a WebMCP tool
    parameter (see webmcp.blade.php) discovered by scanning the raw DOM for
    `input[name]` before Vue finishes mounting — replacing it with a
    Vue-rendered element would leave that scan empty. This only adds a
    sibling results panel and a `data-*` marker; the input itself, its
    `name`, and its WebMCP attributes are untouched.
--}}
<div
    class="search-autocomplete-results absolute inset-x-0 top-full z-30 mt-2 hidden max-h-96 overflow-auto rounded-lg border border-zinc-200 bg-white text-left shadow-lg"
    role="listbox"
>
</div>

@pushOnce('scripts', 'search-autocomplete')
    <script type="module">
        (function () {
            const MIN_LENGTH = {{ (int) (core()->getConfigData('catalog.products.search.min_query_length') ?: 1) }};
            const PRODUCTS_URL = "{{ route('shop.api.products.index') }}";
            const PRODUCT_URL_TEMPLATE = "{{ route('shop.product_or_category.index', ':slug') }}";
            const SEARCH_URL = "{{ route('shop.search.index') }}";
            const NO_RESULTS_TEXT = @json(trans('shop::app.search.no-results'));
            const VIEW_ALL_TEXT = @json(trans('shop::app.search.view-all-results'));

            let debounceTimer = null;
            let activeInput = null;

            function closeAllDropdowns() {
                document.querySelectorAll('.search-autocomplete-results').forEach(function (panel) {
                    panel.classList.add('hidden');
                    panel.innerHTML = '';
                });
            }

            function escapeHtml(value) {
                const div = document.createElement('div');

                div.textContent = value;

                return div.innerHTML;
            }

            function renderResults(panel, query, products) {
                if (! products.length) {
                    panel.innerHTML = '<p class="p-4 text-center text-sm text-zinc-500">' + escapeHtml(NO_RESULTS_TEXT) + '</p>';

                    return;
                }

                const items = products.map(function (product) {
                    const url = PRODUCT_URL_TEMPLATE.replace(':slug', product.url_key);

                    return '' +
                        '<a href="' + url + '" class="flex items-center gap-3 border-b border-zinc-100 p-3 text-sm last:border-b-0 hover:bg-zinc-50">' +
                            '<img src="' + product.base_image.small_image_url + '" alt="' + escapeHtml(product.name) + '" width="48" height="48" class="h-12 w-12 rounded object-cover">' +
                            '<span class="min-w-0 flex-1">' +
                                '<span class="block truncate font-medium text-black">' + escapeHtml(product.name) + '</span>' +
                                '<span class="block text-xs text-zinc-500">' + product.price_html + '</span>' +
                            '</span>' +
                        '</a>';
                }).join('');

                const viewAllUrl = SEARCH_URL + '?query=' + encodeURIComponent(query);

                panel.innerHTML = items +
                    '<a href="' + viewAllUrl + '" class="block p-3 text-center text-sm font-medium text-navyBlue hover:bg-zinc-50">' +
                        escapeHtml(VIEW_ALL_TEXT) + ' "' + escapeHtml(query) + '"' +
                    '</a>';
            }

            function fetchSuggestions(input, panel) {
                const query = input.value.trim();

                if (query.length < MIN_LENGTH) {
                    panel.classList.add('hidden');

                    panel.innerHTML = '';

                    return;
                }

                panel.classList.remove('hidden');

                panel.innerHTML = '<div class="flex items-center justify-center p-4"><span class="shimmer h-5 w-5 rounded-full"></span></div>';

                fetch(PRODUCTS_URL + '?query=' + encodeURIComponent(query) + '&limit=6', {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(function (response) { return response.json(); })
                    .then(function (json) {
                        if (input.value.trim() !== query) {
                            // A newer keystroke has already fired another request.
                            return;
                        }

                        renderResults(panel, query, json.data || []);
                    })
                    .catch(function () {
                        panel.classList.add('hidden');
                    });
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-search-autocomplete]').forEach(function (input) {
                    const panel = input.parentElement?.querySelector('.search-autocomplete-results');

                    if (! panel) {
                        return;
                    }

                    input.addEventListener('input', function () {
                        activeInput = input;

                        clearTimeout(debounceTimer);

                        debounceTimer = setTimeout(function () {
                            fetchSuggestions(input, panel);
                        }, 300);
                    });

                    input.addEventListener('focus', function () {
                        if (input.value.trim().length >= MIN_LENGTH) {
                            activeInput = input;

                            fetchSuggestions(input, panel);
                        }
                    });
                });

                document.addEventListener('click', function (event) {
                    if (activeInput && ! activeInput.parentElement.contains(event.target)) {
                        closeAllDropdowns();
                    }
                });
            });
        })();
    </script>
@endPushOnce

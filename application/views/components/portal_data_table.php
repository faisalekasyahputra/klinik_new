<script>
(function () {
    function initTable(root) {
        if (!root || root.dataset.tableReady === 'true') return;

        var body = root.querySelector('[data-table-body]');
        if (!body) return;

        root.dataset.tableReady = 'true';

        var allRows = Array.prototype.slice.call(body.querySelectorAll('tr[data-table-row]'));
        var state = {
            page: 1,
            perPage: parseInt(root.getAttribute('data-table-per-page'), 10) || 10,
            rows: allRows.slice(),
            sortColumn: null,
            sortDirection: 'asc'
        };
        var search = root.querySelector('[data-table-search]');
        var perPage = root.querySelector('[data-table-per-page-select]');
        var pagination = root.querySelector('[data-table-pagination]');
        var summary = root.querySelector('[data-table-summary]');
        var empty = root.querySelector('[data-table-empty]');
        function textFor(row, column) {
            var cell = row.querySelector('[data-table-column="' + column + '"]');
            return cell ? cell.textContent.trim().toLocaleLowerCase('id-ID') : '';
        }

        function filterRows() {
            var query = search ? search.value.trim().toLocaleLowerCase('id-ID') : '';
            state.rows = allRows.filter(function (row) {
                return !query || row.textContent.toLocaleLowerCase('id-ID').indexOf(query) !== -1;
            });
        }

        function updateSortButtons() {
            var buttons = root.querySelectorAll('[data-table-sort]');
            Array.prototype.forEach.call(buttons, function (button) {
                var active = button.getAttribute('data-table-sort') === state.sortColumn;
                var icon = button.querySelector('[data-table-sort-icon]');
                button.setAttribute('aria-sort', active ? (state.sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
                if (icon) icon.className = active && state.sortDirection === 'desc' ? 'fa-solid fa-arrow-down text-[9px]' : active ? 'fa-solid fa-arrow-up text-[9px]' : 'fa-solid fa-sort text-[9px] opacity-50';
            });
        }

        function sortRows() {
            if (!state.sortColumn) return;
            state.rows.sort(function (a, b) {
                var result = textFor(a, state.sortColumn).localeCompare(textFor(b, state.sortColumn), 'id-ID', { numeric: true });
                return state.sortDirection === 'asc' ? result : -result;
            });
        }

        function makeButton(label, disabled, onClick, active) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            button.className = 'inline-flex h-7 min-w-7 items-center justify-center rounded-md px-2 text-[10px] font-bold transition-colors ' + (active ? 'text-[#123139]' : 'text-[#39707a] hover:bg-[#00a3b5]/10') + (disabled ? ' cursor-not-allowed opacity-40' : '');
            button.style.background = active ? 'var(--brand-primary)' : 'var(--portal-bg)';
            button.style.border = '1px solid var(--portal-border)';
            button.addEventListener('click', onClick);
            return button;
        }

        function renderPagination(totalPages) {
            if (!pagination) return;
            pagination.innerHTML = '';
            if (totalPages <= 1) return;

            pagination.appendChild(makeButton('‹', state.page === 1, function () { state.page--; render(); }));
            var first = Math.max(1, state.page - 2);
            var last = Math.min(totalPages, first + 4);
            first = Math.max(1, last - 4);
            for (var page = first; page <= last; page++) {
                (function (target) {
                    pagination.appendChild(makeButton(String(target), false, function () { state.page = target; render(); }, target === state.page));
                }(page));
            }
            pagination.appendChild(makeButton('›', state.page === totalPages, function () { state.page++; render(); }));
        }

        function render() {
            filterRows();
            sortRows();
            var total = state.rows.length;
            var totalPages = Math.max(1, Math.ceil(total / state.perPage));
            if (state.page > totalPages) state.page = totalPages;
            var start = (state.page - 1) * state.perPage;
            var visible = state.rows.slice(start, start + state.perPage);

            allRows.forEach(function (row) { row.hidden = true; });
            visible.forEach(function (row, position) {
                row.hidden = false;
                var index = row.querySelector('[data-table-index]');
                if (index) index.textContent = String(start + position + 1);
                body.appendChild(row);
            });

            if (summary) {
                summary.textContent = total ? 'Menampilkan ' + (start + 1) + '-' + Math.min(start + state.perPage, total) + ' dari ' + total + ' data' : 'Tidak ada data ditemukan';
            }
            if (empty) empty.hidden = total !== 0;
            renderPagination(totalPages);
            updateSortButtons();
        }

        if (search) search.addEventListener('input', function () { state.page = 1; render(); });
        if (perPage) perPage.addEventListener('change', function () { state.perPage = parseInt(this.value, 10) || 10; state.page = 1; render(); });
        Array.prototype.forEach.call(root.querySelectorAll('[data-table-sort]'), function (button) {
            button.addEventListener('click', function () {
                var column = this.getAttribute('data-table-sort');
                state.sortDirection = state.sortColumn === column && state.sortDirection === 'asc' ? 'desc' : 'asc';
                state.sortColumn = column;
                state.page = 1;
                render();
            });
        });
        render();
    }

    window.initPortalDataTables = function (scope) {
        var container = scope || document;
        if (container.matches && container.matches('[data-portal-table]')) initTable(container);
        Array.prototype.forEach.call(container.querySelectorAll('[data-portal-table]'), initTable);
    };

    window.initPortalDataTables(document);
}());
</script>

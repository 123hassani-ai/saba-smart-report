/**
 * SQL Server Style Data Table
 * 
 * این فایل برای نمایش جداول به سبک SQL Server Management Studio طراحی شده
 * با قابلیت مرتب‌سازی، فیلترینگ و نمایش اطلاعات جداول
 */

class SQLServerTableViewer {
    constructor(options = {}) {
        this.options = {
            container: options.container || 'table-viewer',
            pageSize: options.pageSize || 50,
            apiBase: options.apiBase || '?action=api&endpoint=',
            rtl: options.rtl !== undefined ? options.rtl : true
        };
        
        this.state = {
            tables: [],
            currentTable: null,
            columns: [],
            data: [],
            sortColumn: '',
            sortOrder: 'asc',
            currentPage: 1,
            totalPages: 1,
            filter: '',
            loading: false
        };
        
        this.init();
    }
    
    /**
     * راه‌اندازی اولیه
     */
    init() {
        this.createUI();
        this.bindEvents();
        this.loadTables();
    }
    
    /**
     * ایجاد رابط کاربری
     */
    createUI() {
        const container = document.getElementById(this.options.container);
        if (!container) return;
        
        container.innerHTML = `
            <div class="sql-server-viewer ${this.options.rtl ? 'rtl' : ''}">
                <div class="sql-server-toolbar">
                    <div class="sql-server-tabs">
                        <div class="tab active" data-tab="tables">جداول</div>
                        <div class="tab" data-tab="data">داده‌ها</div>
                        <div class="tab" data-tab="query">پرس‌وجو</div>
                    </div>
                    <div class="sql-server-actions">
                        <button id="refresh-tables" class="sql-btn" title="بارسازی مجدد">🔄</button>
                        <button id="export-data" class="sql-btn" title="خروجی اکسل">📊</button>
                    </div>
                </div>
                
                <div class="sql-server-content">
                    <div class="sql-server-sidebar">
                        <div class="sql-server-search">
                            <input type="text" id="table-search" placeholder="جستجو در جداول..." />
                        </div>
                        <div class="sql-server-list" id="tables-list"></div>
                    </div>
                    
                    <div class="sql-server-main">
                        <div class="sql-server-table-info">
                            <h3 id="current-table-name">انتخاب نشده</h3>
                            <div id="table-stats" class="table-stats"></div>
                        </div>
                        
                        <div class="sql-server-data-container">
                            <div class="sql-server-table-container" id="data-table-container">
                                <table class="sql-server-table" id="data-table">
                                    <thead id="table-header">
                                        <tr>
                                            <th>منتظر انتخاب جدول...</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body"></tbody>
                                </table>
                            </div>
                            
                            <div class="sql-server-pagination" id="pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * اتصال رویدادها
     */
    bindEvents() {
        // جستجو در جداول
        const searchInput = document.getElementById('table-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterTables(e.target.value));
        }
        
        // رفرش جداول
        const refreshBtn = document.getElementById('refresh-tables');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadTables());
        }
        
        // خروجی اکسل
        const exportBtn = document.getElementById('export-data');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportToExcel());
        }
        
        // تغییر تب‌ها
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                tabs.forEach(t => t.classList.remove('active'));
                e.target.classList.add('active');
                // در صورت نیاز اضافه کردن منطق تغییر تب
            });
        });
    }
    
    /**
     * بارگذاری لیست جداول
     */
    async loadTables() {
        try {
            this.setLoading(true);
            const tablesList = document.getElementById('tables-list');
            tablesList.innerHTML = '<div class="loading-indicator">در حال بارگذاری جداول...</div>';
            
            const response = await this.apiCall('tables');
            
            if (!response.success) {
                throw new Error(response.error || 'خطا در بارگذاری جداول');
            }
            
            this.state.tables = response.data;
            this.renderTablesList();
            
        } catch (error) {
            this.showError(`خطا در بارگذاری جداول: ${error.message}`);
            const tablesList = document.getElementById('tables-list');
            tablesList.innerHTML = '<div class="error-message">خطا در بارگذاری جداول</div>';
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * نمایش لیست جداول
     */
    renderTablesList() {
        const tablesList = document.getElementById('tables-list');
        if (!tablesList) return;
        
        if (!this.state.tables || this.state.tables.length === 0) {
            tablesList.innerHTML = '<div class="empty-message">جدولی یافت نشد</div>';
            return;
        }
        
        let html = '';
        
        this.state.tables.forEach(table => {
            const recordCount = table.records !== '?' ? `<span class="record-count">${this.formatNumber(table.records)}</span>` : '';
            
            html += `
                <div class="table-item" data-table="${table.name}">
                    <div class="table-icon">📋</div>
                    <div class="table-name">${table.name}</div>
                    ${recordCount}
                    <button class="count-btn" data-table="${table.name}" title="محاسبه تعداد رکوردها">
                        📊
                    </button>
                </div>
            `;
        });
        
        tablesList.innerHTML = html;
        
        // اتصال رویداد کلیک روی جداول
        tablesList.querySelectorAll('.table-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.classList.contains('count-btn')) {
                    this.loadTableCount(e.target.dataset.table);
                    return;
                }
                this.selectTable(item.dataset.table);
            });
        });
    }
    
    /**
     * فیلتر کردن جداول بر اساس جستجو
     */
    filterTables(query) {
        const tablesList = document.getElementById('tables-list');
        if (!tablesList) return;
        
        const normalizedQuery = query.toLowerCase().trim();
        
        tablesList.querySelectorAll('.table-item').forEach(item => {
            const tableName = item.dataset.table.toLowerCase();
            if (normalizedQuery === '' || tableName.includes(normalizedQuery)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    /**
     * انتخاب یک جدول
     */
    async selectTable(tableName) {
        try {
            if (this.state.loading) return;
            
            // انتخاب ظاهری جدول
            document.querySelectorAll('.table-item').forEach(item => {
                item.classList.toggle('selected', item.dataset.table === tableName);
            });
            
            this.setLoading(true);
            this.state.currentTable = tableName;
            document.getElementById('current-table-name').textContent = tableName;
            
            // بارگذاری ساختار و داده‌های جدول
            await Promise.all([
                this.loadTableStructure(tableName),
                this.loadTableData(tableName)
            ]);
            
            // بارگذاری آمار جدول اگر قبلاً محاسبه نشده
            const tableItem = Array.from(document.querySelectorAll('.table-item'))
                .find(item => item.dataset.table === tableName);
                
            if (tableItem && !tableItem.querySelector('.record-count')) {
                this.loadTableCount(tableName, false);
            }
            
        } catch (error) {
            this.showError(`خطا در بارگذاری جدول: ${error.message}`);
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * بارگذاری ساختار جدول
     */
    async loadTableStructure(tableName) {
        try {
            const response = await this.apiCall(`table_structure&table=${encodeURIComponent(tableName)}`);
            
            if (!response.success) {
                throw new Error(response.error || 'خطا در بارگذاری ساختار جدول');
            }
            
            this.state.columns = response.columns;
            this.renderTableHeader();
            
            // نمایش آمار ساختار جدول
            document.getElementById('table-stats').innerHTML = `
                <div class="stat-item">
                    <span class="stat-label">تعداد ستون‌ها:</span>
                    <span class="stat-value">${response.columns.length}</span>
                </div>
            `;
            
        } catch (error) {
            console.error('Error loading table structure:', error);
            document.getElementById('table-header').innerHTML = '<tr><th>خطا در بارگذاری ساختار جدول</th></tr>';
        }
    }
    
    /**
     * نمایش هدر جدول
     */
    renderTableHeader() {
        const headerRow = document.getElementById('table-header');
        if (!headerRow || !this.state.columns) return;
        
        let html = '<tr>';
        this.state.columns.forEach(column => {
            const sortClass = this.state.sortColumn === column.name 
                ? (this.state.sortOrder === 'asc' ? 'sort-asc' : 'sort-desc') 
                : '';
                
            html += `
                <th class="column-header ${sortClass}" data-column="${column.name}">
                    <div class="column-title">
                        <span>${column.name}</span>
                        <span class="sort-icon"></span>
                    </div>
                    <div class="column-type">${column.type}${column.length ? `(${column.length})` : ''}</div>
                </th>
            `;
        });
        html += '</tr>';
        
        headerRow.innerHTML = html;
        
        // اتصال رویدادهای مرتب‌سازی
        headerRow.querySelectorAll('.column-header').forEach(header => {
            header.addEventListener('click', () => {
                this.sortTable(header.dataset.column);
            });
        });
    }
    
    /**
     * بارگذاری داده‌های جدول
     */
    async loadTableData(tableName, page = 1, limit = this.options.pageSize) {
        try {
            this.state.currentPage = page;
            
            const response = await this.apiCall(
                `table_data&table=${encodeURIComponent(tableName)}&page=${page}&limit=${limit}&sort=${this.state.sortColumn}&order=${this.state.sortOrder}`
            );
            
            if (!response.success) {
                throw new Error(response.error || 'خطا در بارگذاری داده‌های جدول');
            }
            
            this.state.data = response.data;
            this.state.totalPages = response.pages;
            this.renderTableData();
            this.renderPagination();
            
        } catch (error) {
            console.error('Error loading table data:', error);
            document.getElementById('table-body').innerHTML = `
                <tr>
                    <td colspan="${this.state.columns.length || 1}" class="error-cell">
                        خطا در بارگذاری داده‌ها: ${error.message}
                    </td>
                </tr>
            `;
        }
    }
    
    /**
     * نمایش داده‌های جدول
     */
    renderTableData() {
        const tableBody = document.getElementById('table-body');
        if (!tableBody || !this.state.data) return;
        
        if (this.state.data.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${this.state.columns.length || 1}" class="empty-cell">
                        داده‌ای برای نمایش وجود ندارد
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        
        this.state.data.forEach((row, rowIndex) => {
            html += '<tr>';
            this.state.columns.forEach(column => {
                const value = row[column.name];
                const formattedValue = this.formatCellValue(value, column.type);
                const cellClass = this.getCellClass(value, column.type);
                
                html += `<td class="${cellClass}" title="${value || ''}">${formattedValue}</td>`;
            });
            html += '</tr>';
        });
        
        tableBody.innerHTML = html;
    }
    
    /**
     * نمایش صفحه‌بندی
     */
    renderPagination() {
        const pagination = document.getElementById('pagination');
        if (!pagination) return;
        
        if (this.state.totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // دکمه قبلی
        html += `
            <button class="pagination-btn ${this.state.currentPage === 1 ? 'disabled' : ''}" 
                    data-page="prev" ${this.state.currentPage === 1 ? 'disabled' : ''}>
                قبلی
            </button>
        `;
        
        // شماره صفحات
        let startPage = Math.max(1, this.state.currentPage - 2);
        let endPage = Math.min(this.state.totalPages, startPage + 4);
        
        // اصلاح شماره صفحات اگر به انتها نزدیک هستیم
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        if (startPage > 1) {
            html += `<button class="pagination-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <button class="pagination-btn ${i === this.state.currentPage ? 'active' : ''}" 
                        data-page="${i}">
                    ${i}
                </button>
            `;
        }
        
        if (endPage < this.state.totalPages) {
            if (endPage < this.state.totalPages - 1) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
            html += `<button class="pagination-btn" data-page="${this.state.totalPages}">${this.state.totalPages}</button>`;
        }
        
        // دکمه بعدی
        html += `
            <button class="pagination-btn ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''}" 
                    data-page="next" ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''}>
                بعدی
            </button>
        `;
        
        pagination.innerHTML = html;
        
        // اتصال رویدادها
        pagination.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.classList.contains('disabled')) return;
                
                let page = btn.dataset.page;
                
                if (page === 'prev') {
                    page = this.state.currentPage - 1;
                } else if (page === 'next') {
                    page = this.state.currentPage + 1;
                } else {
                    page = parseInt(page);
                }
                
                this.loadTableData(this.state.currentTable, page);
            });
        });
    }
    
    /**
     * مرتب‌سازی جدول بر اساس ستون
     */
    sortTable(columnName) {
        if (this.state.sortColumn === columnName) {
            // تغییر ترتیب مرتب‌سازی اگر ستون فعلی است
            this.state.sortOrder = this.state.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            // تنظیم ستون جدید و ترتیب پیش‌فرض
            this.state.sortColumn = columnName;
            this.state.sortOrder = 'asc';
        }
        
        // به‌روزرسانی نمایش هدر
        const headers = document.querySelectorAll('.column-header');
        headers.forEach(header => {
            if (header.dataset.column === columnName) {
                header.classList.remove('sort-asc', 'sort-desc');
                header.classList.add(this.state.sortOrder === 'asc' ? 'sort-asc' : 'sort-desc');
            } else {
                header.classList.remove('sort-asc', 'sort-desc');
            }
        });
        
        // بارگذاری مجدد داده‌ها با ترتیب جدید
        this.loadTableData(this.state.currentTable, 1);
    }
    
    /**
     * بارگذاری تعداد رکوردهای جدول
     */
    async loadTableCount(tableName, showLoader = true) {
        try {
            const tableItem = Array.from(document.querySelectorAll('.table-item'))
                .find(item => item.dataset.table === tableName);
                
            if (!tableItem) return;
            
            const countBtn = tableItem.querySelector('.count-btn');
            
            if (showLoader) {
                // نمایش لودر
                if (countBtn) countBtn.innerHTML = '⌛';
            }
            
            const response = await this.apiCall(`table_count&table=${encodeURIComponent(tableName)}`);
            
            if (!response.success) {
                throw new Error(response.error || 'خطا در محاسبه تعداد رکوردها');
            }
            
            // بروزرسانی آمار در جدول ها
            const table = this.state.tables.find(t => t.name === tableName);
            if (table) {
                table.records = response.count;
            }
            
            // بروزرسانی نمایش
            if (!tableItem.querySelector('.record-count')) {
                const recordCount = document.createElement('span');
                recordCount.className = 'record-count';
                recordCount.textContent = this.formatNumber(response.count);
                tableItem.insertBefore(recordCount, countBtn);
            } else {
                tableItem.querySelector('.record-count').textContent = this.formatNumber(response.count);
            }
            
            // مخفی کردن دکمه
            if (countBtn) countBtn.style.display = 'none';
            
            // به‌روزرسانی آمار در صورتی که جدول فعلی است
            if (this.state.currentTable === tableName) {
                const statsElement = document.getElementById('table-stats');
                if (statsElement) {
                    statsElement.innerHTML += `
                        <div class="stat-item">
                            <span class="stat-label">تعداد رکوردها:</span>
                            <span class="stat-value">${this.formatNumber(response.count)}</span>
                        </div>
                    `;
                }
            }
            
        } catch (error) {
            console.error('Error loading record count:', error);
            
            // بازگرداندن دکمه به حالت اولیه
            const tableItem = Array.from(document.querySelectorAll('.table-item'))
                .find(item => item.dataset.table === tableName);
                
            if (tableItem) {
                const countBtn = tableItem.querySelector('.count-btn');
                if (countBtn) countBtn.innerHTML = '📊';
            }
            
            this.showError(`خطا در محاسبه تعداد رکوردها: ${error.message}`);
        }
    }
    
    /**
     * خروجی اکسل
     */
    exportToExcel() {
        if (!this.state.currentTable || !this.state.data || this.state.data.length === 0) {
            this.showError('ابتدا یک جدول با داده انتخاب کنید.');
            return;
        }
        
        try {
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // BOM برای پشتیبانی از یونیکد
            
            // سرستون‌ها
            const headers = this.state.columns.map(column => `"${column.name}"`).join(',');
            csvContent += headers + '\r\n';
            
            // داده‌ها
            this.state.data.forEach(row => {
                const rowData = this.state.columns.map(column => {
                    const value = row[column.name];
                    return `"${value !== null && value !== undefined ? String(value).replace(/"/g, '""') : ''}"`;
                }).join(',');
                csvContent += rowData + '\r\n';
            });
            
            // ایجاد لینک دانلود
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `${this.state.currentTable}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showSuccess('فایل اکسل با موفقیت ایجاد شد.');
            
        } catch (error) {
            this.showError(`خطا در ایجاد فایل اکسل: ${error.message}`);
        }
    }
    
    /**
     * فراخوانی API
     */
    async apiCall(endpoint) {
        const url = this.options.apiBase + endpoint;
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * فرمت کردن مقدار سلول بر اساس نوع داده
     */
    formatCellValue(value, type) {
        if (value === null || value === undefined) return '';
        
        switch (type.toLowerCase()) {
            case 'datetime':
            case 'date':
            case 'timestamp':
                try {
                    const date = new Date(value);
                    return date.toLocaleString('fa-IR');
                } catch (e) {
                    return value;
                }
                
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
            case 'decimal':
            case 'numeric':
            case 'float':
            case 'real':
                return this.formatNumber(value);
                
            case 'bit':
                return value === 1 || value === true ? '✓' : '✗';
                
            default:
                return String(value);
        }
    }
    
    /**
     * تعیین کلاس CSS برای سلول بر اساس نوع داده
     */
    getCellClass(value, type) {
        let classes = [];
        
        if (value === null || value === undefined) {
            classes.push('null-cell');
        }
        
        switch (type.toLowerCase()) {
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
            case 'decimal':
            case 'numeric':
            case 'float':
            case 'real':
                classes.push('number-cell');
                break;
                
            case 'datetime':
            case 'date':
            case 'timestamp':
                classes.push('date-cell');
                break;
                
            case 'bit':
                classes.push('bit-cell');
                break;
                
            case 'varchar':
            case 'nvarchar':
            case 'char':
            case 'nchar':
            case 'text':
            case 'ntext':
                classes.push('text-cell');
                break;
        }
        
        return classes.join(' ');
    }
    
    /**
     * فرمت کردن اعداد
     */
    formatNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '';
        return new Intl.NumberFormat('fa-IR').format(num);
    }
    
    /**
     * تنظیم حالت در حال بارگذاری
     */
    setLoading(isLoading) {
        this.state.loading = isLoading;
        document.body.classList.toggle('sql-server-loading', isLoading);
        
        const loadingOverlay = document.querySelector('.sql-server-loading-overlay');
        if (isLoading && !loadingOverlay) {
            const overlay = document.createElement('div');
            overlay.className = 'sql-server-loading-overlay';
            overlay.innerHTML = '<div class="sql-server-spinner"></div>';
            document.body.appendChild(overlay);
        } else if (!isLoading && loadingOverlay) {
            loadingOverlay.remove();
        }
    }
    
    /**
     * نمایش پیام موفقیت
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    /**
     * نمایش خطا
     */
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    /**
     * نمایش اعلان
     */
    showNotification(message, type = 'info') {
        // حذف اعلان‌های قبلی از همان نوع
        document.querySelectorAll(`.sql-server-notification.${type}`).forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `sql-server-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // دکمه بستن
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.add('closing');
            setTimeout(() => notification.remove(), 300);
        });
        
        // نمایش با انیمیشن
        setTimeout(() => notification.classList.add('visible'), 10);
        
        // بستن خودکار
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.add('closing');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// راه‌اندازی نمونه در زمان بارگذاری صفحه
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('sql-server-table-viewer')) {
        window.tableViewer = new SQLServerTableViewer({
            container: 'sql-server-table-viewer',
            pageSize: 50,
            apiBase: '?action=api&endpoint='
        });
    }
});

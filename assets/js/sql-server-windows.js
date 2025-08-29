/**
 * SQL Server Table Viewer for Windows Simple Dashboard
 * بهینه شده برای نسخه ساده ویندوز
 */

class SQLServerTableViewer {
    constructor(options = {}) {
        this.options = {
            container: options.container || 'sql-server-container',
            pageSize: options.pageSize || 25,
            apiEndpoint: options.apiEndpoint || 'windows-simple.php?action=sqlserver',
            rtl: options.rtl !== undefined ? options.rtl : true
        };
        
        this.state = {
            tables: [],
            currentTable: null,
            columns: [],
            data: [],
            filteredData: [],
            currentPage: 1,
            totalPages: 1,
            filter: '',
            loading: false,
            error: null
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
            <div class="sql-server-header">
                <h2>مدیریت داده‌های SQL Server</h2>
                <div class="sql-server-controls">
                    <button id="sql-refresh-btn" class="sql-server-btn">🔄 بروزرسانی</button>
                    <button id="sql-export-btn" class="sql-server-btn">📊 خروجی Excel</button>
                </div>
            </div>
            
            <div class="sql-server-tabs">
                <div class="sql-server-tab active" data-tab="tables">جداول</div>
                <div class="sql-server-tab" data-tab="data">داده‌ها</div>
                <div class="sql-server-tab" data-tab="query">پرس‌وجو</div>
            </div>
            
            <div class="sql-server-content">
                <div class="sql-server-controls">
                    <select id="sql-table-selector" class="sql-server-selector">
                        <option value="">انتخاب جدول...</option>
                    </select>
                    <input type="text" id="sql-table-filter" class="sql-server-filter" placeholder="جستجو در داده‌ها...">
                </div>
                
                <div id="sql-server-table-container" class="sql-server-table-container">
                    <table class="sql-server-table" id="sql-server-table">
                        <thead id="sql-table-header">
                            <tr>
                                <th>لطفاً یک جدول انتخاب کنید</th>
                            </tr>
                        </thead>
                        <tbody id="sql-table-body">
                            <tr>
                                <td>منتظر انتخاب جدول...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="sql-server-pagination" class="sql-server-pagination"></div>
                <div id="sql-server-info" class="sql-server-info"></div>
                <div id="sql-server-error" class="sql-server-error" style="display: none;"></div>
            </div>
        `;
    }
    
    /**
     * اتصال رویدادها
     */
    bindEvents() {
        const container = document.getElementById(this.options.container);
        if (!container) return;
        
        // Table selector
        const tableSelector = document.getElementById('sql-table-selector');
        if (tableSelector) {
            tableSelector.addEventListener('change', () => {
                const tableName = tableSelector.value;
                if (tableName) {
                    this.loadTableData(tableName);
                }
            });
        }
        
        // Filter input
        const filterInput = document.getElementById('sql-table-filter');
        if (filterInput) {
            filterInput.addEventListener('input', () => {
                this.state.filter = filterInput.value;
                this.filterData();
                this.renderTable();
            });
        }
        
        // Refresh button
        const refreshBtn = document.getElementById('sql-refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshData();
            });
        }
        
        // Export button
        const exportBtn = document.getElementById('sql-export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportToExcel();
            });
        }
        
        // Tabs
        const tabs = container.querySelectorAll('.sql-server-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                // TODO: Implement tab switching logic
            });
        });
    }
    
    /**
     * بارگذاری لیست جداول
     */
    loadTables() {
        this.setState({ loading: true, error: null });
        this.showLoader();
        
        fetch(`${this.options.apiEndpoint}&query=tables`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    this.setState({ 
                        loading: false, 
                        error: data.error 
                    });
                    this.showError(data.error);
                } else {
                    this.setState({ 
                        tables: data.tables || [],
                        loading: false
                    });
                    this.populateTableSelector();
                }
            })
            .catch(error => {
                this.setState({ 
                    loading: false, 
                    error: `خطا در بارگذاری جداول: ${error.message}` 
                });
                this.showError(`خطا در بارگذاری جداول: ${error.message}`);
            });
    }
    
    /**
     * پر کردن انتخاب‌گر جدول
     */
    populateTableSelector() {
        const selector = document.getElementById('sql-table-selector');
        if (!selector) return;
        
        // Clear existing options except the first one
        while (selector.options.length > 1) {
            selector.remove(1);
        }
        
        // Add table options
        this.state.tables.forEach(table => {
            const option = document.createElement('option');
            option.value = table;
            option.textContent = table;
            selector.appendChild(option);
        });
        
        // Hide loader
        this.hideLoader();
    }
    
    /**
     * بارگذاری داده‌های جدول
     */
    loadTableData(tableName) {
        this.setState({ 
            currentTable: tableName,
            loading: true,
            error: null,
            filter: '',
            currentPage: 1
        });
        
        // Reset filter input
        const filterInput = document.getElementById('sql-table-filter');
        if (filterInput) {
            filterInput.value = '';
        }
        
        this.showLoader();
        
        fetch(`${this.options.apiEndpoint}&query=tableData&table=${encodeURIComponent(tableName)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    this.setState({ 
                        loading: false, 
                        error: data.error 
                    });
                    this.showError(data.error);
                } else {
                    this.setState({ 
                        columns: data.columns || [],
                        data: data.data || [],
                        filteredData: data.data || [],
                        loading: false,
                        totalPages: Math.ceil((data.data || []).length / this.options.pageSize)
                    });
                    this.renderTable();
                    this.renderPagination();
                    this.updateInfo();
                }
            })
            .catch(error => {
                this.setState({ 
                    loading: false, 
                    error: `خطا در بارگذاری داده‌ها: ${error.message}` 
                });
                this.showError(`خطا در بارگذاری داده‌ها: ${error.message}`);
            });
    }
    
    /**
     * فیلتر کردن داده‌ها
     */
    filterData() {
        const { data, filter } = this.state;
        
        if (!filter) {
            this.setState({ 
                filteredData: data,
                currentPage: 1,
                totalPages: Math.ceil(data.length / this.options.pageSize)
            });
            return;
        }
        
        const lowerFilter = filter.toLowerCase();
        const filteredData = data.filter(row => {
            return Object.values(row).some(value => {
                return value !== null && 
                       value.toString().toLowerCase().includes(lowerFilter);
            });
        });
        
        this.setState({ 
            filteredData,
            currentPage: 1,
            totalPages: Math.ceil(filteredData.length / this.options.pageSize)
        });
    }
    
    /**
     * رندر جدول
     */
    renderTable() {
        const { columns, filteredData, currentPage } = this.state;
        const headerEl = document.getElementById('sql-table-header');
        const bodyEl = document.getElementById('sql-table-body');
        
        if (!headerEl || !bodyEl) return;
        
        // Render header
        let headerHtml = '<tr>';
        columns.forEach(column => {
            headerHtml += `<th>${column}</th>`;
        });
        headerHtml += '</tr>';
        headerEl.innerHTML = headerHtml;
        
        // Render body
        if (filteredData.length === 0) {
            bodyEl.innerHTML = '<tr><td colspan="' + columns.length + '">داده‌ای یافت نشد</td></tr>';
            return;
        }
        
        const start = (currentPage - 1) * this.options.pageSize;
        const end = Math.min(start + this.options.pageSize, filteredData.length);
        const pageData = filteredData.slice(start, end);
        
        let bodyHtml = '';
        pageData.forEach(row => {
            bodyHtml += '<tr>';
            columns.forEach(column => {
                const value = row[column] !== null ? row[column] : '';
                bodyHtml += `<td>${value}</td>`;
            });
            bodyHtml += '</tr>';
        });
        
        bodyEl.innerHTML = bodyHtml;
        this.hideLoader();
    }
    
    /**
     * رندر صفحه‌بندی
     */
    renderPagination() {
        const { currentPage, totalPages } = this.state;
        const paginationEl = document.getElementById('sql-server-pagination');
        
        if (!paginationEl) return;
        
        if (totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">قبلی</button>`;
        
        // Page numbers
        const maxPages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
        let endPage = Math.min(totalPages, startPage + maxPages - 1);
        
        if (endPage - startPage + 1 < maxPages) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<button class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        paginationHtml += `<button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">بعدی</button>`;
        
        paginationEl.innerHTML = paginationHtml;
        
        // Bind click events
        const buttons = paginationEl.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.hasAttribute('disabled')) return;
                
                const page = parseInt(button.getAttribute('data-page'));
                this.setState({ currentPage: page });
                this.renderTable();
                this.renderPagination();
                this.updateInfo();
            });
        });
    }
    
    /**
     * بروزرسانی اطلاعات
     */
    updateInfo() {
        const { filteredData, currentPage, currentTable } = this.state;
        const infoEl = document.getElementById('sql-server-info');
        
        if (!infoEl) return;
        
        const start = (currentPage - 1) * this.options.pageSize + 1;
        const end = Math.min(start + this.options.pageSize - 1, filteredData.length);
        
        infoEl.textContent = `نمایش ${start} تا ${end} از ${filteredData.length} رکورد | جدول: ${currentTable}`;
    }
    
    /**
     * بروزرسانی داده‌ها
     */
    refreshData() {
        const { currentTable } = this.state;
        
        if (currentTable) {
            this.loadTableData(currentTable);
        } else {
            this.loadTables();
        }
    }
    
    /**
     * خروجی اکسل
     */
    exportToExcel() {
        const { columns, filteredData, currentTable } = this.state;
        
        if (!currentTable || filteredData.length === 0) {
            this.showError('جدولی برای خروجی انتخاب نشده است.');
            return;
        }
        
        // Create CSV content
        let csv = columns.join(',') + '\n';
        
        filteredData.forEach(row => {
            const rowData = columns.map(column => {
                const value = row[column] !== null ? row[column] : '';
                // Escape quotes and wrap with quotes if contains comma
                return value.toString().includes(',') ? 
                    `"${value.toString().replace(/"/g, '""')}"` : 
                    value;
            });
            csv += rowData.join(',') + '\n';
        });
        
        // Create download link
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `${currentTable}_export.csv`);
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * نمایش لودر
     */
    showLoader() {
        const tableContainer = document.getElementById('sql-server-table-container');
        if (!tableContainer) return;
        
        // Check if loader already exists
        if (document.getElementById('sql-loader')) return;
        
        const loader = document.createElement('div');
        loader.id = 'sql-loader';
        loader.className = 'sql-server-loader';
        loader.innerHTML = '<div class="loader"></div>';
        
        tableContainer.appendChild(loader);
        
        // Hide table while loading
        const table = document.getElementById('sql-server-table');
        if (table) {
            table.style.display = 'none';
        }
    }
    
    /**
     * مخفی کردن لودر
     */
    hideLoader() {
        const loader = document.getElementById('sql-loader');
        if (loader) {
            loader.remove();
        }
        
        // Show table after loading
        const table = document.getElementById('sql-server-table');
        if (table) {
            table.style.display = 'table';
        }
    }
    
    /**
     * نمایش خطا
     */
    showError(message) {
        const errorEl = document.getElementById('sql-server-error');
        if (!errorEl) return;
        
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            errorEl.style.display = 'none';
        }, 5000);
    }
    
    /**
     * تنظیم وضعیت
     */
    setState(newState) {
        this.state = { ...this.state, ...newState };
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if container exists
    if (document.getElementById('sql-server-container')) {
        window.sqlViewer = new SQLServerTableViewer();
    }
});

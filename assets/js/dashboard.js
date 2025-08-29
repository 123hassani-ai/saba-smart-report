/**
 * Dashboard JavaScript Module
 * ماژول جاوا اسکریپت داشبورد
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class Dashboard {
    constructor() {
        this.apiBase = 'api/';
        this.refreshInterval = 30000; // 30 seconds
        this.charts = {};
        this.init();
    }

    /**
     * مقداردهی اولیه داشبورد
     */
    init() {
        this.bindEvents();
        this.loadDashboardData();
        this.startAutoRefresh();
        this.initializeCharts();
    }

    /**
     * اتصال رویدادها
     */
    bindEvents() {
        // دکمه رفرش
        document.getElementById('refresh-btn')?.addEventListener('click', () => {
            this.loadDashboardData();
        });

        // دکمه تنظیمات
        document.getElementById('settings-btn')?.addEventListener('click', () => {
            this.showSettings();
        });

        // دکمه شروع همگام‌سازی
        document.getElementById('sync-btn')?.addEventListener('click', () => {
            this.startSync();
        });

        // تغییر تم
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });
    }

    /**
     * بارگذاری داده‌های داشبورد
     */
    async loadDashboardData() {
        try {
            this.showLoading(true);
            
            const response = await this.apiCall('dashboard/stats');
            
            if (response.success) {
                this.updateDashboardUI(response.data);
            } else {
                this.showError('خطا در بارگذاری داده‌ها: ' + response.message);
            }
            
        } catch (error) {
            this.showError('خطا در ارتباط با سرور: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * به‌روزرسانی رابط کاربری داشبورد
     */
    updateDashboardUI(data) {
        // آپدیت آمار کلی
        this.updateElement('total-records', data.totalRecords || 0);
        this.updateElement('synced-today', data.syncedToday || 0);
        this.updateElement('pending-sync', data.pendingSync || 0);
        this.updateElement('last-sync', this.formatDate(data.lastSync));

        // آپدیت وضعیت اتصالات
        this.updateConnectionStatus('sql-server-status', data.sqlServerStatus);
        this.updateConnectionStatus('cloud-status', data.cloudStatus);

        // آپدیت جداول اخیر
        this.updateRecentTables(data.recentTables || []);

        // آپدیت نمودارها
        this.updateCharts(data.chartData || {});

        // آپدیت لاگ‌ها
        this.updateLogs(data.recentLogs || []);
    }

    /**
     * آپدیت وضعیت اتصال
     */
    updateConnectionStatus(elementId, status) {
        const element = document.getElementById(elementId);
        if (!element) return;

        element.className = 'status-indicator';
        
        if (status === 'connected') {
            element.classList.add('status-success');
            element.textContent = 'متصل';
        } else {
            element.classList.add('status-error');
            element.textContent = 'قطع';
        }
    }

    /**
     * آپدیت جداول اخیر
     */
    updateRecentTables(tables) {
        const container = document.getElementById('recent-tables');
        if (!container) return;

        container.innerHTML = '';
        
        tables.forEach(table => {
            const tableRow = document.createElement('div');
            tableRow.className = 'table-row';
            tableRow.innerHTML = `
                <div class="table-info">
                    <strong>${table.name}</strong>
                    <span class="table-count">${table.count} رکورد</span>
                </div>
                <div class="table-status">
                    <span class="status-badge ${table.status === 'synced' ? 'success' : 'warning'}">
                        ${table.status === 'synced' ? 'همگام‌سازی شده' : 'در انتظار'}
                    </span>
                </div>
            `;
            container.appendChild(tableRow);
        });
    }

    /**
     * آپدیت لاگ‌ها
     */
    updateLogs(logs) {
        const container = document.getElementById('recent-logs');
        if (!container) return;

        container.innerHTML = '';
        
        logs.forEach(log => {
            const logItem = document.createElement('div');
            logItem.className = `log-item log-${log.level}`;
            logItem.innerHTML = `
                <div class="log-time">${this.formatTime(log.timestamp)}</div>
                <div class="log-message">${log.message}</div>
            `;
            container.appendChild(logItem);
        });
    }

    /**
     * مقداردهی نمودارها
     */
    initializeCharts() {
        // نمودار همگام‌سازی روزانه
        const syncChart = document.getElementById('sync-chart');
        if (syncChart && typeof Chart !== 'undefined') {
            this.charts.sync = new Chart(syncChart, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'رکوردهای همگام‌سازی شده',
                        data: [],
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    /**
     * آپدیت نمودارها
     */
    updateCharts(chartData) {
        if (this.charts.sync && chartData.sync) {
            this.charts.sync.data.labels = chartData.sync.labels;
            this.charts.sync.data.datasets[0].data = chartData.sync.data;
            this.charts.sync.update();
        }
    }

    /**
     * شروع همگام‌سازی
     */
    async startSync() {
        try {
            const confirmSync = await this.showConfirm('آیا مایل به شروع همگام‌سازی هستید؟');
            if (!confirmSync) return;

            this.showLoading(true);
            
            const response = await this.apiCall('sync/start', 'POST');
            
            if (response.success) {
                this.showSuccess('همگام‌سازی با موفقیت شروع شد');
                this.monitorSyncProgress();
            } else {
                this.showError('خطا در شروع همگام‌سازی: ' + response.message);
            }
            
        } catch (error) {
            this.showError('خطا در ارتباط با سرور: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * مانیتورینگ پیشرفت همگام‌سازی
     */
    async monitorSyncProgress() {
        const progressContainer = document.getElementById('sync-progress');
        if (!progressContainer) return;

        progressContainer.style.display = 'block';
        
        const checkProgress = async () => {
            try {
                const response = await this.apiCall('sync/progress');
                
                if (response.success) {
                    const progress = response.data;
                    
                    this.updateElement('sync-progress-text', 
                        `${progress.current}/${progress.total} - ${progress.currentTable}`);
                    
                    const percentage = Math.round((progress.current / progress.total) * 100);
                    this.updateElement('sync-progress-bar', '', 'width', `${percentage}%`);
                    
                    if (progress.status === 'completed') {
                        progressContainer.style.display = 'none';
                        this.showSuccess('همگام‌سازی با موفقیت تکمیل شد');
                        this.loadDashboardData();
                        return;
                    }
                    
                    if (progress.status === 'running') {
                        setTimeout(checkProgress, 2000);
                    }
                }
                
            } catch (error) {
                console.error('خطا در بررسی پیشرفت:', error);
            }
        };
        
        checkProgress();
    }

    /**
     * نمایش تنظیمات
     */
    showSettings() {
        const modal = document.getElementById('settings-modal');
        if (modal) {
            modal.style.display = 'block';
            this.loadSettings();
        }
    }

    /**
     * بارگذاری تنظیمات
     */
    async loadSettings() {
        try {
            const response = await this.apiCall('config/get');
            
            if (response.success) {
                this.populateSettingsForm(response.data);
            }
            
        } catch (error) {
            this.showError('خطا در بارگذاری تنظیمات: ' + error.message);
        }
    }

    /**
     * پر کردن فرم تنظیمات
     */
    populateSettingsForm(config) {
        // SQL Server settings
        this.setFormValue('sql-server-host', config.sql_server?.host);
        this.setFormValue('sql-server-database', config.sql_server?.database);
        this.setFormValue('sql-server-username', config.sql_server?.username);
        
        // Cloud settings
        this.setFormValue('cloud-host', config.cloud?.host);
        this.setFormValue('cloud-database', config.cloud?.database);
        this.setFormValue('cloud-username', config.cloud?.username);
        
        // Sync settings
        this.setFormValue('sync-interval', config.sync?.interval);
        this.setFormValue('batch-size', config.sync?.batch_size);
    }

    /**
     * تغییر تم
     */
    toggleTheme() {
        const body = document.body;
        const isDark = body.classList.contains('dark-theme');
        
        if (isDark) {
            body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
        } else {
            body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
        }
    }

    /**
     * شروع رفرش خودکار
     */
    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardData();
        }, this.refreshInterval);
    }

    // Helper Methods

    /**
     * فراخوانی API
     */
    async apiCall(endpoint, method = 'GET', data = null) {
        const url = this.apiBase + endpoint;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    }

    /**
     * آپدیت المنت
     */
    updateElement(id, value, attribute = 'textContent', attributeValue = null) {
        const element = document.getElementById(id);
        if (element) {
            if (attributeValue !== null) {
                element.style[attribute] = attributeValue;
            } else {
                element[attribute] = value;
            }
        }
    }

    /**
     * تنظیم مقدار فرم
     */
    setFormValue(id, value) {
        const element = document.getElementById(id);
        if (element && value !== undefined) {
            element.value = value;
        }
    }

    /**
     * فرمت تاریخ
     */
    formatDate(timestamp) {
        if (!timestamp) return 'هرگز';
        const date = new Date(timestamp);
        return date.toLocaleDateString('fa-IR') + ' ' + date.toLocaleTimeString('fa-IR');
    }

    /**
     * فرمت زمان
     */
    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return date.toLocaleTimeString('fa-IR');
    }

    /**
     * نمایش لودینگ
     */
    showLoading(show) {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
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
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    /**
     * نمایش تأیید
     */
    showConfirm(message) {
        return new Promise((resolve) => {
            const result = confirm(message);
            resolve(result);
        });
    }
}

// مقداردهی داشبورد پس از بارگذاری صفحه
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new Dashboard();
    
    // بارگذاری تم ذخیره شده
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
});

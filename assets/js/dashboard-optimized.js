/**
 * Dashboard JavaScript Module - Optimized Version
 * ماژول جاوا اسکریپت داشبورد - نسخه بهینه شده
 * 
 * @author Saba Reporting System
 * @version 2.1 - Optimized
 */

class Dashboard {
    constructor() {
        this.apiBase = '?action=api&endpoint=';
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
            window.open('settings.php', '_blank');
        });

        // دکمه تست اتصالات
        document.getElementById('test-connections-btn')?.addEventListener('click', () => {
            this.testConnections();
        });
    }

    /**
     * بارگذاری سریع داده‌های داشبورد
     */
    async loadDashboardData() {
        try {
            this.showLoading(true);
            
            // ابتدا وضعیت اتصالات را بررسی کن
            const statusResponse = await this.apiCall('status');
            if (statusResponse.success) {
                this.updateConnectionStatus(statusResponse.data);
            }
            
            // سپس جداول را بدون COUNT بار کن (سریع)
            const tablesResponse = await this.apiCall('tables');
            if (tablesResponse.success) {
                this.updateTablesUI(tablesResponse.data);
            }
            
        } catch (error) {
            this.showError('خطا در بارگذاری داده‌ها: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * به‌روزرسانی وضعیت اتصالات
     */
    updateConnectionStatus(statusData) {
        // SQL Server Status
        this.updateStatusCard('sql-server-status', {
            status: statusData.sql_server_connected ? 'connected' : 'disconnected',
            details: statusData.sql_server_info || {}
        });

        // Cloud Database Status  
        this.updateStatusCard('cloud-status', {
            status: statusData.cloud_connected ? 'connected' : 'disconnected',
            details: statusData.cloud_info || {}
        });
    }

    /**
     * آپدیت کارت وضعیت
     */
    updateStatusCard(cardId, data) {
        const card = document.getElementById(cardId);
        if (!card) return;

        const isConnected = data.status === 'connected';
        
        // آپدیت وضعیت نمایشی
        const statusElement = card.querySelector('.status-indicator');
        if (statusElement) {
            statusElement.className = `status-indicator ${isConnected ? 'connected' : 'disconnected'}`;
            statusElement.textContent = isConnected ? 'متصل' : 'قطع';
        }

        // آپدیت جزئیات
        const detailsElement = card.querySelector('.connection-details');
        if (detailsElement && data.details) {
            let detailsHtml = '';
            Object.entries(data.details).forEach(([key, value]) => {
                detailsHtml += `<div class="detail-item">${key}: ${value}</div>`;
            });
            detailsElement.innerHTML = detailsHtml;
        }
    }

    /**
     * آپدیت رابط کاربری جداول (نسخه بهینه شده)
     */
    updateTablesUI(tables) {
        const container = document.getElementById('tables-container');
        if (!container) return;

        if (!tables || tables.length === 0) {
            container.innerHTML = '<div class="no-data">جدولی یافت نشد</div>';
            return;
        }

        let html = '<div class="tables-grid">';
        
        tables.forEach(table => {
            const recordsDisplay = table.records === '?' 
                ? `<span class="record-count loading" data-table="${table.name}">نامشخص</span>`
                : `<span class="record-count">${this.formatNumber(table.records)} رکورد</span>`;
                
            html += `
                <div class="table-card" data-table="${table.name}">
                    <div class="table-header">
                        <div class="table-name">${table.name}</div>
                        <button class="btn-count load-count" data-table="${table.name}" 
                                style="${table.records !== '?' ? 'display:none' : ''}"
                                title="محاسبه تعداد رکوردها">
                            📊
                        </button>
                    </div>
                    <div class="table-info">
                        ${recordsDisplay}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // اتصال رویداد برای دکمه‌های بارگذاری تعداد
        container.querySelectorAll('.load-count').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadTableCount(e.target.dataset.table);
            });
        });

        // نمایش تعداد کل جداول
        const totalElement = document.getElementById('total-tables');
        if (totalElement) {
            totalElement.textContent = tables.length;
        }
    }

    /**
     * بارگذاری تعداد رکوردهای یک جدول خاص (با optimization)
     */
    async loadTableCount(tableName) {
        const btn = document.querySelector(`[data-table="${tableName}"].load-count`);
        const countSpan = document.querySelector(`[data-table="${tableName}"].record-count`);
        
        try {
            // نمایش حالت در حال بارگذاری
            if (btn) {
                btn.textContent = '⏳';
                btn.disabled = true;
            }
            if (countSpan) {
                countSpan.textContent = 'در حال محاسبه...';
                countSpan.classList.add('loading');
            }
            
            const response = await this.apiCall(`table_count&table=${encodeURIComponent(tableName)}`);
            
            if (response.success) {
                if (countSpan) {
                    countSpan.textContent = `${this.formatNumber(response.count)} رکورد`;
                    countSpan.classList.remove('loading');
                }
                if (btn) {
                    btn.style.display = 'none';
                }
            } else {
                throw new Error(response.error || 'خطا در محاسبه');
            }
            
        } catch (error) {
            console.error('Error loading table count:', error);
            
            if (countSpan) {
                countSpan.textContent = 'خطا در محاسبه';
                countSpan.classList.remove('loading');
                countSpan.classList.add('error');
            }
            if (btn) {
                btn.textContent = '🔄';
                btn.disabled = false;
                btn.title = 'تلاش مجدد';
            }
        }
    }

    /**
     * تست تمام اتصالات
     */
    async testConnections() {
        try {
            this.showLoading(true);
            
            const response = await this.apiCall('test_connections');
            
            if (response.success) {
                this.showSuccess('تست اتصالات با موفقیت انجام شد');
                this.loadDashboardData(); // رفرش داده‌ها
            } else {
                this.showError('مشکل در تست اتصالات: ' + response.message);
            }
            
        } catch (error) {
            this.showError('خطا در تست اتصالات: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * شروع رفرش خودکار (با مدیریت بهتر)
     */
    startAutoRefresh() {
        // پاکسازی interval قبلی در صورت وجود
        if (this.refreshInterval) {
            clearInterval(this.refreshIntervalId);
        }

        this.refreshIntervalId = setInterval(() => {
            // فقط اگر صفحه در حال نمایش باشد رفرش کن
            if (!document.hidden) {
                this.loadDashboardData();
            }
        }, this.refreshInterval);

        // مکث در رفرش هنگام خروج از صفحه
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(this.refreshIntervalId);
            } else {
                this.startAutoRefresh();
            }
        });
    }

    // Utility Methods

    /**
     * فراخوانی API
     */
    async apiCall(endpoint) {
        const url = this.apiBase + endpoint;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * فرمت عدد
     */
    formatNumber(num) {
        if (!num || num === '?') return '0';
        return Number(num).toLocaleString('fa-IR');
    }

    /**
     * نمایش لودینگ
     */
    showLoading(show) {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }

        // اضافه کردن class loading به body
        document.body.classList.toggle('loading', show);
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
     * نمایش اعلان (بهینه شده)
     */
    showNotification(message, type = 'info') {
        // حذف اعلان‌های قبلی
        document.querySelectorAll('.notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // انیمیشن نمایش
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // حذف خودکار بعد از 5 ثانیه
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// مقداردهی داشبورد پس از بارگذاری صفحه
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new Dashboard();
    
    console.log('🚀 Saba Dashboard Optimized v2.1 Loaded');
});

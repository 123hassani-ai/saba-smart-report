<?php
require_once __DIR__ . '/../BaseModule.php';
require_once __DIR__ . '/../database/SQLServerConnection.php';
require_once __DIR__ . '/../database/CloudConnection.php';

/**
 * Sync Manager - مدیریت همگام‌سازی
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class SyncManager extends BaseModule 
{
    private $sqlConnection;
    private $cloudConnection;
    private $syncHistoryFile;
    private $isRunning = false;
    
    public function __construct($config = null) 
    {
        parent::__construct($config);
        
        $this->sqlConnection = new SQLServerConnection($config);
        $this->cloudConnection = new CloudConnection($config);
        $this->syncHistoryFile = __DIR__ . '/../../logs/sync_history.json';
    }
    
    /**
     * همگام‌سازی جداول انتخاب شده
     */
    public function syncTables($tableNames = []) 
    {
        try {
            if ($this->isRunning) {
                throw new Exception("همگام‌سازی در حال انجام است");
            }
            
            $this->isRunning = true;
            $this->log("شروع همگام‌سازی جداول: " . implode(', ', $tableNames));
            
            $results = [
                'total_tables' => count($tableNames),
                'successful' => 0,
                'failed' => 0,
                'total_records' => 0,
                'start_time' => date('Y-m-d H:i:s'),
                'tables' => []
            ];
            
            foreach ($tableNames as $tableName) {
                try {
                    $tableResult = $this->syncSingleTable($tableName);
                    $results['tables'][$tableName] = $tableResult;
                    
                    if ($tableResult['success']) {
                        $results['successful']++;
                        $results['total_records'] += $tableResult['records_count'];
                    } else {
                        $results['failed']++;
                    }
                    
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['tables'][$tableName] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'records_count' => 0
                    ];
                    $this->log("خطا در همگام‌سازی جدول {$tableName}: " . $e->getMessage(), 'error');
                }
            }
            
            $results['end_time'] = date('Y-m-d H:i:s');
            $results['duration'] = strtotime($results['end_time']) - strtotime($results['start_time']);
            
            $this->saveSyncHistory($results);
            $this->log("پایان همگام‌سازی: {$results['successful']} موفق، {$results['failed']} ناموفق");
            
            return $results;
            
        } catch (Exception $e) {
            $this->log("خطا در همگام‌سازی: " . $e->getMessage(), 'error');
            throw $e;
        } finally {
            $this->isRunning = false;
        }
    }
    
    /**
     * همگام‌سازی تک جدول
     */
    public function syncSingleTable($tableName) 
    {
        $startTime = microtime(true);
        $this->log("شروع همگام‌سازی جدول: {$tableName}");
        
        try {
            // دریافت داده‌ها از SQL Server
            $sourceData = $this->sqlConnection->getTableData($tableName);
            
            if (empty($sourceData)) {
                $this->log("جدول {$tableName} خالی است");
                return [
                    'success' => true,
                    'records_count' => 0,
                    'message' => 'جدول خالی',
                    'duration' => microtime(true) - $startTime
                ];
            }
            
            // ایجاد جدول در Cloud اگر وجود ندارد
            $this->cloudConnection->createTable($tableName, $sourceData[0]);
            
            // درج داده‌ها به صورت batch
            $batchSize = $this->config['settings']['batch_size'] ?? 1000;
            $recordsCount = $this->cloudConnection->insertBatch($tableName, $sourceData, $batchSize);
            
            $duration = microtime(true) - $startTime;
            $this->log("✅ همگام‌سازی {$tableName} کامل شد: {$recordsCount} رکورد در " . round($duration, 2) . " ثانیه");
            
            return [
                'success' => true,
                'records_count' => $recordsCount,
                'duration' => $duration,
                'message' => 'موفقیت‌آمیز'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->log("❌ خطا در همگام‌سازی {$tableName}: " . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'records_count' => 0,
                'duration' => $duration,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * همگام‌سازی تدریجی (Incremental)
     */
    public function incrementalSync($tableName, $timestampColumn = 'updated_at') 
    {
        try {
            $lastSync = $this->getLastSyncTimestamp($tableName);
            
            if ($lastSync) {
                $this->log("همگام‌سازی تدریجی {$tableName} از تاریخ: {$lastSync}");
                
                // فقط رکوردهای جدید یا بروزرسانی شده
                $query = "SELECT * FROM [{$tableName}] WHERE [{$timestampColumn}] > ?";
                // این بخش نیاز به پیاده‌سازی کامل‌تر دارد
            } else {
                // همگام‌سازی کامل
                return $this->syncSingleTable($tableName);
            }
            
        } catch (Exception $e) {
            $this->log("خطا در همگام‌سازی تدریجی: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * دریافت تاریخ آخرین همگام‌سازی
     */
    private function getLastSyncTimestamp($tableName) 
    {
        $history = $this->getSyncHistory();
        
        foreach (array_reverse($history) as $entry) {
            if (isset($entry['tables'][$tableName]) && $entry['tables'][$tableName]['success']) {
                return $entry['end_time'];
            }
        }
        
        return null;
    }
    
    /**
     * ذخیره تاریخچه همگام‌سازی
     */
    private function saveSyncHistory($result) 
    {
        try {
            $history = $this->getSyncHistory();
            $history[] = $result;
            
            // نگه‌داری فقط آخرین 50 رکورد
            $history = array_slice($history, -50);
            
            if (!file_exists(dirname($this->syncHistoryFile))) {
                mkdir(dirname($this->syncHistoryFile), 0755, true);
            }
            
            file_put_contents(
                $this->syncHistoryFile, 
                json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            
        } catch (Exception $e) {
            $this->log("خطا در ذخیره تاریخچه: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * دریافت تاریخچه همگام‌سازی
     */
    public function getSyncHistory($limit = 20) 
    {
        try {
            if (!file_exists($this->syncHistoryFile)) {
                return [];
            }
            
            $content = file_get_contents($this->syncHistoryFile);
            $history = json_decode($content, true) ?? [];
            
            return array_slice(array_reverse($history), 0, $limit);
            
        } catch (Exception $e) {
            $this->log("خطا در خواندن تاریخچه: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * دریافت آمار همگام‌سازی
     */
    public function getSyncStats() 
    {
        try {
            $history = $this->getSyncHistory(100);
            
            $stats = [
                'total_syncs' => count($history),
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'total_records' => 0,
                'last_sync' => null,
                'avg_duration' => 0,
                'most_synced_tables' => []
            ];
            
            $durations = [];
            $tableCounts = [];
            
            foreach ($history as $entry) {
                if ($entry['successful'] > 0) {
                    $stats['successful_syncs']++;
                } else {
                    $stats['failed_syncs']++;
                }
                
                $stats['total_records'] += $entry['total_records'];
                $durations[] = $entry['duration'];
                
                if (!$stats['last_sync'] || $entry['end_time'] > $stats['last_sync']) {
                    $stats['last_sync'] = $entry['end_time'];
                }
                
                // شمارش جداول
                foreach ($entry['tables'] as $tableName => $result) {
                    if ($result['success']) {
                        $tableCounts[$tableName] = ($tableCounts[$tableName] ?? 0) + 1;
                    }
                }
            }
            
            if (!empty($durations)) {
                $stats['avg_duration'] = array_sum($durations) / count($durations);
            }
            
            // مرتب‌سازی جداول بر اساس تعداد همگام‌سازی
            arsort($tableCounts);
            $stats['most_synced_tables'] = array_slice($tableCounts, 0, 10, true);
            
            return $stats;
            
        } catch (Exception $e) {
            $this->log("خطا در محاسبه آمار: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * تست اتصالات
     */
    public function testConnections() 
    {
        $results = [
            'sql_server' => false,
            'cloud' => false,
            'messages' => []
        ];
        
        try {
            // تست SQL Server
            if ($this->sqlConnection->testConnection()) {
                $results['sql_server'] = true;
                $results['messages'][] = "✅ اتصال SQL Server موفق";
            } else {
                $results['messages'][] = "❌ اتصال SQL Server ناموفق";
            }
        } catch (Exception $e) {
            $results['messages'][] = "❌ خطا در SQL Server: " . $e->getMessage();
        }
        
        try {
            // تست Cloud Database
            if ($this->cloudConnection->testConnection()) {
                $results['cloud'] = true;
                $results['messages'][] = "✅ اتصال پایگاه داده ابری موفق";
            } else {
                $results['messages'][] = "❌ اتصال پایگاه داده ابری ناموفق";
            }
        } catch (Exception $e) {
            $results['messages'][] = "❌ خطا در Cloud Database: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * متوقف کردن همگام‌سازی
     */
    public function stopSync() 
    {
        $this->isRunning = false;
        $this->log("همگام‌سازی متوقف شد");
    }
    
    /**
     * وضعیت همگام‌سازی
     */
    public function getStatus() 
    {
        return [
            'is_running' => $this->isRunning,
            'last_sync' => $this->getLastSyncTime(),
            'connections' => [
                'sql_server' => $this->sqlConnection->getConnectionInfo(),
                'cloud' => $this->cloudConnection->getConnectionInfo()
            ]
        ];
    }
    
    /**
     * همگام‌سازی تمام جداول
     */
    public function syncAllTables() 
    {
        try {
            // دریافت لیست جداول از SQL Server
            $tables = $this->sqlConnection->getTables();
            $tableNames = array_column($tables, 'name');
            
            $this->log("شروع همگام‌سازی تمام جداول - تعداد: " . count($tableNames));
            
            return $this->syncTables($tableNames);
            
        } catch (Exception $e) {
            $this->log("خطا در همگام‌سازی تمام جداول: " . $e->getMessage(), 'ERROR');
            return $this->error("خطا در همگام‌سازی: " . $e->getMessage());
        }
    }
    
    /**
     * دریافت آمار کلی سیستم
     */
    public function getStats() 
    {
        try {
            $history = $this->getSyncHistory(10);
            
            // آمار کلی
            $totalSyncs = count($history);
            $successfulSyncs = count(array_filter($history, function($h) { 
                return $h['status'] === 'completed'; 
            }));
            
            // آمار جداول
            $sqlTables = $this->sqlConnection->getTables();
            $cloudTables = $this->cloudConnection->getTables();
            
            return [
                'sync_stats' => [
                    'total_syncs' => $totalSyncs,
                    'successful_syncs' => $successfulSyncs,
                    'success_rate' => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 2) : 0,
                    'last_sync' => $this->getLastSyncTime()
                ],
                'table_stats' => [
                    'sql_server_tables' => count($sqlTables),
                    'cloud_tables' => count($cloudTables),
                    'total_records' => array_sum(array_column($sqlTables, 'records'))
                ],
                'system_status' => [
                    'sql_server_connected' => $this->sqlConnection->testConnection(),
                    'cloud_db_connected' => $this->cloudConnection->testConnection(),
                    'sync_running' => $this->isRunning
                ]
            ];
            
        } catch (Exception $e) {
            $this->log("خطا در دریافت آمار: " . $e->getMessage(), 'ERROR');
            return [
                'sync_stats' => ['total_syncs' => 0, 'successful_syncs' => 0, 'success_rate' => 0],
                'table_stats' => ['sql_server_tables' => 0, 'cloud_tables' => 0, 'total_records' => 0],
                'system_status' => ['sql_server_connected' => false, 'cloud_db_connected' => false, 'sync_running' => false]
            ];
        }
    }
    
    /**
     * دریافت زمان آخرین همگام‌سازی
     */
    private function getLastSyncTime() 
    {
        $history = $this->getSyncHistory(1);
        return !empty($history) ? $history[0]['end_time'] : null;
    }
}

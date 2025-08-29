<?php
require_once __DIR__ . '/../BaseModule.php';

/**
 * Cloud Database Connection Manager  
 * مدیریت اتصال به پایگاه داده ابری (MySQL/MariaDB)
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class CloudConnection extends BaseModule 
{
    private $connection;
    private $connectionInfo;
    
    public function __construct($config = null) 
    {
        parent::__construct($config);
        $this->connectionInfo = $this->config['cloud'] ?? [];
    }
    
    /**
     * برقراری اتصال به پایگاه داده ابری
     */
    public function connect() 
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $this->connectionInfo['host'],
                $this->connectionInfo['port'] ?? '3306',
                $this->connectionInfo['database']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO(
                $dsn,
                $this->connectionInfo['username'],
                $this->connectionInfo['password'],
                $options
            );
            
            $this->log("✅ اتصال موفق به پایگاه داده ابری");
            return true;
            
        } catch (PDOException $e) {
            $this->log("❌ خطا در اتصال به پایگاه داده ابری: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * تست اتصال
     */
    public function testConnection() 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            $stmt = $this->connection->query("SELECT 1 AS test");
            $result = $stmt->fetch();
            
            return $result['test'] == 1;
            
        } catch (Exception $e) {
            $this->log("تست اتصال ناموفق: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * ایجاد جدول در پایگاه داده ابری
     */
    public function createTable($tableName, $sampleRecord) 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            // بررسی وجود جدول
            if ($this->tableExists($tableName)) {
                $this->log("جدول {$tableName} از قبل موجود است");
                return true;
            }
            
            $columns = $this->analyzeRecordStructure($sampleRecord);
            $createSQL = $this->generateCreateTableSQL($tableName, $columns);
            
            $this->connection->exec($createSQL);
            $this->log("✅ جدول {$tableName} ایجاد شد");
            
            return true;
            
        } catch (Exception $e) {
            $this->log("خطا در ایجاد جدول {$tableName}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * بررسی وجود جدول
     */
    private function tableExists($tableName) 
    {
        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * تجزیه ساختار رکورد
     */
    private function analyzeRecordStructure($record) 
    {
        $columns = [];
        
        foreach ($record as $key => $value) {
            $type = $this->detectColumnType($value);
            $columns[$key] = [
                'name' => $key,
                'type' => $type,
                'null' => 'YES'
            ];
        }
        
        return $columns;
    }
    
    /**
     * تشخیص نوع ستون بر اساس مقدار
     */
    private function detectColumnType($value) 
    {
        if ($value === null) {
            return 'TEXT';
        }
        
        if (is_int($value)) {
            return 'INT';
        }
        
        if (is_float($value)) {
            return 'DECIMAL(15,4)';
        }
        
        if (is_bool($value)) {
            return 'TINYINT(1)';
        }
        
        if (is_string($value)) {
            $length = strlen($value);
            
            if ($length <= 255) {
                return 'VARCHAR(255)';
            } elseif ($length <= 65535) {
                return 'TEXT';
            } else {
                return 'LONGTEXT';
            }
        }
        
        // تشخیص تاریخ
        if (is_string($value) && $this->isDateTime($value)) {
            return 'DATETIME';
        }
        
        return 'TEXT';
    }
    
    /**
     * بررسی فرمت تاریخ/زمان
     */
    private function isDateTime($value) 
    {
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d',
            'H:i:s',
            'd/m/Y',
            'm/d/Y'
        ];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * تولید SQL برای ایجاد جدول
     */
    private function generateCreateTableSQL($tableName, $columns) 
    {
        $columnDefinitions = [];
        
        // اضافه کردن ID خودکار
        $columnDefinitions[] = "id INT AUTO_INCREMENT PRIMARY KEY";
        
        foreach ($columns as $column) {
            $columnDefinitions[] = "`{$column['name']}` {$column['type']} {$column['null']}";
        }
        
        // اضافه کردن ستون‌های سیستمی
        $columnDefinitions[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $columnDefinitions[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        
        $sql = "CREATE TABLE `{$tableName}` (\n";
        $sql .= "    " . implode(",\n    ", $columnDefinitions) . "\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $sql;
    }
    
    /**
     * درج رکورد در جدول ابری
     */
    public function insertRecord($tableName, $record) 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            $columns = array_keys($record);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute(array_values($record));
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->log("خطا در درج رکورد در {$tableName}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * درج چندین رکورد به صورت batch
     */
    public function insertBatch($tableName, $records, $batchSize = 1000) 
    {
        try {
            if (empty($records)) {
                return 0;
            }
            
            if (!$this->connection) {
                $this->connect();
            }
            
            $this->connection->beginTransaction();
            $insertedCount = 0;
            
            $chunks = array_chunk($records, $batchSize);
            
            foreach ($chunks as $chunk) {
                $columns = array_keys($chunk[0]);
                $placeholders = array_fill(0, count($columns), '?');
                $valuePlaceholder = '(' . implode(', ', $placeholders) . ')';
                
                $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES ";
                $sql .= implode(', ', array_fill(0, count($chunk), $valuePlaceholder));
                
                $stmt = $this->connection->prepare($sql);
                
                $values = [];
                foreach ($chunk as $record) {
                    $values = array_merge($values, array_values($record));
                }
                
                $stmt->execute($values);
                $insertedCount += $stmt->rowCount();
            }
            
            $this->connection->commit();
            $this->log("✅ {$insertedCount} رکورد در جدول {$tableName} درج شد");
            
            return $insertedCount;
            
        } catch (Exception $e) {
            if ($this->connection) {
                $this->connection->rollBack();
            }
            $this->log("خطا در درج batch: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * دریافت آمار جداول ابری
     */
    public function getTablesStats() 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            $query = "
                SELECT 
                    TABLE_NAME as name,
                    TABLE_ROWS as records,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb,
                    CREATE_TIME as created_at,
                    UPDATE_TIME as updated_at
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute([$this->connectionInfo['database']]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->log("خطا در دریافت آمار جداول: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * بستن اتصال
     */
    public function close() 
    {
        $this->connection = null;
        $this->log("اتصال پایگاه داده ابری بسته شد");
    }
    
    /**
     * دریافت لیست جداول موجود
     */
    public function getTables() 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            $sql = "SHOW TABLES";
            $stmt = $this->connection->query($sql);
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $tables = [];
            foreach ($tableNames as $tableName) {
                // دریافت تعداد رکوردها
                $countSQL = "SELECT COUNT(*) FROM `{$tableName}`";
                $countStmt = $this->connection->query($countSQL);
                $recordCount = $countStmt->fetchColumn();
                
                $tables[] = [
                    'name' => $tableName,
                    'records' => (int)$recordCount,
                    'type' => 'cloud'
                ];
            }
            
            return $tables;
            
        } catch (PDOException $e) {
            $this->log("خطا در دریافت جداول: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * دریافت اطلاعات اتصال
     */
    public function getConnectionInfo() 
    {
        return [
            'host' => $this->connectionInfo['host'] ?? '',
            'database' => $this->connectionInfo['database'] ?? '',
            'username' => $this->connectionInfo['username'] ?? '',
            'port' => $this->connectionInfo['port'] ?? '3306',
            'connected' => $this->connection !== null
        ];
    }
}

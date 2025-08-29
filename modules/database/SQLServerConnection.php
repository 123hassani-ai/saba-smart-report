<?php
require_once __DIR__ . '/../BaseModule.php';

/**
 * SQL Server Connection Manager
 * مدیریت اتصال به SQL Server
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class SQLServerConnection extends BaseModule 
{
    private $connection;
    private $connectionInfo;
    
    public function __construct($config = null) 
    {
        parent::__construct($config);
        $this->connectionInfo = $this->config['sql_server'] ?? [];
    }
    
    /**
     * برقراری اتصال به SQL Server
     */
    public function connect() 
    {
        try {
            $method = $this->connectionInfo['connection_method'] ?? 'odbc';
            
            if ($method === 'com') {
                return $this->connectViaCOM();
            } else {
                return $this->connectViaODBC();
            }
            
        } catch (Exception $e) {
            $this->log("خطا در اتصال به SQL Server: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * اتصال از طریق ODBC
     */
    private function connectViaODBC() 
    {
        $isWindows = (PHP_OS_FAMILY === 'Windows');
        $isMac = (PHP_OS_FAMILY === 'Darwin');
        
        $connectionStrings = [];
        
        if ($isWindows) {
            $connectionStrings = [
                "Driver={ODBC Driver 17 for SQL Server};Server={$this->connectionInfo['server']};Database={$this->connectionInfo['database']};Uid={$this->connectionInfo['username']};Pwd={$this->connectionInfo['password']};",
                "Driver={SQL Server Native Client 11.0};Server={$this->connectionInfo['server']};Database={$this->connectionInfo['database']};Uid={$this->connectionInfo['username']};Pwd={$this->connectionInfo['password']};",
                "Driver={SQL Server};Server={$this->connectionInfo['server']};Database={$this->connectionInfo['database']};Uid={$this->connectionInfo['username']};Pwd={$this->connectionInfo['password']};",
            ];
        } elseif ($isMac) {
            $connectionStrings = [
                "DSN=SQLServer;UID={$this->connectionInfo['username']};PWD={$this->connectionInfo['password']};",
                "Driver=/opt/homebrew/lib/libtdsodbc.so;Server={$this->connectionInfo['server']},{$this->connectionInfo['port']};Database={$this->connectionInfo['database']};UID={$this->connectionInfo['username']};PWD={$this->connectionInfo['password']};TDS_Version=8.0;",
            ];
        } else {
            $connectionStrings = [
                "Driver={FreeTDS};Server={$this->connectionInfo['server']};Port={$this->connectionInfo['port']};Database={$this->connectionInfo['database']};UID={$this->connectionInfo['username']};PWD={$this->connectionInfo['password']};TDS_Version=8.0;",
            ];
        }
        
        foreach ($connectionStrings as $dsn) {
            try {
                $this->connection = new PDO("odbc:" . $dsn);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->log("✅ اتصال موفق به SQL Server از طریق ODBC در " . PHP_OS_FAMILY);
                return true;
            } catch (Exception $e) {
                $this->log("⚠️ تلاش اتصال ناموفق: " . $e->getMessage(), 'warning');
                continue;
            }
        }
        
        throw new Exception("امکان اتصال به SQL Server از طریق ODBC وجود ندارد");
    }
    
    /**
     * اتصال از طریق COM Object (ویندوز)
     */
    private function connectViaCOM() 
    {
        if (!class_exists('COM')) {
            throw new Exception("COM Object در این سیستم پشتیبانی نمی‌شود");
        }
        
        $conn = new COM("ADODB.Connection");
        $connectionString = "Provider=SQLOLEDB;Data Source={$this->connectionInfo['server']};Initial Catalog={$this->connectionInfo['database']};User ID={$this->connectionInfo['username']};Password={$this->connectionInfo['password']};";
        
        $conn->Open($connectionString);
        $this->connection = $conn;
        
        $this->log("✅ اتصال موفق به SQL Server از طریق COM Object");
        return true;
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
            
            // تست ساده
            if ($this->connection instanceof PDO) {
                $stmt = $this->connection->query("SELECT 1 AS test");
                $result = $stmt->fetch();
                return $result['test'] == 1;
            } elseif ($this->connection instanceof COM) {
                $recordset = $this->connection->Execute("SELECT 1 AS test");
                return !$recordset->EOF;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->log("تست اتصال ناموفق: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * دریافت لیست جداول
     */
    public function getTables() 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            if ($this->connection instanceof PDO) {
                return $this->getTablesViaPDO();
            } elseif ($this->connection instanceof COM) {
                return $this->getTablesViaCOM();
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->log("خطا در دریافت لیست جداول: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * دریافت جداول از طریق PDO
     */
    private function getTablesViaPDO() 
    {
        $query = "
            SELECT 
                t.TABLE_NAME as name,
                COUNT(c.COLUMN_NAME) as column_count,
                COALESCE(s.row_count, 0) as record_count
            FROM INFORMATION_SCHEMA.TABLES t
            LEFT JOIN INFORMATION_SCHEMA.COLUMNS c ON t.TABLE_NAME = c.TABLE_NAME
            LEFT JOIN (
                SELECT 
                    t.NAME as table_name,
                    SUM(p.rows) as row_count
                FROM sys.tables t
                INNER JOIN sys.indexes i ON t.OBJECT_ID = i.object_id
                INNER JOIN sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id
                WHERE t.is_ms_shipped = 0 AND i.index_id IN (0,1)
                GROUP BY t.NAME
            ) s ON t.TABLE_NAME = s.table_name
            WHERE t.TABLE_TYPE = 'BASE TABLE'
            GROUP BY t.TABLE_NAME, s.row_count
            ORDER BY t.TABLE_NAME
        ";
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tables[] = [
                'name' => $row['name'],
                'columns' => $row['column_count'],
                'records' => $row['record_count'],
                'last_sync' => null // این اطلاعات بعداً از سایر منابع دریافت می‌شود
            ];
        }
        
        return $tables;
    }
    
    /**
     * دریافت جداول از طریق COM
     */
    private function getTablesViaCOM() 
    {
        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME";
        
        // برای COM object - استفاده از call_user_func برای جلوگیری از خطای static analysis
        $recordset = call_user_func([$this->connection, 'Execute'], $query);
        
        $tables = [];
        while (!$recordset->EOF) {
            $tableName = $recordset->Fields("TABLE_NAME")->Value;
            
            // دریافت تعداد رکوردها
            try {
                $countQuery = "SELECT COUNT(*) as cnt FROM [{$tableName}]";
                $countRecordset = call_user_func([$this->connection, 'Execute'], $countQuery);
                $recordCount = $countRecordset->Fields("cnt")->Value;
            } catch (Exception $e) {
                $recordCount = 0;
            }
            
            $tables[] = [
                'name' => $tableName,
                'columns' => 0, // نیاز به کوئری جداگانه
                'records' => $recordCount,
                'last_sync' => null
            ];
            
            $recordset->MoveNext();
        }
        
        return $tables;
    }
    
    /**
     * دریافت داده‌های جدول
     */
    public function getTableData($tableName, $limit = null, $offset = null) 
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
            
            $query = "SELECT * FROM [{$tableName}]";
            
            if ($limit) {
                $query .= " ORDER BY (SELECT NULL) OFFSET " . ($offset ?? 0) . " ROWS FETCH NEXT {$limit} ROWS ONLY";
            }
            
            if ($this->connection instanceof PDO) {
                $stmt = $this->connection->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($this->connection instanceof COM) {
                $recordset = $this->connection->Execute($query);
                $data = [];
                
                while (!$recordset->EOF) {
                    $row = [];
                    for ($i = 0; $i < $recordset->Fields->Count; $i++) {
                        $field = $recordset->Fields($i);
                        $row[$field->Name] = $field->Value;
                    }
                    $data[] = $row;
                    $recordset->MoveNext();
                }
                
                return $data;
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->log("خطا در دریافت داده‌های جدول {$tableName}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * بستن اتصال
     */
    public function close() 
    {
        $this->connection = null;
        $this->log("اتصال SQL Server بسته شد");
    }
    
    /**
     * دریافت اطلاعات اتصال
     */
    public function getConnectionInfo() 
    {
        return [
            'server' => $this->connectionInfo['server'] ?? '',
            'database' => $this->connectionInfo['database'] ?? '',
            'username' => $this->connectionInfo['username'] ?? '',
            'port' => $this->connectionInfo['port'] ?? '1433',
            'method' => $this->connectionInfo['connection_method'] ?? 'odbc',
            'connected' => $this->connection !== null
        ];
    }
}

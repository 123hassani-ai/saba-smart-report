<?php
/**
 * Windows SQL Server Diagnostic Tool
 * ابزار تشخیص اتصال SQL Server در ویندوز
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Windows SQL Server Diagnostic</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap');
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            direction: rtl;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .diagnostic-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-good { border-left: 5px solid #4CAF50; }
        .status-warning { border-left: 5px solid #FF9800; }
        .status-error { border-left: 5px solid #F44336; }
        
        .diagnostic-title {
            color: white;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .diagnostic-content {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }
        
        .code-block {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 1rem;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Windows SQL Server Diagnostic</h1>
            <p style="color: rgba(255,255,255,0.8);">ابزار تشخیص اتصال SQL Server</p>
        </div>

        <?php
        // بررسی PHP Extensions
        echo '<div class="diagnostic-card status-' . (extension_loaded('com_dotnet') ? 'good' : 'error') . '">';
        echo '<div class="diagnostic-title">🔌 COM Extension Status</div>';
        echo '<div class="diagnostic-content">';
        if (extension_loaded('com_dotnet')) {
            echo '✅ COM extension is loaded and ready<br>';
            echo 'COM class available: ' . (class_exists('COM') ? 'YES' : 'NO');
        } else {
            echo '❌ COM extension is NOT loaded<br>';
            echo '<strong>Solution:</strong> Enable php_com_dotnet.dll in php.ini';
        }
        echo '</div></div>';
        
        // بررسی SQLSRV Extension
        echo '<div class="diagnostic-card status-' . (extension_loaded('sqlsrv') ? 'good' : 'warning') . '">';
        echo '<div class="diagnostic-title">🗄️ SQLSRV Extension Status</div>';
        echo '<div class="diagnostic-content">';
        if (extension_loaded('sqlsrv')) {
            echo '✅ SQLSRV extension is loaded<br>';
            echo 'Version: ' . phpversion('sqlsrv');
        } else {
            echo '⚠️ SQLSRV extension is NOT loaded<br>';
            echo '<strong>Alternative:</strong> Using COM Object instead';
        }
        echo '</div></div>';
        
        // بررسی PDO Extensions
        $pdo_drivers = PDO::getAvailableDrivers();
        echo '<div class="diagnostic-card status-' . (in_array('sqlsrv', $pdo_drivers) ? 'good' : 'warning') . '">';
        echo '<div class="diagnostic-title">📦 PDO Drivers</div>';
        echo '<div class="diagnostic-content">';
        echo 'Available drivers: ' . implode(', ', $pdo_drivers) . '<br>';
        if (in_array('sqlsrv', $pdo_drivers)) {
            echo '✅ PDO_SQLSRV is available';
        } else {
            echo '⚠️ PDO_SQLSRV is NOT available';
        }
        echo '</div></div>';
        
        // تست اتصال COM
        if (class_exists('COM')) {
            echo '<div class="diagnostic-card status-good">';
            echo '<div class="diagnostic-title">🚀 COM Connection Test</div>';
            echo '<div class="diagnostic-content">';
            
            try {
                $conn = new COM("ADODB.Connection");
                echo '✅ COM Object created successfully<br>';
                
                // بررسی config
                if (file_exists('config.json')) {
                    $config = json_decode(file_get_contents('config.json'), true);
                    $sqlConfig = $config['sql_server'];
                    
                    $server = $sqlConfig['server'];
                    $database = $sqlConfig['database'];
                    $username = $sqlConfig['username'] ?? '';
                    $password = $sqlConfig['password'] ?? '';
                    
                    echo '<div class="code-block">';
                    echo "Server: {$server}<br>";
                    echo "Database: {$database}<br>";
                    echo "Username: " . ($username ? $username : 'Windows Authentication') . "<br>";
                    echo '</div>';
                    
                    // تلاش برای اتصال
                    $connectionString = "Provider=SQLOLEDB;Server={$server};Database={$database};";
                    if (!empty($username)) {
                        $connectionString .= "UID={$username};PWD={$password};";
                    } else {
                        $connectionString .= "Integrated Security=SSPI;";
                    }
                    
                    try {
                        $conn->Open($connectionString);
                        echo '🎉 <strong>SQL Server Connection SUCCESSFUL!</strong><br>';
                        echo 'Connection string worked: ' . $connectionString;
                        $conn->Close();
                    } catch (Exception $e) {
                        echo '❌ Connection failed: ' . $e->getMessage() . '<br>';
                        echo '<strong>Try these solutions:</strong><br>';
                        echo '• Check SQL Server is running<br>';
                        echo '• Verify server name and credentials<br>';
                        echo '• Enable TCP/IP protocol in SQL Server Configuration Manager<br>';
                        echo '• Check Windows Firewall settings';
                    }
                } else {
                    echo '❌ config.json file not found';
                }
                
            } catch (Exception $e) {
                echo '❌ COM Object creation failed: ' . $e->getMessage();
            }
            
            echo '</div></div>';
        }
        
        // اطلاعات سیستم
        echo '<div class="diagnostic-card status-good">';
        echo '<div class="diagnostic-title">💻 System Information</div>';
        echo '<div class="diagnostic-content">';
        echo '<div class="code-block">';
        echo 'PHP Version: ' . PHP_VERSION . '<br>';
        echo 'Operating System: ' . PHP_OS . '<br>';
        echo 'Architecture: ' . php_uname('m') . '<br>';
        echo 'Server Software: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Command Line') . '<br>';
        echo '</div>';
        echo '</div></div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <button class="btn" onclick="location.reload()">🔄 Refresh Diagnostic</button>
            <button class="btn" onclick="window.location.href='windows.php'">🏠 Back to Dashboard</button>
        </div>
    </div>
</body>
</html>

<?php
echo "=== Saba System Test ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";

// Check required files
$files = ['windows.php', 'config.json'];
foreach ($files as $file) {
    echo "File $file: " . (file_exists($file) ? 'EXISTS' : 'MISSING') . "\n";
}

// Check modules directory
echo "Modules directory: " . (is_dir('modules') ? 'EXISTS' : 'MISSING') . "\n";

echo "\nTest completed!\n";
?>

<?php
/**
 * Docker Network Test Script
 * 
 * This script helps diagnose Docker networking issues that prevent FTP connections.
 * Run this from within your Docker container to test connectivity.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!function_exists('wp_loaded')) {
        require_once('../../../wp-load.php');
    }
}

function test_docker_connectivity() {
    echo "=== Docker Network Connectivity Test ===\n\n";
    
    // Test 1: Check if we can resolve DNS
    echo "1. DNS Resolution Test:\n";
    $test_hosts = ['google.com', '8.8.8.8', '1.1.1.1'];
    foreach ($test_hosts as $host) {
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            echo "   ✓ {$host} resolves to {$ip}\n";
        } else {
            echo "   ✗ {$host} failed to resolve\n";
        }
    }
    
    // Test 2: Check if we can connect to common ports
    echo "\n2. Port Connectivity Test:\n";
    $test_connections = [
        ['host' => 'google.com', 'port' => 80],
        ['host' => 'google.com', 'port' => 443],
        ['host' => '8.8.8.8', 'port' => 53]
    ];
    
    foreach ($test_connections as $test) {
        $conn = @fsockopen($test['host'], $test['port'], $errno, $errstr, 5);
        if ($conn) {
            echo "   ✓ {$test['host']}:{$test['port']} - Connected\n";
            fclose($conn);
        } else {
            echo "   ✗ {$test['host']}:{$test['port']} - Failed ({$errstr})\n";
        }
    }
    
    // Test 3: Check FTP extension
    echo "\n3. FTP Extension Test:\n";
    if (function_exists('ftp_connect')) {
        echo "   ✓ FTP extension is available\n";
    } else {
        echo "   ✗ FTP extension is NOT available\n";
    }
    
    // Test 4: Check container network info
    echo "\n4. Container Network Info:\n";
    $hostname = gethostname();
    echo "   Hostname: {$hostname}\n";
    
    $local_ip = $_SERVER['SERVER_ADDR'] ?? 'unknown';
    echo "   Local IP: {$local_ip}\n";
    
    // Test 5: Check if we can reach external FTP servers
    echo "\n5. External FTP Test:\n";
    $ftp_test_hosts = [
        'ftp.gnu.org',
        'ftp.ubuntu.com'
    ];
    
    foreach ($ftp_test_hosts as $host) {
        $conn = @ftp_connect($host, 21, 5);
        if ($conn) {
            echo "   ✓ {$host}:21 - FTP connection successful\n";
            ftp_close($conn);
        } else {
            echo "   ✗ {$host}:21 - FTP connection failed\n";
        }
    }
    
    echo "\n=== Recommendations ===\n";
    echo "If external connections fail, try these solutions:\n\n";
    
    echo "1. Check Docker network mode:\n";
    echo "   docker run --network host your-image\n\n";
    
    echo "2. Add port forwarding to docker-compose.yml:\n";
    echo "   ports:\n";
    echo "     - \"21:21\"\n";
    echo "     - \"20:20\"\n\n";
    
    echo "3. Use host networking:\n";
    echo "   docker run --network host your-image\n\n";
    
    echo "4. Check Docker daemon settings:\n";
    echo "   - Ensure DNS is properly configured\n";
    echo "   - Check if firewall rules block outbound connections\n\n";
    
    echo "5. Test from host machine:\n";
    echo "   ftp your-ftp-server.com\n\n";
    
    echo "6. Use alternative deployment methods:\n";
    echo "   - SFTP instead of FTP\n";
    echo "   - SSH-based deployment\n";
    echo "   - Web-based file upload\n";
}

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    test_docker_connectivity();
} 
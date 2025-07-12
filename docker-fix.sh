#!/bin/bash

# Docker FTP Connectivity Fix Script
# This script helps fix FTP connectivity issues in Docker containers

echo "=== Docker FTP Connectivity Fix ==="
echo "Container: bb-wp"
echo "Current network mode: Bridge (default)"
echo ""

# Check if container is running
if ! docker ps | grep -q bb-wp; then
    echo "❌ Container bb-wp is not running"
    exit 1
fi

echo "✅ Container bb-wp is running"
echo ""

# Test current connectivity
echo "1. Testing current network connectivity..."
echo "   Testing DNS resolution..."
if docker exec bb-wp nslookup google.com > /dev/null 2>&1; then
    echo "   ✅ DNS resolution works"
else
    echo "   ❌ DNS resolution failed"
fi

echo "   Testing basic connectivity..."
if docker exec bb-wp ping -c 1 google.com > /dev/null 2>&1; then
    echo "   ✅ Basic connectivity works"
else
    echo "   ❌ Basic connectivity failed"
fi

echo "   Testing FTP connectivity..."
if docker exec bb-wp timeout 5 ftp ftp.gnu.org > /dev/null 2>&1; then
    echo "   ✅ FTP connectivity works"
else
    echo "   ❌ FTP connectivity failed"
fi

echo ""

# Show current network settings
echo "2. Current network settings:"
docker inspect bb-wp | grep -A 10 "NetworkSettings"

echo ""

# Provide solutions
echo "3. Solutions to try (in order of preference):"
echo ""

echo "Option A: Use host networking (Recommended)"
echo "   Stop current container:"
echo "   docker stop bb-wp"
echo ""
echo "   Start with host networking:"
echo "   docker run --network host --name bb-wp-new your-wordpress-image"
echo ""

echo "Option B: Add custom DNS"
echo "   Stop current container:"
echo "   docker stop bb-wp"
echo ""
echo "   Start with custom DNS:"
echo "   docker run --dns 8.8.8.8 --dns 1.1.1.1 --name bb-wp-new your-wordpress-image"
echo ""

echo "Option C: Use docker-compose with host networking"
echo "   Create docker-compose.yml:"
echo "   version: '3'"
echo "   services:"
echo "     wordpress:"
echo "       image: your-wordpress-image"
echo "       network_mode: host"
echo "       ports:"
echo "         - '8080:80'"
echo ""

echo "Option D: Test SFTP instead of FTP"
echo "   - Change FTP port from 21 to 22"
echo "   - Use SSH credentials instead of FTP"
echo "   - SFTP is more reliable in Docker"
echo ""

echo "4. Quick test commands:"
echo "   Test DNS: docker exec bb-wp nslookup your-ftp-server.com"
echo "   Test FTP: docker exec bb-wp ftp your-ftp-server.com"
echo "   Test port: docker exec bb-wp telnet your-ftp-server.com 21"
echo ""

echo "5. Network debugging:"
echo "   Monitor traffic: docker exec bb-wp tcpdump -i any port 21"
echo "   Check routes: docker exec bb-wp ip route"
echo "   Check DNS: docker exec bb-wp cat /etc/resolv.conf"
echo ""

echo "=== Recommended Action ==="
echo "Try Option A (host networking) first, as it's the most reliable solution"
echo "for Docker FTP connectivity issues." 
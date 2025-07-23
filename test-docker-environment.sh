#!/bin/bash

echo "🧪 Testing Xelite Repost Engine in Docker environment..."

# Check if containers are running
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Docker containers are not running. Start them first with: ./setup-docker.sh"
    exit 1
fi

echo "✅ Docker containers are running"

# Test WordPress accessibility
echo "🔍 Testing WordPress accessibility..."
if curl -s http://localhost:8082 > /dev/null; then
    echo "✅ WordPress is accessible"
else
    echo "❌ WordPress is not accessible"
    exit 1
fi

# Test plugin file structure
echo "📁 Testing plugin file structure..."
if docker-compose exec wordpress test -d /var/www/html/wp-content/plugins/xelite-repost-engine; then
    echo "✅ Plugin directory exists"
else
    echo "❌ Plugin directory not found"
    exit 1
fi

# Test main plugin file
if docker-compose exec wordpress test -f /var/www/html/wp-content/plugins/xelite-repost-engine/xelite-repost-engine.php; then
    echo "✅ Main plugin file exists"
else
    echo "❌ Main plugin file not found"
    exit 1
fi

# Test PHP syntax
echo "🔍 Testing PHP syntax..."
if docker-compose exec wordpress php -l /var/www/html/wp-content/plugins/xelite-repost-engine/xelite-repost-engine.php; then
    echo "✅ Main plugin file has valid PHP syntax"
else
    echo "❌ Main plugin file has PHP syntax errors"
    exit 1
fi

# Test WP-CLI installation
echo "🔧 Testing WP-CLI installation..."
if docker-compose exec wordpress wp --version --allow-root; then
    echo "✅ WP-CLI is installed and working"
else
    echo "❌ WP-CLI is not working"
    exit 1
fi

echo ""
echo "🎉 Docker environment test completed successfully!"
echo ""
echo "📋 Manual testing checklist:"
echo "1. Visit http://localhost:8082"
echo "2. Complete WordPress setup if needed"
echo "3. Go to Plugins > Installed Plugins"
echo "4. Activate 'Xelite Repost Engine'"
echo "5. Go to Xelite Repost Engine > Settings"
echo "6. Configure API keys and settings"
echo "7. Test all plugin features"
echo ""
echo "🔧 Useful commands:"
echo "  - View logs: docker-compose logs -f wordpress"
echo "  - Access container: docker-compose exec wordpress bash"
echo "  - Check plugin status: docker-compose exec wordpress wp plugin list --allow-root"
echo "  - phpMyAdmin: http://localhost:8083" 
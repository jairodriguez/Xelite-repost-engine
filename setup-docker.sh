#!/bin/bash

echo "🚀 Setting up Xelite Repost Engine in Docker WordPress environment..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Build and start the containers
echo "📦 Building and starting Docker containers..."
docker-compose up -d --build

# Wait for WordPress to be ready
echo "⏳ Waiting for WordPress to be ready..."
sleep 30

# Check if WordPress is accessible
echo "🔍 Checking WordPress accessibility..."
if curl -s http://localhost:8082 > /dev/null; then
    echo "✅ WordPress is running at http://localhost:8082"
    echo "✅ phpMyAdmin is available at http://localhost:8083"
    echo ""
    echo "📋 Next steps:"
    echo "1. Visit http://localhost:8082 to complete WordPress setup"
    echo "2. Install and activate the Xelite Repost Engine plugin"
    echo "3. Configure the plugin settings"
    echo "4. Test all functionality"
    echo ""
    echo "🔧 Useful commands:"
    echo "  - View logs: docker-compose logs -f wordpress"
    echo "  - Stop containers: docker-compose down"
    echo "  - Restart containers: docker-compose restart"
    echo "  - Access container shell: docker-compose exec wordpress bash"
else
    echo "❌ WordPress is not accessible. Check the logs:"
    docker-compose logs wordpress
fi 
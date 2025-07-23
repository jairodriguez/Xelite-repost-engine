# Docker WordPress Environment for Xelite Repost Engine

This directory contains Docker configuration files to test the Xelite Repost Engine plugin in a local WordPress environment.

## Quick Start

1. **Start the environment:**
   ```bash
   ./setup-docker.sh
   ```

2. **Test the environment:**
   ```bash
   ./test-docker-environment.sh
   ```

3. **Access WordPress:**
   - WordPress: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

## Manual Setup

If you prefer to set up manually:

1. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Wait for WordPress to be ready (about 30 seconds)**

3. **Complete WordPress setup:**
   - Visit http://localhost:8080
   - Follow the WordPress installation wizard
   - Create an admin account

4. **Activate the plugin:**
   - Go to Plugins > Installed Plugins
   - Find "Xelite Repost Engine"
   - Click "Activate"

5. **Configure the plugin:**
   - Go to Xelite Repost Engine > Settings
   - Enter your API keys and configuration

## Environment Details

### Services
- **WordPress**: PHP 8.1 with Apache, accessible on port 8080
- **MySQL**: Database server (MySQL 8.0)
- **phpMyAdmin**: Database management interface on port 8081

### Volumes
- `wordpress_data`: WordPress files and uploads
- `db_data`: MySQL database data
- Plugin files are mounted from `./xelite-repost-engine`

### Configuration
- **PHP Memory Limit**: 512MB
- **Upload Max Filesize**: 64MB
- **Max Execution Time**: 300 seconds
- **Debug Mode**: Enabled

## Useful Commands

### Container Management
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f wordpress

# Restart containers
docker-compose restart

# Access container shell
docker-compose exec wordpress bash
```

### WordPress CLI
```bash
# List plugins
docker-compose exec wordpress wp plugin list --allow-root

# Activate plugin
docker-compose exec wordpress wp plugin activate xelite-repost-engine --allow-root

# Check plugin status
docker-compose exec wordpress wp plugin status xelite-repost-engine --allow-root
```

### Database Management
```bash
# Access MySQL
docker-compose exec db mysql -u wordpress -p wordpress

# Export database
docker-compose exec db mysqldump -u wordpress -p wordpress > backup.sql

# Import database
docker-compose exec -T db mysql -u wordpress -p wordpress < backup.sql
```

## Testing Checklist

After setting up the environment, test the following:

### Basic Functionality
- [ ] Plugin activates without errors
- [ ] Admin menu appears in WordPress admin
- [ ] Settings page loads correctly
- [ ] No PHP errors in debug log

### API Integration
- [ ] OpenAI API connection test works
- [ ] X (Twitter) API authentication works
- [ ] API keys are saved correctly

### User Interface
- [ ] Dashboard loads without errors
- [ ] All admin pages are accessible
- [ ] AJAX requests work properly
- [ ] JavaScript functionality works

### Database Operations
- [ ] Plugin tables are created on activation
- [ ] User meta is saved correctly
- [ ] Analytics data is stored properly

### Content Generation
- [ ] Content generation works
- [ ] Few-shot learning examples are saved
- [ ] A/B testing functionality works

### Chrome Extension
- [ ] Extension can communicate with WordPress
- [ ] REST API endpoints are accessible
- [ ] Authentication works properly

## Troubleshooting

### Common Issues

1. **WordPress not accessible:**
   - Check if containers are running: `docker-compose ps`
   - View logs: `docker-compose logs wordpress`
   - Wait longer for initial setup

2. **Plugin not appearing:**
   - Check if plugin files are mounted correctly
   - Verify file permissions
   - Check WordPress debug log

3. **Database connection issues:**
   - Ensure MySQL container is running
   - Check database credentials
   - Verify network connectivity

4. **PHP errors:**
   - Check PHP error log in container
   - Verify PHP extensions are installed
   - Check memory limits

### Debug Mode

Debug mode is enabled by default. Check logs at:
- WordPress debug log: Inside the container at `/var/www/html/wp-content/debug.log`
- PHP error log: Inside the container at `/var/log/php_errors.log`

### Performance

For better performance in development:
- Increase PHP memory limit if needed
- Use Redis for object caching
- Enable OPcache for PHP

## Cleanup

To completely remove the environment:
```bash
# Stop and remove containers
docker-compose down

# Remove volumes (WARNING: This will delete all data)
docker-compose down -v

# Remove images
docker-compose down --rmi all
```

## Production Considerations

This setup is for development/testing only. For production:
- Use proper SSL certificates
- Configure proper security settings
- Use production-grade database
- Set up proper backups
- Configure monitoring and logging 
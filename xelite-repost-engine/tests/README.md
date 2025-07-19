# Database Integration Tests

This directory contains comprehensive database integration tests for the Xelite Repost Engine plugin.

## Overview

The database integration tests verify that all database functionality works correctly, including:

- Table creation and structure verification
- CRUD operations with various data types and edge cases
- Batch operations (bulk insert, update)
- Utility methods for common queries
- Performance testing with large datasets
- Database upgrades and version management
- Error handling and edge cases
- Concurrent database operations

## Files

### `class-xelite-repost-engine-database-test.php`
Comprehensive WordPress unit test class that extends `WP_UnitTestCase`. This file contains all the test methods for database functionality.

**Test Coverage:**
- Table creation and structure verification
- Basic CRUD operations
- Batch operations with transaction support
- Utility methods (get_reposts_by_user, get_top_performing_reposts, etc.)
- Data validation and sanitization
- Search functionality
- Analytics functionality
- Database statistics
- Export functionality
- Database cleanup
- Concurrent operations
- Database upgrade functionality
- Error handling
- Performance with large datasets

### `class-xelite-repost-engine-mock-data-generator.php`
Mock data generator class that creates realistic test data for the database tests.

**Features:**
- Generates realistic tweet content using templates
- Creates engagement metrics with different levels (low, medium, high)
- Generates analysis data for reposts
- Creates test users with user meta data
- Generates performance test data for large datasets
- Generates date range test data for analytics testing

### `run-database-tests.php`
Standalone test runner that can be executed independently of WordPress testing environment.

**Usage:**
```bash
php tests/run-database-tests.php
```

## Running Tests

### Option 1: WordPress Testing Environment (Recommended)

If you have WordPress testing environment set up:

1. Navigate to your WordPress testing directory
2. Run the tests using PHPUnit:
   ```bash
   phpunit tests/class-xelite-repost-engine-database-test.php
   ```

### Option 2: Standalone Test Runner

For quick testing without full WordPress environment:

```bash
cd xelite-repost-engine
php tests/run-database-tests.php
```

This will run a simplified version of the tests and provide a summary of results.

### Option 3: Individual Test Methods

You can also run individual test methods by modifying the test runner or using PHPUnit's `--filter` option:

```bash
phpunit --filter test_basic_crud_operations tests/class-xelite-repost-engine-database-test.php
```

## Test Data

The tests use realistic mock data that simulates actual repost data:

- **Source Handles**: 20 different realistic handles (productivity_guru, marketing_expert, etc.)
- **Tweet Content**: Templates for different categories (productivity, marketing, business, mindset)
- **Engagement Metrics**: Realistic likes, retweets, and replies with different engagement levels
- **Analysis Data**: Pattern analysis, tone detection, sentiment scores, etc.
- **Date Ranges**: Spread across different time periods for analytics testing

## Test Categories

### 1. Basic Functionality Tests
- Table creation and structure verification
- Basic CRUD operations (Create, Read, Update, Delete)
- Data validation and sanitization

### 2. Advanced Query Tests
- Batch operations with transaction support
- Utility methods for common queries
- Top performing reposts with JSON field extraction
- Search functionality with text matching

### 3. Analytics and Reporting Tests
- Analytics functionality with period-based grouping
- Database statistics
- Export functionality for data portability

### 4. Performance Tests
- Large dataset handling (100+ records)
- Batch operation performance
- Query performance with complex filters

### 5. Error Handling Tests
- Invalid data handling
- Invalid table names
- Invalid user IDs
- Edge cases and error conditions

### 6. Database Management Tests
- Database cleanup functionality
- Concurrent operations
- Database upgrade functionality

## Expected Results

When all tests pass, you should see output similar to:

```
Starting Xelite Repost Engine Database Integration Tests
=======================================================

Running: Table Creation and Structure... ✅ PASSED
Running: Basic CRUD Operations... ✅ PASSED
Running: Batch Operations... ✅ PASSED
Running: Utility Methods... ✅ PASSED
Running: Top Performing Reposts... ✅ PASSED
Running: Data Validation and Sanitization... ✅ PASSED
Running: Search Functionality... ✅ PASSED
Running: Analytics Functionality... ✅ PASSED
Running: Database Statistics... ✅ PASSED
Running: Export Functionality... ✅ PASSED
Running: Database Cleanup... ✅ PASSED
Running: Concurrent Operations... ✅ PASSED
Running: Database Upgrade... ✅ PASSED
Running: Error Handling... ✅ PASSED
Running: Performance with Large Datasets... ✅ PASSED

Test Results Summary:
====================
Passed: 15
Failed: 0
Total: 15

🎉 All tests passed! Database integration is working correctly.
```

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Ensure WordPress database is properly configured
   - Check database credentials in wp-config.php
   - Verify database server is running

2. **Permission Errors**
   - Ensure the database user has CREATE, INSERT, UPDATE, DELETE permissions
   - Check file permissions for test files

3. **Memory Issues with Large Datasets**
   - Increase PHP memory limit if testing with large datasets
   - Consider reducing the size of performance test data

4. **Timeout Issues**
   - Increase PHP execution time limit for performance tests
   - Consider running performance tests separately

### Debug Mode

To enable debug mode for more detailed error information:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Contributing

When adding new database functionality:

1. Add corresponding test methods to `class-xelite-repost-engine-database-test.php`
2. Update the mock data generator if needed
3. Update the test runner to include new tests
4. Ensure all tests pass before committing changes

## Integration with CI/CD

These tests can be integrated into continuous integration pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run Database Tests
  run: |
    cd xelite-repost-engine
    php tests/run-database-tests.php
```

## Performance Benchmarks

The tests include performance benchmarks to ensure the database operations remain efficient:

- **Batch Insert**: Should complete 100 records within 5 seconds
- **Query Performance**: Complex queries should complete within 1 second
- **Memory Usage**: Should not exceed reasonable memory limits

## Security Testing

The tests verify security aspects:

- SQL injection prevention through prepared statements
- Data sanitization and validation
- Input validation for all parameters
- Error handling without exposing sensitive information 
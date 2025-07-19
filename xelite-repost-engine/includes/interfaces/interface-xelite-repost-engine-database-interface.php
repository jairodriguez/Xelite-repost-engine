<?php
/**
 * Database Interface
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Interface
 */
interface XeliteRepostEngine_Database_Interface {
    
    /**
     * Create database tables
     *
     * @return bool
     */
    public function create_tables();
    
    /**
     * Drop database tables
     *
     * @return bool
     */
    public function drop_tables();
    
    /**
     * Insert data
     *
     * @param string $table Table name
     * @param array  $data  Data to insert
     * @return int|false
     */
    public function insert($table, $data);
    
    /**
     * Update data
     *
     * @param string $table  Table name
     * @param array  $data   Data to update
     * @param array  $where  Where conditions
     * @return int|false
     */
    public function update($table, $data, $where);
    
    /**
     * Delete data
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int|false
     */
    public function delete($table, $where);
    
    /**
     * Get data
     *
     * @param string $table   Table name
     * @param array  $where   Where conditions
     * @param array  $orderby Order by conditions
     * @param int    $limit   Limit
     * @param int    $offset  Offset
     * @return array
     */
    public function get($table, $where = array(), $orderby = array(), $limit = 0, $offset = 0);
    
    /**
     * Get single row
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return object|null
     */
    public function get_row($table, $where = array());
    
    /**
     * Count rows
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int
     */
    public function count($table, $where = array());
    
    /**
     * Check if table exists
     *
     * @param string $table Table name
     * @return bool
     */
    public function table_exists($table);
} 
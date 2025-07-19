<?php
/**
 * Database functionality class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database functionality class
 */
class XeliteRepostEngine_Database extends XeliteRepostEngine_Abstract_Base implements XeliteRepostEngine_Database_Interface {
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Table names
     *
     * @var array
     */
    private $tables = array();
    
    /**
     * Initialize the class
     */
    protected function init() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Define table names
        $this->tables = array(
            'reposts' => $this->wpdb->prefix . 'xelite_reposts',
        );
        
        $this->log_debug('Database class initialized');
    }
    
    /**
     * Create database tables
     *
     * @return bool
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Reposts table
        $table_name = $this->tables['reposts'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_handle varchar(255) NOT NULL,
            original_tweet_id varchar(255) NOT NULL,
            original_text text NOT NULL,
            repost_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_handle (source_handle),
            KEY original_tweet_id (original_tweet_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        $this->log_debug('Database tables created', array('result' => $result));
        
        return true;
    }
    
    /**
     * Drop database tables
     *
     * @return bool
     */
    public function drop_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        $this->log_debug('Database tables dropped');
        
        return true;
    }
    
    /**
     * Insert data
     *
     * @param string $table Table name
     * @param array  $data  Data to insert
     * @return int|false
     */
    public function insert($table, $data) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->insert($table_name, $data);
        
        if ($result === false) {
            $this->log_error('Failed to insert data', array(
                'table' => $table_name,
                'data' => $data,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data inserted', array(
            'table' => $table_name,
            'id' => $this->wpdb->insert_id
        ));
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update data
     *
     * @param string $table  Table name
     * @param array  $data   Data to update
     * @param array  $where  Where conditions
     * @return int|false
     */
    public function update($table, $data, $where) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->update($table_name, $data, $where);
        
        if ($result === false) {
            $this->log_error('Failed to update data', array(
                'table' => $table_name,
                'data' => $data,
                'where' => $where,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data updated', array(
            'table' => $table_name,
            'rows_affected' => $result
        ));
        
        return $result;
    }
    
    /**
     * Delete data
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int|false
     */
    public function delete($table, $where) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->delete($table_name, $where);
        
        if ($result === false) {
            $this->log_error('Failed to delete data', array(
                'table' => $table_name,
                'where' => $where,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data deleted', array(
            'table' => $table_name,
            'rows_affected' => $result
        ));
        
        return $result;
    }
    
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
    public function get($table, $where = array(), $orderby = array(), $limit = 0, $offset = 0) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT * FROM $table_name";
        
        // Add WHERE clause
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        // Add ORDER BY clause
        if (!empty($orderby)) {
            $orderby_clause = $this->build_orderby_clause($orderby);
            $sql .= " ORDER BY $orderby_clause";
        }
        
        // Add LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        $this->log_debug('Data retrieved', array(
            'table' => $table_name,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Get single row
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return object|null
     */
    public function get_row($table, $where = array()) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT * FROM $table_name";
        
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        $sql .= " LIMIT 1";
        
        $result = $this->wpdb->get_row($sql);
        
        $this->log_debug('Single row retrieved', array(
            'table' => $table_name,
            'found' => $result !== null
        ));
        
        return $result;
    }
    
    /**
     * Count rows
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int
     */
    public function count($table, $where = array()) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT COUNT(*) FROM $table_name";
        
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        $count = $this->wpdb->get_var($sql);
        
        $this->log_debug('Row count retrieved', array(
            'table' => $table_name,
            'count' => $count
        ));
        
        return (int) $count;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table Table name
     * @return bool
     */
    public function table_exists($table) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );
        
        return $result === $table_name;
    }
    
    /**
     * Get table name
     *
     * @param string $table Table key
     * @return string
     */
    private function get_table_name($table) {
        if (isset($this->tables[$table])) {
            return $this->tables[$table];
        }
        
        // If not found, assume it's already a full table name
        return $table;
    }
    
    /**
     * Build WHERE clause
     *
     * @param array $where Where conditions
     * @return string
     */
    private function build_where_clause($where) {
        $conditions = array();
        
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $placeholders = array_fill(0, count($value), '%s');
                $conditions[] = $this->wpdb->prepare(
                    "$column IN (" . implode(',', $placeholders) . ")",
                    $value
                );
            } else {
                $conditions[] = $this->wpdb->prepare("$column = %s", $value);
            }
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Build ORDER BY clause
     *
     * @param array $orderby Order by conditions
     * @return string
     */
    private function build_orderby_clause($orderby) {
        $conditions = array();
        
        foreach ($orderby as $column => $direction) {
            $direction = strtoupper($direction);
            if (!in_array($direction, array('ASC', 'DESC'))) {
                $direction = 'ASC';
            }
            $conditions[] = "$column $direction";
        }
        
        return implode(', ', $conditions);
    }
} 
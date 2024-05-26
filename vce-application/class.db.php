<?php
/**
 * This class deals with all database communication.
 */
class DB {
	
    private $connection = null;

    public $prefix = null;

	/**
	 * Instantiates MYSQLI and creates connection.
	 */
    public function __construct($vce = null, $host = null, $user = null, $password = null, $name = null, $port = null) {
    
    	if (func_num_args() == 1) {
    		// add db to vce object
    		if (!empty($vce)) {
    			$vce->db = $this;
    		}
    		// use TABLE_PREFIX only if this class is being instantiated from initiate.php 
    		$this->prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : '';
    	}
    	
    	// set values
		$db_host = (empty($host) && defined('DB_HOST')) ? DB_HOST : $host;
		$db_user = (empty($user) && defined('DB_USER')) ? DB_USER : $user; 
		$db_password = (empty($password) && defined('DB_PASSWORD')) ? DB_PASSWORD : $password; 
		$db_name = (empty($name) && defined('DB_NAME')) ? DB_NAME : $name;
		$db_port = (empty($port)) ? (defined('DB_PORT') ? DB_PORT : '3306') : $port;
    
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $this->connection = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
            $this->connection->set_charset("utf8");
        } catch (Exception $e) {
			ob_start();
			echo '<pre>';
			print_r($e);
			echo '</pre>';
			die('Database Connection Failed');
        }
    }

	/**
	 * Closes connection at destruction of class.
	 */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
	/** 
	 * Executes any query
	 */
    public function query($query) {
    	
    	// make query and return results
        $results = $this->connection->query($query);
        
        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $query);
            return false; 
        } else {
            return $results;
        }
    }
    
    
	/** 
	 * Executes multi_query for the provide $query
	 */
    public function multi_query($query) {
    
		$this->connection->multi_query($query);

		// this would be when we are wanting to query for somethign
		$results = array();

		do {
			// check if query currently being processed hasn't failed
			if ($this->connection->error) {
				$this->log_db_errors($this->connection->error, $query);
				return false; 
			}

			// store and possibly process result of the query,
			// both store_result & use_result will return false 
			// for queries that do not return results (INSERT for example)
			if (($each_result = $this->connection->store_result()) !== false) {
			   $results[] = $each_result->fetch_all(MYSQLI_ASSOC);
					$each_result->free();
			}

			// exit if there are no more results
			if (($this->connection->more_results()) === false) {
				break;
			}

			// get result of the next query results
			$this->connection->next_result();

		} while (true); // exit only on error or when there are no more queries to process
	
		return $results;	

    }

    
	/**  
	 * Mysqli->fetch_object(). Default output is an array of objects. Multi-dimensional array is the other option.
	 * Use:
	 * $stuff = $db->get_data_object('SELECT * FROM vce_test ');
	 * foreach ($stuff as $row) {
	 * echo '<br>';	
	 * echo $row->name;
	 * }
	 */  
    public function get_data_object($query, $object = true) {
        //set $row var to null
        $output = null;
        $result = $this->connection->query($query);
        
        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $query);
            return false;
        } else {
            $output = array();
            while ($row = ($object) ? $result->fetch_object() : $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
    

    /**
	 * Insert data into database table
	 * This handles most types of insertion into a single table.
	 * This looks to see if it is a single row or multiple rows being inserted and calls
	 * either insert_multi() or insert_single()
	 * for multi:
	 * $stuff = $db->insert('test', 
	 * array(
	 * array('name'=>'test1', 'email'=>'test1@email.com', 'state'=>'WA'),
	 * array('name'=>'test2', 'email'=>'test2@email.com', 'state'=>'WA'),
	 * array('name'=>'test3', 'email'=>'test3@email.com', 'state'=>'WA')
	 * )
	 * );
	 * $db->insert('table', $data);
	 * $stuff->lastid 	outputs the last id used
	 * $stuff->insertedids 	outputs an array of all the inserted ids, in order
	 * 
	 * for single:
	 * $data = array(
	 * 'name' => 'Thelma', 
	 * 'email' => 'thelma@address.com', 
	 * 'active' => 1
	 *  )
     */
    public function insert($table, $data = array()) {
		$table = $this->prefix . $table;   
        //Check for values
        if (empty($data)) {
        	$this->log_db_errors('data is empty', 'insert function');
            return false;
        }
        // check if $variables is a multi dimentional array
        if (isset($data[0])) {
       		$output = array();
        	//insert an array of keys=>values
        	foreach ($data as $insertion_data) {
        		array_push($output, $this->insert_single($table, $insertion_data));
        	}
        } else {
    		//insert single key=>value
    		$output = $this->insert_single($table, $data);
        }
        
        global $vce;
        if (isset($vce->site)) {
			// db_insert hook
			if (isset($vce->site->hooks['db_insert'])) {
				foreach($vce->site->hooks['db_insert'] as $hook) {
					call_user_func($hook, $table, $data, $output);
				}
			}
        }
        
		// returns insert id as array
		return $output;
    }
    
    /**
     *  Example usage:
     *  $user_data = array(
     *  'name' => 'Serio', 
     *  'email' => 'email@address.com', 
     *  'active' => 1
     *  );
     *  $db->insert( 'users_table', $user_data );
     */
    public function insert_single($table, $data = array()) {
        //Make sure the array isn't empty
        if (empty($data)) {
            $this->log_db_errors('data is empty', 'insert_single function');
            return false;
        }

        $sql = "REPLACE INTO " . $table;
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = "'" . $value . "'";
        }
        $fields = '(' . implode(',', $fields) . ')';
        $values = '(' . implode(',', $values) . ')';
        
        $sql .= $fields . ' VALUES ' . $values;

        $query = $this->connection->query($sql);
        
        if ($this->connection->error) {
            //return false; 
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
        	return $this->connection->insert_id;
        }
    }


	/**
     *   Update data in database table
     *   
     *   Example usage:
     *
     *   Single Query
     *   $update = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     *   $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     *   $db->update( 'users_table', $update, $update_where);
     *    
     *   Mulit Query
     *   $update[] = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     *   $update_where[] = array( 'user_id' => 44, 'name' => 'Bennett' );
     *   $db->update( 'users_table', $update, $update_where);
     */
    public function update($table, $data = array(), $where = array()) {
    
    	global $vce;

        // if data is empty return
        if (empty($data)) {
            return false;
        }
         
        $table = $this->prefix . $table;
		
		// this is a multi_query
        if (isset($data[0])) {
        
			// if where is not an array, trigger error
			if (!isset($where[0]['component_id'])) {
				$this->log_db_errors('$where is not an array for multi_query', '$db->update_multiple');
				return false;
			}
		 
			// if array counts for data and where are not equal, tigger error
			if (count($data) != count($where)) {
				$this->log_db_errors('$data and $where are not the same count in multi_query', '$db->update_multiple');
				return false;
			}
         
			$queries = array();
		
			foreach ($data as $data_key=>$data_value) {
		
				$sql = "UPDATE " . $table . " SET ";
				$updates = array();
				foreach ($data_value as $field=>$value) {
					$updates[] = "$field='$value'";
				}
				$sql .= implode(', ', $updates);
		
				// add the $where clauses as needed
				$clause = array();
				foreach ($where[$data_key] as $field=>$value ) {
					$value = $value;
					$clause[] = "$field='$value'";
				}
				$sql .= ' WHERE ' . implode(' AND ', $clause);
		
				$queries[] = $sql;
		 
			}
			
			// send queries to multi_query
			$this->connection->multi_query(implode(';', $queries));
		
			// find and return errors
			$row = 0;
			do {
				if ($this->connection->error) {
					$this->log_db_errors($this->connection->error, $queries[$row]);
					return false;
				}

				// exit loop if there ar no more queries to process
				if (($this->connection->more_results()) === false) {
					break;
				}

				$this->connection->next_result();
				$row++;
			} while (true);
		
        } else {
        
       	 	// single query
        
			$sql = "UPDATE " . $table . " SET ";
			foreach ($data as $field => $value) {
				$updates[] = "$field = '$value'";
			}
			$sql .= implode(', ', $updates);
		
			// add the $where clauses as needed
			if (!empty($where)) {
				foreach ($where as $field => $value ) {
					$value = $value;
					$clause[] = "$field='$value'";
				}
				$sql .= ' WHERE ' . implode(' AND ', $clause);   
			}
		
			if (!empty($limit)) {
				$sql .= ' LIMIT ' . $limit;
			}

			$this->connection->query($sql);
			
			if ($this->connection->error) {
				$this->log_db_errors($this->connection->error, $sql);
				return false;
			}
        
		}
		
		global $vce;
		if (isset($vce->site)) {
			// db_update hook
			if (isset($vce->site->hooks['db_update'])) {
				foreach($vce->site->hooks['db_update'] as $hook) {
					call_user_func($hook, $table, $where, $data);
				}
			}
		}
		
		return true;
		
    }
    
    
  	/**
     *  Delete rows from table
     *  
     *  Example usage:
     *  $where = array( 'user_id' => 4241, 'email' => 'email@address.com' );
     *  $db->delete( 'table', $where, 1 );
     *  
     */
    public function delete($table, $where = array(), $limit = '') {
        $table = $this->prefix . $table;
        //Delete clauses require a where param, otherwise use "truncate"
        if (empty($where)) {
            return false;
        }
        
        // build where clause
		$where_clause = " WHERE ";
        foreach ($where as $field => $value) {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $where_clause .= implode(' AND ', $clause);
        
        if (!empty($limit)) {
            $where_clause .= " LIMIT " . $limit;
        }
        
        // get_data_object before delete to return
        $sql = "SELECT * FROM " . $table . $where_clause;
        $data = $this->get_data_object($sql);
        
        // delete statement
        $sql = "DELETE FROM " . $table . $where_clause;
        $query = $this->connection->query($sql);

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
        
			global $vce;
			if (isset($vce->site)) {
				// db_delete hook
				if (isset($vce->site->hooks['db_delete'])) {
					foreach($vce->site->hooks['db_delete'] as $hook) {
						call_user_func($hook, $table, $where, $data);
					}
				}
			}
		
            return $data;
        }
	}
    
    /**
     *  Get last auto-incrementing ID associated with an insertion
     *  
     *  Example usage:
     *  $db->insert( 'users_table', $user );
     *  $last = $db->lastid();
     */
    public function lastid() {
        return $this->connection->insert_id;
    }
    


    /**
     *  Error message handling for dev sites
     */
    public function log_db_errors($error, $query) {
        $message = '<p>Error at ' . date('Y-m-d H:i:s') . ':</p>';
        $message .= '<p>Query: ' . htmlentities($query) . '<br>Error: ' . $error . '</p>';

        if (!defined('VCE_DEBUG') || (defined('VCE_DEBUG') && VCE_DEBUG)) {
            trigger_error($message);
        }
    }
    

    /**
     *  sanitize data
     *  
     *  example :
     *  $sanitizedData = $db->sanitize( $_POST['data'] );
     *  
     *  filter an entire array:
     *  $data = array( 'name' => $_POST['data'], 'email' => 'address' );
     *  $data = $db->sanitize( $data );
     *  
     */
     public function sanitize($data) {
         if (!is_array($data)) {
             $data = $this->connection->real_escape_string($data);
             $data = trim(htmlentities($data, ENT_QUOTES, 'UTF-8', false));
         } else {
             //split array and call self to sanitize
             $data = array_map(array($this, 'sanitize'), $data);
         }
         return $data;
     }
     
     
	/**
     *  filter using mysqli_real_escape_string
	 */
     public function mysqli_escape($data) {
         if (!is_array($data)) {
             $data = $this->connection->real_escape_string($data);
         } else {
             //split array and call self to escape
             $data = array_map(array($this, 'mysqli_escape'), $data);
         }
         return $data;
     }

    /**
     *  filter using mysqli_real_escape_string
	 */
    public function mysqli_escape_only_string($data) {
        if (!is_array($data)) {
            if ($data && is_string($data)) {
                $data = $this->connection->real_escape_string($data);
            }
        } else {
            //split array and call self to escape
            $data = array_map(array($this, 'mysqli_escape_only_string'), $data);
        }
        return $data;
    }
    
	/**
     *  reverse the effects of mysqli_escape and sanitize (if values are stored in sanitized form in the DB, this will bring
     *  them back in the original form they had.)
	 */
     public function clean($data) {
         $data = stripslashes($data);
         // by html_entity_decode, xss insertions are being exposed, so commenting this out for now
         // $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
         $data = urldecode($data);
         return $data;
     }

    /**
     * Prepare a query with arguments
     *
     * @param [type] $query
     * @param [type] ...$args
     * @return void
     */
    public function prepare($query, ...$args) {
        if (is_null($query)) {
            return;
        }

        // If args were passed as an array (as in vsprintf), move them up.
        if (isset($args[0]) && is_array($args[0]) && count($args) == 1) {
            $args = $args[0];
        }

        /*
         * Specify the formatting allowed in a placeholder. The following are allowed:
         *
         * - Sign specifier. eg, $+d
         * - Numbered placeholders. eg, %1$s
         * - Padding specifier, including custom padding characters. eg, %05s, %'#5s
         * - Alignment specifier. eg, %05-s
         * - Precision specifier. eg, %.2f
         */
        $allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

        /*
         * If a %s placeholder already has quotes around it, removing the existing quotes and re-inserting them
         * ensures the quotes are consistent.
         *
         * For backward compatibility, this is only applied to %s, and not to placeholders like %1$s, which are frequently
         * used in the middle of longer strings, or as table name placeholders.
         */
        $query = str_replace("'%s'", '%s', $query); // Strip any existing single quotes.
        $query = str_replace('"%s"', '%s', $query); // Strip any existing double quotes.
        $query = preg_replace('/(?<!%)%s/', "'%s'", $query); // Quote the strings, avoiding escaped strings like %%s.

        $query = preg_replace("/(?<!%)(%($allowed_format)?f)/", '%\\2F', $query); // Force floats to be locale unaware.

        $query = preg_replace("/%(?:%|$|(?!($allowed_format)?[sdF]))/", '%%\\1', $query); // Escape any unescaped percents.

        array_walk($args, array($this, 'sanitize'));
        $query = vsprintf($query, $args);

        return $query;
    }

    /**
     * return the first row from query or null if nothing
     *
     * @param [type] $query
     * @return void
     */
    public function get_row($query, $object = false) {
        $r = $this->get_data_object($query, $object);
        return sizeof($r) > 0 ? $r[0] : null;
    }

    /**
     * get one values from a query
     *
     * @param [type] $query
     * @param integer $x
     * @param integer $y
     * @return void
     */
    public function get_var($query = null, $x = 0, $y = 0) {
        $q = [];
        if ($query) {
            $q = $this->query($query);
        }

        $q = $q->fetch_all();

        if (!empty($q[$y])) {
            $q = $q[$y];
        }

        // If there is a value return it else return null
        return (isset($q[$x]) && $q[$x] !== '') ? $q[$x] : null;
    }

    public function get_table_schema($table) {
        // map mysql types to php types
        static $schemata_types = [
            1 => 'int',
            2 => 'int',
            3 => 'int',
            4 => 'float',
            5 => 'float',
            7 => 'timestamp',
            8 => 'int',
            9 => 'int',
            10 => 'timestamp',
            11 => 'timestamp',
            12 => 'timestamp',
            13 => 'timestamp',
            16 => 'timestamp',
            249 => 'string',
            250 => 'string',
            251 => 'string',
            252 => 'string',
            253 => 'string',
            254 => 'string',
            246 => 'int'
        ];

        static $table_schemata = array();

        if (!array_key_exists($table, $table_schemata)) {
            $f = $this->connection->query("select * from $table limit 1")->fetch_fields();
            $schema = array();
            foreach ($f as $field_meta) {
                $schema[$field_meta->name] = $schemata_types[$field_meta->type];
            }
            $table_schemata[$table] = $schema;
        } else {
            $schema = $table_schemata[$table];
        }

        return $schema;
    }

    public function convert_value($schema, $field, $value) {
        if ($value === null) {
            $value = 'NULL';
        } else {
            switch ($schema[$field]) {
                case 'string':
                    $value = "'" . $this->connection->real_escape_string($value) . "'";
                    break;
                case 'int':
                    $value = $value;
                    break;
                case 'float':
                    $value = $value;
                    break;
                case 'timestamp':
                    $value = "'" . $value . "'";
                    break;
                default:
                    $value = $value;
            }
        }

        return $value;
    }

    public function sanitized_insert($table, $data = array()) {
        $table = $this->prefix . $table;

        $schema = $this->get_table_schema($table);

        // Construct sql
        $sql = "REPLACE INTO " . $table;
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $values[] = $this->convert_value($schema, $field, $value);
            $fields[] = $field;
        }
        $fields = '(' . implode(',', $fields) . ')';
        $values = '(' . implode(',', $values) . ')';
        
        $sql .= $fields . ' VALUES ' . $values;

        // run
        $this->connection->query($sql);

        if ($this->connection->error) {
            //return false; 
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        } else {
        	return $this->connection->insert_id;
        }
    }

    public function sanitized_update($table, $data = array(), $where = array()) {
        $table = $this->prefix . $table;

        $schema = $this->get_table_schema($table);

        $sql = "UPDATE " . $table . " SET ";
        foreach ($data as $field => $value) {
            $value = $this->convert_value($schema, $field, $value);
            $updates[] = "$field = $value";
        }
        $sql .= implode(', ', $updates);

        // add the $where clauses as needed
        if (!empty($where)) {
            foreach ($where as $field => $value) {
                $value = $this->convert_value($schema, $field, $value);
                $clause[] = "$field=$value";
            }
            $sql .= ' WHERE ' . implode(' AND ', $clause);
        }

        $this->connection->query($sql);

        if ($this->connection->error) {
            $this->log_db_errors($this->connection->error, $sql);
            return false;
        }

        return true;
    }
 
}
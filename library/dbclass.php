<?php
/*###############################################################
			=== Database Class © James Mickley 2014 ===
						
This file contains a generic class with everything needed for accessing databases.
To setup, edit the host, username, and password at the top of the class

Mostly we just use the query() and pquery() functions,
passing them a query to execute and then using fetch() or arr() to get the data.
Use pquery() with any queries containing variables supplied by users (GET, POST).


##### Public Function Reference #####
* open( [database] )									Opens a database connection
* close()												Closes a database connection and any result sets
* query( query, [database] )							Runs the specified query and returns the result
* createdb( databasename )								Creates a database
* tablefields( tablename, [database] )					Get an array of the fields for a table
* pquery( query, [bind_type_str],[bind_params...] )		Wrapper query function for prepared statements
* prepare()												Prepare a prepared statement query
* bind()												Bind parameters to a prepared statement query
* execute()												Execute a prepared statement query	
* fetch( [arraytype] )									Returns the next row of the result as an array
* arr()													Returns all rows of the result as an array of arrays


##### Version History #####
- 1/14/2014 JGM - Version 1.0:
		- Start of version numbering

###############################################################*/


class DB {

// ################# Database Setup: Edit Here ##################
  
   
	// Server and login information for our database goes here:
	public $host = "localhost";
	public $username = "[username]";
	public $pass = "[password]";
	public $debug = TRUE;
	public $db = "[database]";
   
     
// #################### Class Initialization ####################


	// Class variables that are created by class functions.
	// All can be referenced from outside the class except $conn
	private $conn;			// Stores our connection, not accessible
	public $rows;			// The number of rows returned from a SELECT query
	public $columns;		// The column names in a query
	public $insertid;		// The autoincrement id from an INSERT query
	public $result;			// The result resource returned by a query
	public $fetch;			// The array that fetch() returns for one row in a result
	public $tablefields;	// The fields in a given table returned by tablefields()
	public $resultarr;		// The array of row arrays for a query returned by arr()
	public $statemnt;		// Stores our statement object for prepared statements


	// Class constructor function, runs when a new class instance is made
	// This also takes care of opening a database connection
	// eg $db = new DB("databasename");
	public function __construct($db=''){

		// If database is unspecified, use the default, otherwise save the database to use
		if($db == ''){$db = $this->db;}else{$this->db = $db;}

		// Open a database connection
		$this->open($db);

	}
     
     
// #################### Database Connections ####################

     
	// This function opens a database connection and stores it in $this->conn
	public function open($db=''){

		// If database is unspecified, use the default
		if($db == ''){$db = $this->db;}

		// Open a connection (if already open, just return the conn link)
		$this->conn = new mysqli($this->host, $this->username, $this->pass, $db);

		// security vulnerability, but useful for bugtesting
		if (mysqli_connect_errno()) {
        	if ($this->debug) {
        		echo "Could not connect to database server. Reason: ".$this->conn->connect_error."<br>";
        		debug_print_backtrace();
        	}
        	exit;
        }
			
		// Return the database conn link
		return $this->conn;
	}
     
     
	// This function closes the class's current database connection and cleans up
	public function close(){

		// Check to see if the result is an object.  If not, it's probably not a result set
		if(is_object($this->result)){
			
			// Close the result set
			$this->result->close();
		}
		
		// Close the MYSQLI connection
		$this->conn->close();
	}



// ################## Standard Query Functions ##################
 
     
	// This function executes the query passed to it on the specified database.
	// It also gets a bunch of information after the query runs, such as:
	//	  number of rows, column names, and autoincrement id.
	public function query($query, $db=''){

		// If database is unspecified, use the default
		if($db == ''){$db = $this->db;}
	  
		// Open a database connection
		$this->open($db);
	  
		// saves the returned query resource in $this->result
		$this->result = $this->conn->query($query);

    	// security vulnerability, but useful for bugtesting
		if ($this->conn->errno) {
        	if ($this->debug) {
            	echo "Query on $db failed.  Reason: ".$this->conn->error."<br>";
            	debug_print_backtrace();
        	}
        	exit;
      	}

		// Get query statistics: insert ID, number of rows, column names
		$this->result_stats();
	  
		// Return the query resource
		return $this->result;
	}
     
     
	// This function creates a database with the specified name
	public function createdb($dbname){

		// Build query
		$query = "CREATE DATABASE ".dbname;

		// Execute query
		$this->query($query);

		// Close the connection
		$this->close();
	}
     
     
	// This returns the fieldnames for a given database/table combination
	public function tablefields($tablename, $db = ''){

		// If database is unspecified, use the default
		if($db == ''){$db = $this->db;}

		// Get the fieldnames via query
		$this->result = $this->query("SHOW FIELDS FROM $tablename", $db);

		// Fetch arrays representing a field's information, then get the fieldname and store in tablefields
		while($this->fetch())
			$this->tablefields[] = $this->fetch['Field'];
		
		// Return the fieldnames
		return $this->tablefields;

		// Close the connection
		$this->close();
	}


// #################### Prepared Statements #####################


	// This is a prepared statement version of the query function
	// Use this function to sanitize variables from userspace in queries, preventing SQL injection
	// It takes a query, and optionally binding parameters as further arguments
	// Queries can contain a ? where a variable will be substituted
	// The 2nd parameter is a type string for the further parameters
	// Valid types: i=int, d=dbl, s=string, b=blob
	// Parameters 3 to n are variables to substitute into the query
	public function pquery($query){

		// Prepare the query statement
		$this->prepare($query);

		// Bind the parameters to the query  
		// Minimum for binding is 3 params: query, type string, variable
		if(func_num_args()>2){

			// Get all parameters, except the first (which is the query)
			$params = array_slice(func_get_args(),1);

			// Call bind_param() passing it our params array
			call_user_func_array(array($this->statemnt, 'bind_param'), $params); 

		}

		// Execute the prepared statement
		$this->execute();

		// Return the statemnt object
		return $this->statemnt;
	}


	// Prepares a query for a prepared statement
	public function prepare($query, $db=''){

		// If database is unspecified, use the default
		if($db == ''){$db = $this->db;}
	  
		// Open a database connection
		//$this->open($db);

		// Prepare the statement, and store in statemnt object
		$this->statemnt = $this->conn->prepare($query);

    	// security vulnerability, but useful for bugtesting
		if ($this->conn->errno) {
        	if ($this->debug) {
            	echo "Prepared Statement on $db failed.  Reason: ".$this->conn->error."<br>";
            	debug_print_backtrace();
        	}
        	exit;
      	}

		// Return the statemnt object
		return $this->statemnt;
	}


	// Binds parameters to a prepared query
	// The 1st parameter is a type string for the further parameters
	// Valid types: i=int, d=dbl, s=string, b=blob
	// Parameters 2 to n are variables to substitute into the query
	public function bind(){

		// Bind the parameters to the query
		// Minimum for binding is 2 params: type string and variable
		if(func_num_args()>1){

			// Get all parameters passed to bind()
			$params = array_slice(func_get_args(),0);

			// Call bind_param() passing it our params array
			call_user_func_array(array($this->statemnt, 'bind_param'), $params);
		}
	}


	// This function executes the prepared statement
	// Also saves the statement object as the result and generates result stats
	public function execute(){

		// Execute the prepared statement set up in prepare()
		$this->statemnt->execute();

		// Store the result set (makes affected_rows, num_rows etc available)
		$this->statemnt->store_result();

		// Save the statement object as the result, we'll access it with the fetch functions
		$this->result = $this->statemnt;

		// Get query statistics: insert ID, number of rows, column names
		$this->result_stats();
	}


// #################### Data Return Functions ###################


	// // This function fetches the next row on the query resource stack and returns it as an array
	// // We can decide whether the array has fieldnames as the keys, numbers or both by passing fetchtype
	// // Valid types are MYSQLI_NUM, MYSQLI_ASSOC, and MYSQLI_BOTH
	public function fetch($fetchtype = MYSQLI_ASSOC){

		// Check to see if there are any rows to return in the result set
		if ($this->result->num_rows > 0){

			// The query was a prepared statement, so we'll have to treat it accordingly and make our own associative fetch
			if($this->result instanceof mysqli_stmt){

				// Construct the row array that will receive a row and pass by reference to the variables array
				foreach($this->columns as $column)
					$variables[] = &$row[$column];
				
		        // Bind the variables to receive data from the prepared statement
		        call_user_func_array(array($this->result, 'bind_result'), $variables);

		        // Fetch the next row
		        $this->result->fetch();

		        // Return the array type specified
				if($fetchtype == MYSQLI_ASSOC){

					// Associative array
					$this->fetch = $row;

				}else if($fetchtype == MYSQLI_NUM){

					// Numeric array
					$this->fetch = array_values($row);

				}else if($fetchtype == MYSQLI_BOTH){

					// Numeric and associative array
					foreach($row as $column => $value){

						$this->fetch[] = $value;
						$this->fetch[$column] = $value;
					}
				}
		        

			// Normal query, proceed as usual using the normal fetch
			}else if($this->result instanceof mysqli_result){

				// Get the next row
				$this->fetch = $this->result->fetch_array($fetchtype);

			}

			// Return the row
			return $this->fetch;

		}else{

			// No result set to return
			return FALSE;
		}
	}

     
	// This function returns an array of row arrays from the current query
	// Each row has a key of its row number.
	// Each row array has keys named for its fields
	// Data can be accessed via: $arr[1]['fieldname']
	public function arr(){

		// Check to see if there are any rows to return in the result set
		if ($this->result->num_rows > 0){

			// The query was a prepared statement, so we'll have to treat it accordingly and make our own associative fetch
			if($this->result instanceof mysqli_stmt){

				// Construct the row array that will receive each row and pass by reference to the variables array
				foreach($this->columns as $column)
					$variables[] = &$row[$column];
				
		        // Bind the variables to receive data from the prepared statement
		        call_user_func_array(array($this->result, 'bind_result'), $variables);

		        // Fetch all the rows in the result
		        while($this->result->fetch()){

		        	// Hack to get around row being passed by reference
		        	foreach($row as $column => $value){
		        		$tmp_row[$column] = $value;
		        	}

		        	// Store the resulting row array in the 2d data array with a key of the rownumber
		        	$this->resultarr[] = $tmp_row;

		        }


		   	// Normal query, proceed as usual using the normal fetch
			}else if($this->result instanceof mysqli_result){
				
				// Fetch all the rows in the result
				while($row = $this->fetch(MYSQL_ASSOC)){

					// Store the resulting row array in the 2d data array with a key of the rownumber
					$this->resultarr[] = $row;
				}
			}

			// Return the array of row arrays
			return $this->resultarr;

		}else{

			// No result set to return
			return FALSE;
		}
	}


	// NOTE: $this->conn->fetch_object() could be useful!


	//  This function sets class variables for various info on the result set
	// Included are the column/field names, the number of affected rows, and the InsertID
	private function result_stats(){

		// Remove the previous columns class variable (if any)
		$this->columns = array();

		// The query was from a prepared statement
		if($this->result instanceof mysqli_stmt){

			// Use $this->result (or $this->statemnt) for prepared statements
			$result = $this->result;
			
			// Check to see if there are any rows in the result set
			if($this->result->num_rows > 0){

				// Gets a list of fields in the result
				$fields = $this->result->result_metadata();

				// Gets each field in the result as an object, saving the column name in the class variable
			    while($field = $fields->fetch_field())
			    	$this->columns[] = $field->name;
			}


		// Normal query, not prepared statement
		}else if($this->result instanceof mysqli_result){

			// Use $this->conn for normal results
			$result = $this->conn;

			// Check to see if there are any rows in the result set
			if($this->result->num_rows > 0){

				// Gets a list of fields in the result
				$fields = $this->result->fetch_fields();

				// Gets each field in the result as an object, saving the column name in the class variable
			    foreach ($fields as $field) 
			    	$this->columns[] = $field->name;
			}

		}else{

			// Use $this->conn for non objects (eg standard queries that don't return results)
			$result = $this->conn;
		}

		// If the query is an INSERT query, returns the autoincrement value, otherwise returns zero
		$this->insertid = $result->insert_id;

		// Execute this if it wasn't an insert query, gets #rows and column names
		//if ($this->insertid == 0){
	  
			// Gets the number of rows in the result.  The "@" suppresses errors for non-select queries.
			$this->rows = $result->affected_rows;

			// INSERT UPDATE AND DELETE use affected rows, SELECT uses num rows


		//}

		//$this->rows = $this->statemnt->num_rows;
		
	}

 
// #################### Statistical Functions ###################


	// function means
	// SELECT AVG(`data`) as `data` FROM `test` WHERE `animal` = 'dog'
	public function stats($db, $table, $fields, $stat, $condition = "WHERE 1"){

		// Make it an array if it isn't already
		if(!is_array($fields)){$fields = array($fields);}

		// Start the query with a SELECT clause
		$query = "SELECT ";

		// Cycle through the array of fieldnames, adding them & the stat to the query
		for($i = 0; $i < count($fields); $i++){

			// Add a comma if it's not the first mean
			if($i > 0){$query .= ",";}

			// Add the stat function statement and store the stat as "fieldname"
			$query .= " $stat(`$fields[$i]`) AS $fields[$i]";
		}

		// Add the table (FROM clause)
		$query .= " FROM `$table` ";

		// Add the conditions (WHERE clause)
		$query .= $condition;

		// Execute the query
		$this->query($query,$db);

		// Fetch the result
		$stats = $this->fetch(MYSQL_ASSOC);

		// If only one mean was requested, return a single value instead of an array
		if(count($stats)== 1){$stats = $stats[$fields[0]];}

		// Return the result
		return $stats;

	}
     
     
	// Mean function
	public function mean($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with AVG() and return it
		return $this->stats($db, $table, $fields, "AVG", $condition);
	}
     
     
	// Median function
	public function median($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with AVG() and return it

		// MEDIAN is (MIN + MAX) / 2
		// This does not seem right
		return $this->stats($db, $table, $fields, "AVG", $condition);
	}
     
     
	// Minimum function
	public function min($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with MIN() and return it
		return $this->stats($db, $table, $fields, "MIN", $condition);
	}
     
     
	// Maximum function
	public function max($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with MAX() and return it
		return $this->stats($db, $table, $fields, "MAX", $condition);
	}
     

	// Count function (number of records)
	public function count($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with COUNT() and return it
		return $this->stats($db, $table, $fields, "COUNT", $condition);
	}
     
     
	// Sum function
	public function sum($db, $table, $fields, $condition = "WHERE 1"){

		// Call the stats function with SUM() and return it
		return $this->stats($db, $table, $fields, "SUM", $condition);
	}
     
     
	// Standard Deviation function
	// $type can be either STDDEV_SAMP or STDDEV_POP for sample or population standard deviation
	public function sd($db, $table, $fields, $condition = "WHERE 1", $type = "STDDEV_POP"){

		// Call the stats function with $type and return it
		return $this->stats($db, $table, $fields, $type, $condition);
	}
     
     
	// Variance function
	// $type can be either VAR_SAMP or VAR_POP for sample or population variance
	public function variance($db, $table, $fields, $condition = "WHERE 1", $type = "VAR_POP"){

		// Call the stats function with $type and return it
		return $this->stats($db, $table, $fields, $type, $condition);
	}
}


// Depreciated old code, use prepared statements instead
     
// ################## Preventing SQL Injection ##################


	// // This function "cleans" variables to be used in queries to prevent SQL injection.
	// // Pass it an array of variables or a single variable
	// // It will modify those variables in place, and does not return anything.
	// public function sanitize(&$arr){
	  
	// 	// Open a database connection
	// 	$this->open();

	// 	// Make it an array if it isn't already
	// 	if(!is_array($arr)){$arr = array($arr);}

	// 	// Cycle through the array and escape the contents of each variable in the array
	// 	foreach ($arr as &$var) {

	// 		// Strip slashes if magic_quotes_gpc is on
	// 		if(get_magic_quotes_gpc()){$var = stripslashes($var);}

	// 		// Escape the variable
	// 		if(function_exists("mysql_real_escape_string")){
			
	// 			// For newer PHP versions, use mysql_real_escape_string
	// 			$var = mysql_real_escape_string($var);
				
	// 		}else{
			
	// 			//for PHP version < 4.3.0 use addslashes
	// 			$var = addslashes($var); 
	// 	   }
	// 	}

	// 	// If the function wasn't passed an array, just edit that particular variable in place
	// 	if(count($arr) == 1){$arr = array_pop($arr);}

	// 	// Close the connection
	// 	$this->close();
	// }

?>

<?php
 /**
  * Just a helper class, 
  * Now containing a static method to import all SQL from a install.schema.sql
  */
 class installhelper {

	/**
	 * Restore MySQL dump using PHP 
	 *
	 * (c) 2006 Daniel15
	 * Last Update: 9th December 2006
	 * Version: 0.2
	 * Edited: Cleaned up the code a bit. 
	 *
	 * Please feel free to use any part of this, but please give me some credit :-)
	 * Modified to use PDO instead by Jim Kinsman (github.com/relipse)
	 * 
	 * Note* You may do $pdo->beginTransaction() prior to calling this., and then $pdo->commit(); to complete
	 */
     public static function import($filename, $pdo){

			// Temporary variable, used to store current query
			$templine = '';
			// Read in entire file
			$lines = file($filename);

			$errors = array();
			$statements = array();

			// Loop through each line
			foreach ($lines as $line)
			{
			    // Skip it if it's a comment
			    if (substr($line, 0, 2) == '--' || $line == ''){
			        continue;
			    }
			 
			    // Add this line to the current segment
			    $templine .= $line;
			    // If it has a semicolon at the end, it's the end of the query
			    if (substr(trim($line), -1, 1) == ';')
			    {
			        // Perform the query
			        try{
			        	$sql = $templine;
			        	$templine = '';
			        	$stm = $pdo->query($sql);
			        	$statements[] = $stm;
			       	}catch(Exception $e){
			       		$errors[] = $e;
			       	}
			    }
			}

			return array($statements, $errors);
     }
 }


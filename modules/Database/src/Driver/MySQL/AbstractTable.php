<?php
namespace Database\Driver\MySQL;

use Database\Table,
	Database\Model\AbstractModel,
	Database\Query\QueryParams,
	Database\Query\QueryExpr;

abstract class AbstractTable extends Table\AbstractTable implements Table\ReadableTableInterface, Table\WritableTableInterface, Table\ModelProviderInterface
{
	/**
	 * Insert a record into the table and return the last inserted ID
	 * 
	 * @param array $data
	 * @param array $updateOnDuplicate
	 * @return mixed Returns the last insert ID
	 */
	public function insert(array $data, array $updateOnDuplicate = null)
	{
		$update = null;
		if (!empty($updateOnDuplicate)) {
			$pairs = [];
			foreach ($updateOnDuplicate as $key => $value) {
				if (is_numeric($key)) {
					$pairs[] = "`{$value}` = VALUES(`{$value}`)";
				} else { 
					$pairs[] = "`{$key}` = ". $this->db->quote($value);
				}
			}
			
			if (!empty($pairs)) {
				$update = "ON DUPLICATE KEY UPDATE ". implode(",", $pairs);
			}
		}
		
		$columnNames = array_keys($data);
		$columnValues = array_map(function($value) {
			if ($value === null) {
				return "NULL";
			} else {
				return $this->db->quote($value);
			}
		}, array_values($data));
		
		$statement = $this->db->prepare("
			INSERT INTO `". $this->getName() ."`
				(`". implode("`,`", $columnNames) ."`)
			VALUES
				(". implode(",", $columnValues) .")
			{$update}
		");
		$statement->execute();
		
		return $this->db->lastInsertId();
	}
	
	/**
	 * Update the data in the table matching $params
	 * 
	 * @param array $data
	 * @param QueryParams $params
	 * @return int Number of rows affected
	 */
	public function update(array $data, QueryParams $params = null) : int
	{
		if (!$params) {
			$params = new QueryParams();
		}
		
		$pairs = [];
		foreach ($data as $name => $value) {
			$pairs[] = "`{$name}` = ". $this->db->quote($value);
		}
		
		$whereClause = $this->_generateWhereClause($params->conditions);
		
		return $this->db->exec("
			UPDATE	`". $this->getName() ."`
			SET		". implode(", ", $pairs) ."
			{$whereClause}
		");
	}
	
	/**
	 * Delete rows from the table
	 * 
	 * @param QueryParams $queryParams
	 * @return int The number of rows deleted
	 */
	public function delete(QueryParams $queryParams) : int
	{
		$whereClause = $this->_generateWhereClause($queryParams->conditions);
		
		return $this->db->exec("
			DELETE FROM	`". $this->getName() ."`
			{$whereClause}
		");
	}
	
	/**
	 * Attempt to fetch a single row from the table
	 * 
	 * @param QueryParams $params
	 * @params array $inputParams
	 * @return AbstractModel|boolean
	 */
	public function fetch(QueryParams $params = null, array $inputParams = []) 
	{
		if ($params === null) {
			$params = new QueryParams();
		}
		
		$params->maxResults = 1;
		$all = $this->fetchAll($params, $inputParams);
		
		if (count($all)) {
			return array_shift($all);
		}
		return false;
	}
	
	/**
	 * Fetch all records matching the parameters passed
	 * 
	 * @param QueryParams $params
	 * @params array $inputParams
	 * @return AbstractModel[]
	 */
	public function fetchAll(QueryParams $params = null, array $inputParams = []) : array 
	{
		if ($params === null) {
			$params = new QueryParams();
		}
		
		$statement = $this->db->prepare($this->_generateSelectStatement($params));
		$statement->execute($inputParams);
		
		return $this->prepareRows(
			$statement->fetchAll(\PDO::FETCH_ASSOC)
		);
	}

	/**
	 * Fetch a single column value from the table
	 * 
	 * @param string $columnName
	 * @param QueryParams $params
	 * @params array $inputParams
	 * @return mixed
	 */
	public function fetchColumn(string $columnName, QueryParams $params = null, array $inputParams = []) 
	{
		if ($params === null) {
			$params = new QueryParams();
		}
		
		$statement = $this->db->prepare($this->_generateSelectStatement($params));
		$statement->execute($inputParams);
		
		$row = $statement->fetch(\PDO::FETCH_ASSOC);
		
		if ($row && isset($row[$columnName])) {
			return $row[$columnName];
		}
		
		return false;
	}
	
	/**
	 * Generate a SELECT statement using a set of QueryParams
	 * 
	 * @param QueryParams $params
	 * @return string
	 */
	private function _generateSelectStatement(QueryParams $params) : string
	{
		$whereClause = $this->_generateWhereClause($params->conditions);
		$orderClause = $this->_generateOrderClause($params->orderings);
		$limitClause = $this->_generateLimitClause($params->maxResults, $params->resultOffset);
		
		return "
			SELECT	*
			FROM	`". $this->getName() ."`
			{$whereClause}
			{$orderClause}
			{$limitClause}
		";
	}

	/**
	 * Generate a WHERE clause based on the conditions provided
	 * 
	 * @param array $conditions
	 * @return string
	 */
	private function _generateWhereClause(array $conditions) : string
	{
		if (empty($conditions)) {
			return "";
		}
		
		$pairs = [];
		foreach ($conditions as $key => $value) {
			if ($value instanceof QueryExpr) {
				$pairs[] = $value->toString();
			} else if (is_array($value)) {
				$values = implode(",", array_map([$this->db, "quote"], $value));
				if (!empty($values)) {
					$pairs[] = "`{$key}` IN ({$values})";
				}
			} else if ($value === null) {
				$pairs[] = "`{$key}` IS NULL";
			} else {
				$pairs[] = "`{$key}` = ". $this->db->quote($value);
			}
		}
		
		return "WHERE ". implode(" AND ", $pairs);
	}
	
	/**
	 * Generate an ORDER BY clause for a query
	 * 
	 * @param array $orderings
	 * @return string
	 */
	private function _generateOrderClause(array $orderings) : string
	{
		if (empty($orderings)) {
			return "";
		}
		
		$pairs = [];
		foreach ($orderings as $key => $value) {
			if (is_numeric($key)) {
				$pairs[] = $value;
			} else {
				$pairs[] = "`{$key}` {$value}";
			}
		}
		
		return "ORDER BY ". implode(",", $pairs);
	}
	
	/**
	 * Generate a LIMIT clause for a query
	 * 
	 * @param int $maxResults
	 * @param int $resultOffset
	 * @return string
	 */
	private function _generateLimitClause(int $maxResults, int $resultOffset) : string
	{
		if ($maxResults < 1) {
			return "";
		}
		
		return "LIMIT {$maxResults} OFFSET {$resultOffset}";
	}
}
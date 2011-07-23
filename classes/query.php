<?php

class Query {

	private $where = null;
	public $primary_table = null;
	protected $fields = array();

	/**
	 * Construct a Query
	 * @example
	 * $q = new Query('{posts}');
	 * @param $primary_table Name of the primary table (use {table} syntax to expand)
	 */
	public function __construct($primary_table = null)
	{
		$this->primary_table = $primary_table;
	}

	/**
	 * Static helper method to create a new query instance
	 * @example
	 * $q = Query::create('{posts}');;
	 * @static
	 * @param string $primary_table Name of the primary table (use {table} syntax to expand)
	 * @return Query A new instance of the Query class
	 */
	public static function create($primary_table = null)
	{
		return new Query($primary_table);
	}

	public function select($fields)
	{
		$this->fields = array_merge($this->fields, Utils::single_array($fields));
		return $this;
	}

	public function from($primary_table)
	{
		$this->primary_table = $primary_table;
		return $this;
	}

	/**
	 * Create and/or return a QueryWhere object representing the where clause of this query
	 * @param string $operator The operator (AND/OR) to use between expressions in this clause
	 * @return QueryWhere An instance of the where clause for this query
	 */
	public function where($operator = 'AND')
	{
		if(!isset($this->where)) {
			$this->where = new QueryWhere($operator);
		}
		return $this->where;
	}

	/**
	 * Obtain the SQL used to execute this query
	 * @return string The SQL to execute
	 */
	public function get()
	{
		$sql = "SELECT ";
		if(count($this->fields) > 0) {
			$sql .= implode(', ', $this->fields);
		}
		else {
			$sql .= "*";
		}
		$sql .= " FROM " . $this->primary_table;
		$sql .= " WHERE " . $this->where()->get();
		return $sql;
	}

	/**
	 * Obtain the parameter values needed for the query
	 * @return array An associative array containing the parameters of the query
	 */
	public function params()
	{
		return $this->where()->params();
	}

	public static function new_param_name($prefix = null)
	{
		static $param_names = array();

		if(!isset($prefix)) {
			$prefix = 'param';
		}
		if(!isset($param_names[$param])) {
			$param_names[$param] = 0;
		}
		$param_names[$param]++;
		return $prefix . '_' . $param_names[$param];
	}

}

/**
 * QueryWhere
 * Represents a where clause (or subclause) of a Query
 * @see Query
 */
class QueryWhere {
	protected $operator = 'AND';
	protected $expressions = array();
	protected $parameters = array();

	/**
	 * Constructor for the QueryWhere
	 * @param string $operator The operator (AND/OR) to user between expressions in this clause
	 */
	public function __construct($operator = 'AND')
	{
		$this->operator = $operator;
	}

	/**
	 * @param string|QueryWhere $expression A string expression to use as part of the query's where clause or
	 *                                      a compound expression represented by an additional QueryWhere instance
	 * @param array $parameters An associative array of values to use as named parameters in the added expression
	 * @return QueryWhere Returns $this, for fluid interface.
	 */
	public function add($expression, $parameters = array(), $name = null)
	{
		$this->expressions[$name] = $expression;
		$this->parameters = array_merge($this->parameters, $parameters);
		return $this;
	}

	/**
	 * Shortcut to implementing an IN or equality test for one or more values as a new expression
	 * @param $field
	 * @param $values
	 * @param null $paramname
	 * @param null $validator
	 * @param boolean $positive
	 * @return QueryWhere Retruns $this, for fluid interface
	 */
	public function in($field, $values, $paramname = null, $validator = null, $positive = true)
	{
		$expression = $field . ' ';
		if(is_array($values) && count($values) > 1) {
			$in_elements = array();
			if(is_callable($validator)) {
				foreach($values as $value) {
					$in_elements[] = $validator($value);
				}
			}
			else {
				foreach($values as $value) {
					$value_name = Query::new_param_name($paramname);
					$in_elements[] = ':' . $value_name;
					$this->parameters[$value_name] = $value;
				}
			}
			if(!$positive) {
				$expression .= 'NOT ';
			}
			$expression .= 'IN (' . implode(',', $in_elements) . ')';
		}
		else {
			if(is_array($values)) {
				$values = reset($values);
			}
			if(!$positive) {
				$expression .= ' <> ';
			}
			else {
				$expression .= ' = ';
			}

			if(empty($paramname)) {
				$paramname = Query::new_param_name();
			}

			if(is_callable($validator)) {
				$expression .= $validator($values);
			}
			else {
				$expression .= ':' . $paramname;
				$this->parameters[$paramname] = $values;
			}
		}

		$this->expressions[] = $expression;
		return $this;
	}

	/**
	 * Obtain the parameters supplied for the where clause
	 * @return array An associative array of parameters added to this where clause
	 */
	public function params()
	{
		$parameters = $this->parameters;
		foreach($this->expressions as $expression) {
			if($expression instanceof QueryWhere) {
				$parameters = array_merge($parameters, $expression->params());
			}
		}
		return $parameters;
	}

	/**
	 * Set a parameter value, class magic __set method
	 * @param string $name The name of the parameter to set
	 * @param mixed $value The value to set the parameter to
	 * @return mixed The supplied value
	 */
	public function __set($name, $value)
	{
		$this->parameters[$name] = $value;
		return $this->parameters[$name];
	}

	/**
	 * Get a parameter value, class magic __get method
	 * @param string $name The name of the parameter to get
	 * @return mixed The value of the parameter requested
	 */
	public function __get($name)
	{
		return $this->parameters[$name];
	}

	/**
	 * Obtain the where clause as a string to use in a query
	 * @return string The where clause represented by this object
	 */
	public function get()
	{
		$outputs = array();
		foreach($this->expressions as $expression) {
			if($expression instanceof QueryWhere) {
				$outputs[] = $expression->get();
			}
			else {
				$outputs[] = $expression;
			}
		}
		return '(' . implode(' ' . $this->operator . ' ', $outputs) . ')';
	}

}

?>
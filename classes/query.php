<?php

/**
 * Base Query class for building new queries
 * @see Posts::get
 */
class Query {

	/** @var QueryWhere $where Internal QueryWhere object */
	private $where = null;
	public $primary_table = null;
	protected $fields = array();
	protected $joins = array();
	protected $join_params = array();
	protected $limit = null;
	protected $offset = null;
	protected $orderby = null;
	protected $groupby = null;
	protected $having = null;

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

	/**
	 * Adds fields to the SELECT statement
	 * @param array|string $fields A field or list of fields to add to existing select'ed fields
	 * @return Query Returns $this for fluid interface
	 */
	public function select($fields)
	{
		$args = func_get_args();
		foreach($args as $fields) {
			$this->fields = array_merge($this->fields, Utils::single_array($fields));
		}
		return $this;
	}

	/**
	 * Sets fields for the SELECT statement
	 * @param array|string $fields A field or list of fields to set as the fields to select, replaces existing selected fields
	 * @return Query Returns $this for fluid interface
	 */
	public function set_select($fields)
	{
		$this->fields = Utils::single_array($fields);
		return $this;
	}

	/**
	 * Set whether only distinct results should be returned
	 * @param bool $set True if the result set should be distinct
	 * @return Query Returns $this for fluid interface
	 */
	public function distinct($set = true)
	{
		$this->distinct = $set;
		return $this;
	}

	/**
	 * Sets the primary table for the FROM statement
	 * @param string $primary_table The primary table from which to select
	 * @return Query Returns $this for fluid interface
	 */
	public function from($primary_table)
	{
		$this->primary_table = $primary_table;
		return $this;
	}

	/**
	 * Adds a JOIN table
	 * @param string $join The table to create a join with
	 * @param array $parameters An array of parameters on which the JOIN is built
	 * @param string $alias An optional alias for the joined table
	 * @return Query Returns $this for fluid interface
	 */
	public function join($join, $parameters = array(), $alias = null)
	{
		if(empty($alias)) {
			$alias = md5($join);
		}
		$this->joins[$alias] = $join;
		$this->join_params = array_merge($this->join_params, $parameters);
		return $this;
	}

	/**
	 * Discover if a table alias is already JOINed to this query
	 * @param string $alias The name of a JOIN table alias
	 * @return bool true if the alias was used for a join
	 */
	public function joined($alias)
	{
		return array_key_exists($alias, $this->joins);
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
	 * Set the GROUP BY clause
	 * @param string $value The GROUP BY clause
	 * @return Query Returns $this for fluid interface
	 */
	public function groupby($value)
	{
		$this->groupby = empty($value) ? null : $value;
		return $this;
	}

	/**
	 * Set the ORDER BY clause
	 * @param string $value The ORDER BY clause
	 * @return Query Returns $this for fluid interface
	 */
	public function orderby($value)
	{
		$this->orderby = empty($value) ? null : $value;
		return $this;
	}

	public function having($value)
	{
		$this->having = empty($value) ? null : $value;
		return $this;
	}

	/**
	 * Sets the LIMIT
	 * @param integer $value The LIMIT
	 * @return Query Returns $this for fluid interface
	 */
	public function limit($value)
	{
		$this->limit = is_numeric($value) ? intval($value) : null;
		return $this;
	}

	/**
	 * Sets the OFFSET
	 * @param integer $value The OFFSET
	 * @return Query Returns $this for fluid interface
	 */
	public function offset($value)
	{
		$this->offset = is_numeric($value) ? intval($value) : null;
		return $this;
	}

	/**
	 * Obtain the SQL used to execute this query
	 * @return string The SQL to execute
	 */
	public function get()
	{
		$fields = $this->fields;

		// If the orderby has a function in it, try to create a select field for it with an alias
		$orderby = null;
		if(isset($this->orderby)) {
			$orderby = $this->orderby;
			if ( strpos( $orderby, '(' ) !== false ) {
				$orders = explode( ',', $orderby );
				$ob_index = 0;
				foreach ( $orders as $key => $order ) {
					if ( !preg_match( '%(?P<field>.+)\s+(?P<direction>DESC|ASC)%i', $order, $order_matches ) ) {
						$order_matches = array(
							'field' => $order,
							'direction' => '',
						);
					}

					if ( strpos( $order_matches['field'], '(' ) !== false ) {
						$ob_index++;
						$field = 'orderby' . $ob_index;
						$fields[$field] = "{$order_matches['field']} AS $field";
						$orders[$key] = $field . ' ' . $order_matches['direction'];
					}
				}
				$orderby = implode( ', ', $orders );
			}
		}

		$sql = "SELECT \n\t";
		if($this->distinct()) {
			$sql .= "DISTINCT \n\t";
		}
		if(count($fields) > 0) {
			$sql .= implode(",\n\t", $fields);
		}
		else {
			$sql .= "*";
		}
		$sql .= "\nFROM\n\t" . $this->primary_table;
		foreach($this->joins as $join) {
			$sql .= "\n" . $join;
		}
		$where = $this->where()->get();
		if(!empty($where)) {
			$sql .= "\nWHERE\n" . $this->where()->get();
		}

		if(isset($this->groupby)) {
			$sql .= "\nGROUP BY " . $this->groupby;
		}

		if(isset($this->having)) {
			$sql .= "\nHAVING " . $this->having;
		}
		if(isset($orderby)) {
			$sql .= "\nORDER BY " . $orderby;
		}

		if(isset($this->limit)) {
			$sql .= "\nLIMIT " . $this->limit;
			if(isset($this->offset)) {
				$sql .= "\nOFFSET " . $this->offset;
			}
		}

		return $sql;
	}

	/**
	 * Obtain the parameter values needed for the query
	 * @return array An associative array containing the parameters of the query
	 */
	public function params()
	{
		return array_merge($this->where()->params(), $this->join_params);
	}

	/**
	 * Obtain a parameter name with an optionally specified prefix that has not yet been used
	 * @static
	 * @param string $prefix An optional prefix to use for a new parameter
	 * @return string the new parameter
	 */
	public static function new_param_name($prefix = 'param')
	{
		static $param_names = array();

		if(!isset($param_names[$prefix])) {
			$param_names[$prefix] = 0;
		}
		$param_names[$prefix]++;
		return $prefix . '_' . $param_names[$prefix];
	}

	/**
	 * Execute and return the first row of this query
	 * @param string $class The optional class to return results as
	 * @return object The result object, a QueryRecord or the specified class
	 */
	public function row($class = null)
	{
		return DB::get_row($this->get(), $this->params(), $class);
	}

	/**
	 * Execute and return the returns of this query
	 * @param string $class The optional class to return results as
	 * @return array The results array of QueryRecords or the specified class
	 */
	public function results($class = null)
	{
		return DB::get_results($this->get(), $this->params(), $class);
	}

	/**
	 * Execute and return key-value pairs from this query
	 * @return array The results array of QueryRecords or the specified class
	 */
	public function keyvalue()
	{
		return DB::get_keyvalue($this->get(), $this->params());
	}

	/**
	 * Execute and return the first first field from each row of this query
	 * @return array The result array of values
	 */
	public function column()
	{
		return DB::get_column($this->get(), $this->params());
	}

	/**
	 * Execute and return the first row of this query
	 * @return object The result object, a QueryRecord or the specified class
	 */
	public function value()
	{
		return DB::get_value($this->get(), $this->params());
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
	 * Convenience function for fluid interface
	 * @param string $operator The operator (AND/OR) to user between expressions in this clause
	 * @return QueryWhere Configured instance of the QueryWhere
	 */
	public function create($operator = 'AND')
	{
		return new QueryWhere($operator);
	}

	/**
	 * @param string|QueryWhere $expression A string expression to use as part of the query's where clause or
	 *                                      a compound expression represented by an additional QueryWhere instance
	 * @param array $parameters An associative array of values to use as named parameters in the added expression
	 * @param string $name Name of the expression
	 * @return QueryWhere Returns $this, for fluid interface.
	 */
	public function add($expression, $parameters = array(), $name = null)
	{
		if(empty($name)) {
			$name = count($this->expressions) + 1;
		}
		$this->expressions[$name] = $expression;
		$this->parameters = array_merge($this->parameters, $parameters);
		return $this;
	}

	/**
	 * Shortcut to implementing an IN or equality test for one or more values as a new expression
	 * @param $field
	 * @param $values
	 * @param string $paramname
	 * @param callback $validator
	 * @param boolean $positive
	 * @return QueryWhere Returns $this, for fluid interface
	 */
	public function in($field, $values, $paramname = null, $validator = null, $positive = true)
	{
		$expression = $field . ' ';
		if($values instanceof Query) {
			if( !$positive ) {
				$expression .= 'NOT ';
			}
			$expression .= 'IN (' . $values->get() . ')';
			$this->parameters = array_merge( $this->parameters, $values->params() );
		}
		elseif(is_array($values) && count($values) > 1) {
			$in_elements = array();
			if(is_callable($validator)) {
				foreach($values as $value) {
					$newvalue = $validator($value);
					if(!empty($newvalue)) {
						$in_elements[] = $newvalue;
					}
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

		if(empty($paramname)) {
			$paramname = count($this->expressions) + 1;
		}

		$this->expressions[$paramname] = $expression;
		return $this;
	}

	/**
	 * Shortcut to implementing an EXISTS test for one or more values as a new expression
	 * @param Query $values
	 * @param string $paramname
	 * @param boolean $positive
	 * @return QueryWhere Returns $this, for fluid interface
	 */
	public function exists( Query $values, $paramname = null, $positive = true )
	{
		$expression = '';

		if( !$positive ) {
			$expression .= 'NOT ';
		}

		$expression .= 'EXISTS (' . $values->get() . ')';

		$this->parameters = array_merge( $this->parameters, $values->params() );

		if( empty( $paramname ) ) {
			$paramname = count( $this->expressions ) + 1;
		}

		$this->expressions[$paramname] = $expression;
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
			if($expression instanceof Query) {
				$parameters = array_merge($parameters, $expression->params());
			}
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
	 * @param int $level Used internally to retain indenting
	 * @return string The where clause represented by this object
	 */
	public function get($level = 0)
	{
		$outputs = array();
		$indents = str_repeat("\t", $level);
		if(count($this->expressions) == 0) {
			return null;
		}
		foreach($this->expressions as $expression) {
			if($expression instanceof Query) {
				$outputs[] = $expression->get();
			}
			if($expression instanceof QueryWhere) {
				$outputs[] = $expression->get($level + 1);
			}
			else {
				$outputs[] = $indents . "\t" .  $expression;
			}
		}
		$outputs = array_filter($outputs);
		$output = implode("\n" . $indents . $this->operator . "\n", $outputs);
		if($level == 0) {
			return $output;
		}
		return $indents . "(\n" . $output . "\n" . $indents . ")";
	}

	/**
	 * Get the number of expressions contained in this QueryWhere
	 * @return int Number of expressions.
	 */
	public function count()
	{
		return count($this->expressions);
	}

}

?>
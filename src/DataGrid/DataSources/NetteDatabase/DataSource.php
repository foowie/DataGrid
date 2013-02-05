<?php

namespace DataGrid\DataSources\NetteDatabase;


class DataSource extends \DataGrid\DataSources\DataSource {

	/**
	 * @var \Nette\Database\Table\Selection
	 */
	private $ts;

	/**
	 * @var int Total data count
	 */
	private $count = null;

	/**
	 * @var array Keys are column names and values are restrictions
	 */
	private $filterNames = array();
	
	/**
	 * Name of key of selection
	 */
	private $keyName;

	/**
	 * @var type array|null
	 */
	private $defaultSorting = null;	
	
	/**
	 * @param \Nette\Database\Table\Selection
	 */
	public function __construct(\Nette\Database\Table\Selection $ts, $keyName = 'id') {
		$this->ts = $ts;
		$this->keyName = $keyName;
	}

	/**
	 * Get list of columns available in datasource
	 * @return array
	 */
	public function getColumns() {
		throw new \Nette\NotSupportedException();
	}

	/**
	 * Does datasource have column of given name?
	 * @return boolean
	 */
	public function hasColumn($name) {
		throw new \Nette\NotSupportedException();
	}

	public function addFilter($name, $to) {
		$this->filterNames[$name] = $to;
		return $this;
	}
	
	public function setFilters(array $filters) {
		$this->filterNames = $filters;
	}
	
	/**
	 * Add filtering onto specified column
	 * @param string column name
	 * @param string filter
	 * @param string|array operation mode
	 * @param string chain type (if third argument is array)
	 * @throws InvalidArgumentException
	 * @return IDataSource
	 */
	public function filter($column, $operation = \DataGrid\DataSources\IDataSource::EQUAL, $value = NULL, $chainType = NULL) {
		if(!\Nette\Utils\Strings::contains($column, '.')) { // columns with same name exists
			$column = $this->ts->getName() . '.' . $column;
		}
		$this->count = null;		
		if(isset($this->filterNames[$column]))
			$column = $this->filterNames[$column];
		if (is_array($operation)) {
			if ($chainType !== self::CHAIN_AND && $chainType !== self::CHAIN_OR) {
				throw new \Nette\InvalidArgumentException('Invalid chain operation type.');
			}
			$conds = array();
			foreach ($operation as $t) {
				$this->validateFilterOperation($t);
				if ($t === self::IS_NULL || $t === self::IS_NOT_NULL) {
					$conds[] = array($column . ' ' . $t);
				} else {
					if ($operation === self::LIKE || $operation === self::NOT_LIKE)
						$value = \DataGrid\DataSources\Utils\WildcardHelper::formatLikeStatementWildcards($value);

					$conds[] = array($column . ' ' . $t . ' ?', $value);
				}
			}
			if ($chainType === self::CHAIN_AND) {
				foreach ($conds as $cond) {
					if (isset($cond[1]))
						$this->ts->where($cond[0], $cond[1]);
					else
						$this->ts->where($cond[0]);
				}
			} elseif ($chainType === self::CHAIN_OR) {
				$keys = array();
				$values = array();
				foreach ($conds as $cond) {
					$keys[] = $cond[0];
					if (isset($cond[1]))
						$values[] = $cond[1];
				}
				$this->ts->where('(' . implode(') OR (', $keys) . ')', $values);
			}
		} else {
			$this->validateFilterOperation($operation);

			if ($operation === self::IS_NULL || $operation === self::IS_NOT_NULL) {
				$this->ts->where($column . ' ' . $operation);
			} else {
				if ($operation === self::LIKE || $operation === self::NOT_LIKE)
					$value = \DataGrid\DataSources\Utils\WildcardHelper::formatLikeStatementWildcards($value);
				$this->ts->where($column . ' ' . $operation . ' ?', $value);
			}
		}

		return $this;
	}

	/**
	 * Adds ordering to specified column
	 * @param string column name
	 * @param string one of ordering types
	 * @throws InvalidArgumentException
	 * @return IDataSource
	 */
	public function sort($column, $order = \DataGrid\DataSources\IDataSource::ASCENDING) {
		$this->ts->order($column . ($order === self::ASCENDING ? ' ASC' : ' DESC'));
		$this->defaultSorting = null;
		return $this;
	}

	public function setDefaultSorting($column, $order = \DataGrid\DataSources\IDataSource::ASCENDING) {
		$this->defaultSorting = $column === null ? null : array($column, $order);
	}	
	
	/**
	 * Reduce the result starting from $start to have $count rows
	 * @param int the number of results to obtain
	 * @param int the offset
	 * @throws OutOfRangeException
	 * @return IDataSource
	 */
	public function reduce($count, $start = 0) {
		if ($count < 0 || $start < 0)
			throw new \OutOfRangeException();

		$this->ts->limit($count == null ? 1 : $count, $start == null ? 0 : $start);

		return $this;
	}

	/**
	 * Get iterator over data source items
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->fetch());
	}

	/**
	 * Fetches and returns the result data.
	 * @return array
	 */
	public function fetch() {
		if($this->defaultSorting !== null) {
			$this->sort($this->defaultSorting[0], $this->defaultSorting[1]);
		}		
		$result = array();
		foreach ($this->ts as $item)
			$result[] = $item->toArray();
		return $result;
	}

	/**
	 * Count items in data source
	 * @return integer
	 * @todo: if there is a group by clause in the query, count it correctly
	 */
	public function count() {
		if($this->count === null)
			$this->count = $this->ts->count("*");
		return $this->count;
	}

	/**
	 * Return distinct values for a selectbox filter
	 * @param string Column name
	 * @return array
	 */
	public function getFilterItems($column) {
		throw new \Nette\NotImplementedException();
	}

	/**
	 * Clone dibi fluent instance
	 * @return void
	 */
	public function __clone() {
		$this->ts = clone $this->ts;
	}
	
	public function getKeyName() {
		return $this->keyName;
	}

}
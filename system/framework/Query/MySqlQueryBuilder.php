<?php


namespace Xaveere\framework\Query;

use \PDO;
use Xaveere\framework\Connectors\Connector;

class MySqlQueryBuilder extends Connector implements QueryBuilder
{

    protected $query;

    protected $table;

    public function __construct()
    {
        $this->database = parent::make();
        return $this->database;
    }


    protected function reset(): void
    {
        $this->query = new \stdClass;
    }

    /**
     * @param string $table
     * @param array $fields
     * @return QueryBuilder
     */
    public function select(array $fields = []): QueryBuilder
    {
        $this->reset();
        $this->query->base = "SELECT " . implode(", ", $fields) . " FROM " . $this->table;
        $this->query->type = 'select';

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $operator
     * @return QueryBuilder
     * @throws \Exception
     */
    public function where(string $field, string $value, string $operator = '='): QueryBuilder
    {
        if (!in_array($this->query->type, ['select', 'update'])) {
            throw new \Exception("WHERE can only be added to SELECT OR UPDATE");
        }
        $this->query->where[] = "$field $operator '$value'";

        return $this;
    }

    /**
     * @param int $start
     * @param int $offset
     * @return QueryBuilder
     * @throws \Exception
     */
    public function limit(int $start, int $offset): QueryBuilder
    {
        if (!in_array($this->query->type, ['select'])) {
            throw new \Exception("LIMIT can only be added to SELECT");
        }
        $this->query->limit = " LIMIT " . $start . ", " . $offset;
        $this->database->prepare($this->query->limit);

        return $this;
    }

    public function columnNames() : QueryBuilder
    {
        $this->reset();
        $this->query->base = "SHOW COLUMNS FROM {$this->table}";

        return $this;
    }
    /**
     * Get the final query string.
     */
    public function getSQL(): string
    {
        $query = $this->query;
        $sql = $query->base;
        if (!empty($query->where)) {
            $sql .= " WHERE " . implode(' AND ', $query->where);
        }
        if (isset($query->limit)) {
            $sql .= $query->limit;
        }
        $sql .= ";";
        return $sql;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): string
    {
        $this->table = $table;
    }

    public function first()
    {
        return $this->database->query($this->getSQL())->fetch(PDO::FETCH_OBJ);
    }

    public function toArray()
    {
        return $this->database->query($this->getSQL())->fetchAll();
    }

    public function get()
    {
        return $this->database->query($this->getSQL())->fetchAll(PDO::FETCH_OBJ);
    }

}
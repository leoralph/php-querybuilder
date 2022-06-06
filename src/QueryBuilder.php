<?php

namespace LeoRalph\QueryBuilder;

use LeoRalph\QueryBuilder\Exceptions\QueryBuilderException;

class QueryBuilder
{
    private string $table;
    private string $type;
    private string $main;
    private array $joins = [];
    private array $conditions;
    private array $order = [];
    private string $limit;
    private array $params = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function insert(array $values)
    {
        $this->type = "INSERT";
        $columns = implode(',', array_keys($values));
        $placeholders = str_repeat('?,', count($values)-1) . '?';
        $this->main = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->params = array_values($values);
        return $this;
    }

    public function select(array $columns = ['*'])
    {
        $this->type = "SELECT";
        $this->main = "SELECT " . implode(',', $columns) . " FROM {$this->table}";
        return $this;
    }

    public function update(array $values)
    {
        $this->type = "UPDATE";
        $update = "UPDATE {$this->table} SET";
        $valores = [];

        foreach ($values as $coluna => $valor) {
            $update .= " $coluna = ?,";
            $valores[] = $valor;
        }

        $this->main = rtrim($update, ',');
        $this->params = $valores;

        return $this;
    }

    public function delete()
    {
        $this->type = "DELETE";
        $this->main = "DELETE FROM {$this->table}";
        return $this;
    }

    public function where(string $column, string|int $value, string $operator = '=')
    {
        if ($this->type == "INSERT") {
            throw new QueryBuilderException("CANNOT USE CONDITIONS INSIDE INSERT QUERY");
        }

        $this->conditions = ["WHERE $column $operator ?"];
        $this->params[] = $value;
        return $this;
    }

    public function and(string $column, string|int $value, string $operator = '=')
    {
        if (empty($this->conditions)) {
            throw new QueryBuilderException("CANNOT USE AND/OR BEFORE WHERE");
        }
        $this->conditions[] = "AND $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function or(string $column, string|int $value, string $operator = '=')
    {
        if (empty($this->conditions)) {
            throw new QueryBuilderException("CANNOT USE AND/OR BEFORE WHERE");
        }
        $this->conditions[] = "OR $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values)
    {
        if ($this->type == "INSERT") {
            throw new QueryBuilderException("CANNOT USE CONDITIONS INSIDE INSERT QUERY");
        }

        $placeholders = str_repeat('?,', count($values)-1) . '?';
        $this->conditions = ["WHERE $column IN ($placeholders)"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function andIn(string $column, array $values)
    {
        if (empty($this->conditions)) {
            throw new QueryBuilderException("CANNOT USE AND/OR BEFORE WHERE");
        }
        $placeholders = str_repeat('?,', count($values)-1) . '?';
        $this->conditions[] = "AND $column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }
    
    public function orIn(string $column, array $values)
    {
        if (empty($this->conditions)) {
            throw new QueryBuilderException("CANNOT USE AND/OR BEFORE WHERE");
        }
        $placeholders = str_repeat('?,', count($values)-1) . '?';
        $this->conditions[] = "OR $column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function leftJoin(string $table, string $t1Column, string $t2Column, string $operator = '=')
    {
        if ($this->type != "SELECT") {
            throw new QueryBuilderException("CANNOT USE JOINS OUTSIDE SELECT QUERY");
        }

        $this->joins[] = "LEFT JOIN $table ON $t1Column $operator $t2Column";
        return $this;
    }

    public function rightJoin(string $table, string $t1Column, string $t2Column, string $operator = '=')
    {
        if ($this->type != "SELECT") {
            throw new QueryBuilderException("CANNOT USE JOINS OUTSIDE SELECT QUERY");
        }

        $this->joins[] = "RIGHT JOIN $table ON $t1Column $operator $t2Column";
        return $this;
    }

    public function innerJoin(string $table, string $t1Column, string $t2Column, string $operator = '=')
    {
        if ($this->type != "SELECT") {
            throw new QueryBuilderException("CANNOT USE JOINS OUTSIDE SELECT QUERY");
        }
        $this->joins[] = "INNER JOIN $table ON $t1Column $operator $t2Column";
        return $this;
    }

    public function order(string $column, $order = 'asc')
    {
        if ($this->type != "SELECT") {
            throw new QueryBuilderException("CANNOT USE ORDER OUTSIDE SELECT QUERY");
        }
        $this->order[] = "$column " . strtoupper($order);
        return $this;
    }

    public function limit(int $limit)
    {
        if ($this->type != "SELECT") {
            throw new QueryBuilderException("CANNOT USE LIMIT OUTSIDE SELECT QUERY");
        }
        $this->limit = "LIMIT $limit";
    }

    public function build()
    {
        $query = $this->main;
        $retorno = [];

        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->conditions)) {
            $query .= ' ' . implode(' ', $this->conditions);
        }

        if (!empty($this->order)) {
            $query .= " ORDER BY " . implode(',', $this->order);
        }

        if (!empty($this->limit)) {
            $query .= " {$this->limit}";
        }

        $retorno = [
            'query' => $query,
            'params' => $this->params
        ];

        if (substr_count($query, '?') != count($this->params)) {
            throw new QueryBuilderException("INVALID NUMBER OF QUERY PARAMETERS PASSED");
        }

        return $this->query = $retorno;
    }

    public function __toString()
    {
        return $this->build();
    }

}

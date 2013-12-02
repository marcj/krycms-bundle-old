<?php

namespace Kryn\CmsBundle\Controller\Admin;

use RestService\Server;

/**
 * RestController for the entry points which are from type store or framework window.
 *
 */
class Store extends Server
{
    public $entryPoint;

    public $itemsMaxCount = 5;

    /**
     * The table name, if you want to use auto-sql.
     * Without prefix.
     *
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $tableLabel;

    /**
     * @var string
     */
    public $tableKey;

    /**
     * Additional where clauses started with 'AND '
     */
    public $where;

    /**
     * The whole SQL (use %pfx% as prefix placeholder) if you want to use a own sql.
     *
     * @var string
     */
    public $sql;

    /**
     * The default order
     *
     * @var string
     */
    public $order;

    /**
     * Order direction `asc` or `desc`.
     *
     * @var string
     */
    public $orderDirection;

    public function exceptionHandler($exception)
    {
        if (get_class($exception) != 'AccessDeniedException') {
            \Core\Utils::exceptionHandler($exception);
        }
    }

    public function setEntryPoint($entryPoint)
    {
        $this->entryPoint = $entryPoint;
    }

    public function run()
    {
        $this
            ->addGetRoute('', 'getItems')
            ->addGetRoute('([^/]+)', 'getItem');

        //run parent
        parent::run();
    }

    public function getItem($id)
    {
        $res = array();
        if (!$this->table) {
            return $res;
        }
        $table = database::getTable($this->table);

        $id2 = $id;
        if ($id2 + 0 > 0) {
            $id2 += 0;
        } else {
            $id2 = "'" . esc($id2) . "'";
        }

        $sql = ' SELECT ' . $this->tableKey . ' as id, ' . $this->tableLabel . ' as label
            FROM ' . $table . '
            WHERE ' . $this->tableKey . ' = ' . $id2;

        $res = dbExfetch($sql, 1);

        return $res;
    }

    /**
     * Returns a where clausel without "WHERE "
     *
     * @return string
     */

    public function getItemsWhere()
    {
        return '';
    }

    public function getSearchWhere()
    {
        if (getArgv('search')) {
            $search = strtolower(getArgv('search', 1));

            return ' AND LOWER(' . $this->tableLabel . ") LIKE '$search%' ";
        }

        return '';
    }

    public function getLimit($from, $count)
    {
        $limit = '';
        if ($from > 0) {
            $limit = 'OFFSET ' . $from;
        }

        if ($count > 0) {
            $limit .= ' LIMIT ' . $count;
        }

        return $limit;
    }

    /**
     * @param  int   $offset
     * @param  int   $limit
     *
     * @return array
     */
    public function getItems($offset = 0, $limit = 0)
    {
        $res = array();
        $offset += 0;
        $limit += 0;

        if (!$this->table && !$this->sql) {
            throw new \MisconfigurationException('`table` or `sql` shall be defined.');
        }

        $limit2 = $this->getLimit($offset, $limit);

        if ($this->sql) {
            $sql = $this->sql . $limit2;
        } else {
            $table = pfx . $this->table;

            $where = $this->where;
            if (!$where) {
                $where = $this->getItemsWhere();
            }

            $where .= $this->getSearchWhere();
            $sql = ' SELECT ' . $this->tableKey . ', ' . $this->tableLabel . '
            FROM ' . $table .
                ' WHERE 1=1 ' . $where
                . $limit2;
        }

        $dbRes = dbQuery($sql);
        while ($row = dbFetch($dbRes)) {
            $res[$row[$this->tableKey]] = array('label' => $row[$this->tableLabel]);
        }

        dbFree($dbRes);

        return $res;
    }

    public function setWhere($where)
    {
        $this->where = $where;
    }

    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param string $tableLabel
     */
    public function setTableLabel($tableLabel)
    {
        $this->tableLabel = $tableLabel;
    }

    /**
     * @return string
     */
    public function getTableLabel()
    {
        return $this->tableLabel;
    }

    /**
     * @param string $tableKey
     */
    public function setTableKey($tableKey)
    {
        $this->tableKey = $tableKey;
    }

    /**
     * @return string
     */
    public function getTableKey()
    {
        return $this->tableKey;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $orderDirection
     */
    public function setOrderDirection($orderDirection)
    {
        $this->orderDirection = $orderDirection;
    }

    /**
     * @return string
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

}

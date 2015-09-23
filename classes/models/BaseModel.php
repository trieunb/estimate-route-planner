<?php
/**
 * The base model class for simple CRUD on a single table
 * @author Lht
 *
 */
abstract class BaseModel {
    public static $db;

    protected $tableName;
    protected $fillable = [];

    public final function __construct() {
        if (!self::$db) {
            try {
                self::$db = new PDO(
                    'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST,
                    DB_USER,
                    DB_PASSWORD,
                    [PDO::ATTR_PERSISTENT => true]
                );
                self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
                self::$db->setAttribute( PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
            } catch (PDOException $e) {
                die("Couldn't connect to the database.");
            }
        }
        $this->tableName = $this->getTableName();
    }

    public abstract function getTableName();

    /**
     * Filter data base on $fillable when update or insert
     * Note: if $fillable is empty, all inputs will be accepted
     */
    protected function filterAttrs($inputs) {
        $filteredInputs = [];
        if (count($this->fillable) > 0) {
            foreach ($this->fillable as $attr) {
                if (isset($inputs[$attr])) {
                    $filteredInputs[$attr] = $inputs[$attr];
                }
            }
            return $filteredInputs;
        } else {
            return $inputs;
        }
    }

    public function getLastInsertId() {
        return self::$db->lastInsertId();
    }
    /**
     * Execute a given raw SQl statement
     *
     * @return int
     */
    public function exec($sql) {
        $result = self::$db->exec($sql);
        if($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Execute SQl and fetch all rows
     *
     * @return array
     */
    public function query($sql) {
        $res = self::$db->query($sql, PDO::FETCH_ASSOC);
        if($res) {
            return $res->fetchAll();
        } else {
            return [];
        }
    }

    protected function buildSelectStatement(array $conditions) {
        $sql = 'SELECT * FROM ' . $this->tableName;
        if(!empty($conditions)) {
            $sql .= ' WHERE ';
            $sql .= $this->buildWhereAND($conditions);
        }
        return $sql;
    }

    protected function buildSelectColumnsStatement(array $columns, array $conditions){
        $columns_name = '';
        for ($i=0; $i < count($columns); $i++) {
            if ($i == (count($columns) - 1)) {
                $columns_name .= ' ' .$columns[$i];
            }else {
                $columns_name .= ' ' .$columns[$i]. ',';
            }
        }
        $sql = 'SELECT' .$columns_name. ' FROM ' .$this->tableName;
        if(!empty($conditions)) {
            $sql .= ' WHERE ';
            $sql .= $this->buildWhereAND($conditions);
        }
        return $sql;
    }

    protected function buildInsertStatement(array $safeData) {
        $columns = array_keys($safeData);
        $values  = array_values($safeData);

        $santizedValues = [];

        foreach($values as $index => $v) {
            if (is_null($v)) {
                array_push($santizedValues, "NULL");
            } elseif(is_scalar($v)) {
                if (is_bool($v)) {
                    array_push($santizedValues, ($v ? '1' : '0'));
                } else {
                    array_push($santizedValues, "'" . addslashes($v) . "'");
                }
            } else {
              throw new Exception("Invalid value for column `{$columns[$index]}`");
            }
        }

        $sql = 'INSERT INTO ' . $this->tableName .
            '(' . implode($columns, ',') . ')' .
            ' VALUES ' .
            '(' . implode($santizedValues, ',') . ')';
        return $sql;
    }

    protected function buildWhereAND(array $conditions) {
        $colValPairs = [];
        foreach($conditions as $col => $val) {
            if(is_null($val)) {
                array_push($colValPairs, $col . " is NULL");
            } elseif(is_array($val)) { // WHERE IN
                array_push($colValPairs, $col . " IN (" . implode(",", $val) . ")");
            } elseif(is_bool($val)) {
                array_push($colValPairs, $col . "='" . ($val ? 1 : 0) . "'");
            } else {
                array_push($colValPairs, $col . "='" . $val . "'");
            }
        }
        return implode($colValPairs, " AND ");
    }

    protected function buildUpdateStatement(array $safeData, array $conditions) {
        $sql = 'UPDATE ' . $this->tableName . ' SET ';
        $colValPairs = [];
        foreach($safeData as $col => $val) {
            if(is_null($val)) {
                array_push($colValPairs, $col . "=NULL");
            } elseif(is_bool($val)) {
                array_push($colValPairs, $col . "='" . ($val ? 1 : 0) . "'");
            } else {
                array_push($colValPairs, $col . "='" . addslashes($val) . "'");
            }
        }
        $sql .= implode($colValPairs, ',');
        if($conditions) {
            $sql .= ' WHERE ' . $this->buildWhereAND($conditions);
        }
        return $sql;
    }

    protected function buildDeleteStatement(array $conditions) {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE ';
        $sql .= $this->buildWhereAND($conditions);
        return $sql;
    }

    /**
     * Get first record
     * @return array
    */
    public function first() {
      $selectStmt = $this->buildSelectStatement([]);
      $selectStmt .= ' LIMIT 1';
      $res = $this->query($selectStmt);
      if(isset($res[0])) {
          return $res[0];
      } else {
          return NULL;
      }
    }

    /**
     * Get all records
     * @return array
    */
    public function all() {
        return $this->query(
            $this->buildSelectStatement([])
        );
    }

    public function getAllWithColumns(array $columns, array $conditions){
        return $this->query(
            $this->buildSelectColumnsStatement($columns, $conditions)
        );
    }

    /**
     * Find records with conditions
     *
     * Note: It's just only `AND` all the conditions with equal comparisons.
     *
     * SomeModel#findBy([
     *   'employee_id' => 60,
     *   'role' => 1
     * ]);
     *
     *
     * @param $cons array
     * @return array
    */
    public function where(array $cons) {
        return $this->query(
            $this->buildSelectStatement($cons)
        );
    }

    /**
     * Find the first record matched given conditions
     * LIKE where() but return the first record if exists
     *
     * SomeModel#findBy([
     *   'employee_id' => 60
     * ]);
     *
     * @param $cons array
     * @return mixed(array | NULL)
    */
    public function findBy(array $cons) {
        $res = $this->query(
            $this->buildSelectStatement($cons)
        );
        if(isset($res[0])) {
            return $res[0];
        } else {
            return NULL;
        }
    }

    /**
     * Insert new record
     *
     * Example:
     * SomeModel#insert([
     *   'employee_id' => 60,
     *   'created_at' => date('Y-m-d h:i:s')
     * ]);
     *
     * @param $data array
     * @return integer
     *
    */
    public function insert(array $data) {
        return $this->exec(
            $this->buildInsertStatement($this->filterAttrs($data))
        );
    }

    /**
     * Update records with data and conditions
     *
     * Example:
     * SomeModel#update(
     *  [
     *   'confirmed' => date('Y-m-d h:i:s')
     *  ],
     *  [
     *   'employee_id' => 60
     *  ]
     * );
     *
     * @param $data array
     * @param $cons array
     * @return integer
     *
    */
    public function update(array $data, array $cons = []) {
        return $this->exec(
            $this->buildUpdateStatement($this->filterAttrs($data), $cons)
        );
    }

    /**
     * Delete records with conditions
     *
     * Example:
     * SomeModel::delete(
     *  [
     *   'employee_id' => 60
     *  ]
     * );
     * @param $cons array
     * @return integer
    */
    public function delete(array $cons = []) {
        return $this->exec(
            $this->buildDeleteStatement($cons)
        );
    }
  }
?>

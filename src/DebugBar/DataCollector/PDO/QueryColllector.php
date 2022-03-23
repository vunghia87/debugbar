<?php

namespace DebugBar\DataCollector\PDO;

use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\Supports\Utils;

class QueryColllector extends PDOCollector
{
    protected $queries = [];
    protected $showHints = false;

    /**
     * Show or hide the hints in the parameters
     *
     * @param boolean $enabled
     */
    public function setShowHints($enabled = true)
    {
        $this->showHints = $enabled;
    }
    /**
     * @param TracedStatement $stmt
     * @return array|void
     */
    public function addQuery(\DebugBar\DataCollector\PDO\TracedStatement $stmt)
    {
        $query = $this->renderSqlWithParams ? $stmt->getSqlWithParams($this->sqlQuotationChar) : $stmt->getSql();
        foreach ($this->skip as $skip) {
            preg_match('/\s+from\s+`?([a-z\d_]+)`?/i', strtolower($query), $matches);
            if (!empty($matches[0]) && strpos($query, $skip) || $skip == $query) {
                return [];
            }
        }
        $hints = $this->performQueryAnalysis($query);
        $source = array_values($stmt->getDebugTrace());
        $item = array(
            'sql' => $this->getDataFormatter()->formatSql($query),
            'row_count' => $stmt->getRowCount(),
            'stmt_id' => $stmt->getPreparedId(),
            'prepared_stmt' => $stmt->getSql(),
            'params' => (object)$stmt->getParameters(),
            'duration' => $stmt->getDuration(),
            'duration_str' => $this->getDataFormatter()->formatDuration($stmt->getDuration()),
            'memory' => $stmt->getMemoryUsage(),
            'memory_str' => $this->getDataFormatter()->formatBytes($stmt->getMemoryUsage()),
            'end_memory' => $stmt->getEndMemory(),
            'end_memory_str' => $this->getDataFormatter()->formatBytes($stmt->getEndMemory()),
            'is_success' => $stmt->isSuccess(),
            'error_code' => $stmt->getErrorCode(),
            'error_message' => $stmt->getErrorMessage(),
            'hints' => $this->showHints ? $hints : null,
            'match' => false,
            'backtrace' => $source,
        );

        foreach ($this->listen as $listen) {
            if (strpos($query, $listen)) {
                $item['match'] = true;
            }
        }

        return $item;
    }
    
    /**
     * Explainer::performQueryAnalysis()
     *
     * Perform simple regex analysis on the code
     *
     * @package xplain (https://github.com/rap2hpoutre/mysql-xplain-xplain)
     * @author e-doceo
     * @copyright 2014
     * @version $Id$
     * @access public
     * @param string $query
     * @return string[]
     */
    protected function performQueryAnalysis($query)
    {
        // @codingStandardsIgnoreStart
        $hints = [];
        if (preg_match('/^\\s*SELECT\\s*`?[a-zA-Z0-9]*`?\\.?\\*/i', $query)) {
            $hints[] = 'Use <code>SELECT *</code> only if you need all columns from table';
        }
        if (preg_match('/ORDER BY RAND()/i', $query)) {
            $hints[] = '<code>ORDER BY RAND()</code> is slow, try to avoid if you can.
                You can <a href="http://stackoverflow.com/questions/2663710/how-does-mysqls-order-by-rand-work" target="_blank">read this</a>
                or <a href="http://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function" target="_blank">this</a>';
        }
        if (strpos($query, '!=') !== false) {
            $hints[] = 'The <code>!=</code> operator is not standard. Use the <code>&lt;&gt;</code> operator to test for inequality instead.';
        }
        if (stripos($query, 'WHERE') === false && preg_match('/^(SELECT) /i', $query)) {
            $hints[] = 'The <code>SELECT</code> statement has no <code>WHERE</code> clause and could examine many more rows than intended';
        }
        if (preg_match('/LIMIT\\s/i', $query) && stripos($query, 'ORDER BY') === false) {
            $hints[] = '<code>LIMIT</code> without <code>ORDER BY</code> causes non-deterministic results, depending on the query execution plan';
        }
        if (preg_match('/LIKE\\s[\'"](%.*?)[\'"]/i', $query, $matches)) {
            $hints[] = 'An argument has a leading wildcard character: <code>' . $matches[1] . '</code>.
                The predicate with this argument is not sargable and cannot use an index if one exists.';
        }
        return $hints;

        // @codingStandardsIgnoreEnd
    }

    /**
     * Collects data from a single TraceablePDO instance
     *
     * @param TraceablePDO $pdo
     * @param TimeDataCollector $timeCollector
     * @param string|null $connectionName the pdo connection (eg default | read | write)
     * @return array
     */
    protected function collectPDO(TraceablePDO $pdo, TimeDataCollector $timeCollector = null, $connectionName = null)
    {
        if (empty($connectionName) || $connectionName == 'default') {
            $connectionName = 'pdo';
        } else {
            $connectionName = 'pdo ' . $connectionName;
        }
        $stmts = array();
        /** @var \DebugBar\DataCollector\PDO\TracedStatement $stmt */
        foreach ($pdo->getExecutedStatements() as $stmt) {
            if (!empty($item = $this->addQuery($stmt))) {
                $stmts[] = $item;
                if ($timeCollector !== null) {
                    $timeCollector->addMeasure($stmt->getSql(), $stmt->getStartTime(), $stmt->getEndTime(), array(), $connectionName);
                }
            }
        }

        return array(
            'nb_statements' => count($stmts),
            'nb_failed_statements' => count($pdo->getFailedExecutedStatements()),
            'accumulated_duration' => $pdo->getAccumulatedStatementsDuration(),
            'accumulated_duration_str' => $this->getDataFormatter()->formatDuration($pdo->getAccumulatedStatementsDuration()),
            'memory_usage' => $pdo->getMemoryUsage(),
            'memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'peak_memory_usage' => $pdo->getPeakMemoryUsage(),
            'peak_memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'statements' => $stmts
        );
    }
}
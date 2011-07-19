<?php

namespace FormFactory\Adapter;

interface AdapterInterface
{

    /**
     * Returns column names from an entity / model
     * @param Entity Name
     * @return array
     */
    public function getColumns($entityName);


    /**
     * Returns column definitions including
     * fieldName | type | length | precision | scale | nullable | unique | columnName | id
     * @param string Entity Name
     * @param string Column Name
     * @return array associative key = columnName, value = fieldName
     */
    public function getColumnDefinitions($entityName, $columnName);


    /**
     *
     * Return data from persistence layer
     * @param string $entityName
     * @param string $fields
     * @param array $filters
     */
    public function getDataFromEntity($entityName, $fields, $filters);
}
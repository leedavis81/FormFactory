<?php

namespace FormFactory\Adapter;

use FormFactory\Config\General;

class Doctrine2 implements AdapterInterface
{

    protected $entity = null;

	/**
	 *  @var Doctrine\ORM\EntityManager
	 */
    protected $em;


    public function getColumns($entityName)
    {
        $metaData = $this->getEntityManager()->getClassMetadata($entityName);
        if (!empty($metaData->fieldNames))
        {
            return $metaData->fieldNames;
        }
        return array();
    }

    public function getColumnDefinitions($entityName, $columnName)
    {
        if (!class_exists($entityName, true))
        {
            throw new \FormFactory\Config\Exception('Class ' . $entityName . ' doesn\'t exist');
        }

        $metaData = $this->getEntityManager()->getClassMetadata($entityName);
        if (isset($metaData->fieldMappings[$columnName]))
        {
            return $metaData->fieldMappings[$columnName];
        }
        return array();
    }

    public function getDataFromEntity($entityName, $fields, $filters = null)
    {
        $className = General::getInstance()->getEntityClassName($entityName);

        $dql = 'SELECT d.' . implode(', d.', $fields) . ' FROM ' . $className . ' d';

        if (!is_null($filters))
        {
            foreach ($filters as $filterName => $filterValue)
            {
                if (strstr($dql, 'WHERE') == false)
                {
                    $dql .= ' WHERE d.' . $filterName . ' = \'' . $filterValue . '\'';
                } else
                {
                    $dql .= ' AND d.' . $filterName . ' = \'' . $filterValue . '\'';
                }
            }
        }
        $query = $this->em->createQuery($dql);
        return $query->getArrayResult();
    }

    public function setEntitiyManager($em)
    {
        if (!isset($this->em))
        {
            $this->em = $em;
        }
    }

    public function getEntityManager()
    {
        if (isset($this->em))
        {
            return $this->em;
        }
        throw new \FormFactory\Config\Exception('Entity Manager has not been set to Doctrine2 adapter');
    }
}
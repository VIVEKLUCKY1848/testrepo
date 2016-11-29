<?php

/**
 * Drop this into the shell directory in the Magento root and run with -h to see all options.
 */

require_once 'abstract.php';

/**
 * Fix duplicate url keys for categories and products to work with the 1.8 alpha1 CE url key constraints.
 * Run this if the URL Rewrite index does not want to run.
 *
 * @author    Vinai Kopp <vinai@netzarbeiter.com>
 * @author    Fabrizio Branca <mail@{firstname}-{lastname}.de>
 * @author    Erik Dannenberg <erik.dannenberg@bbe-consulting.de>
 */
class Netzarbeiter_Fix_UrlKeys extends Mage_Shell_Abstract
{
    /** @var Mage_Eav_Model_Entity_Attribute */
    protected $_attr;
    /** @var string */
    protected $_qualifyAttrCode;
    /** @var Varien_Db_Adapter_Pdo_Mysql */
    protected $_connection;
    /** @var string */
    protected $_table;

    public function run()
    {
        $this->_showHelp();
        $dupesCat = $this->_gatherDupeUrlKeys('categories'); 
        $dupesProd = $this->_gatherDupeUrlKeys();
        if ($this->getArg('listProd')) {
            $this->_listDupes($dupesProd);
        } else if ($this->getArg('listCat')) {
            $this->_listDupes($dupesCat, 'categories');
        } else {
            $this->_fixDupes($dupesCat, 'categories');
            if ($this->getArg('qualifyByAttrCode')) {
                $this->_qualifyAttrCode = $this->getArg('qualifyByAttrCode');
            }
            $this->_fixDupes($dupesProd);
        }
    }

    protected function _gatherDupeUrlKeys($mode='products')
    {
        $this->_initMode($mode);
        $this->_connection = Mage::getSingleton('core/resource')->getConnection('eav_write');
        /** @var Varien_Db_Select $select */
        $select = $this->_connection->select()->from($this->_table, array(
            'num' => new Zend_Db_Expr('COUNT(*)'),
            'url_key' => 'value',
            'store' => 'store_id'
        ))
            ->where('attribute_id=?', $this->_attr->getId())
            ->group('value')
            ->group('store_id')
            ->order('num')
            ->having('num > 1');
        Mage::getResourceHelper('core')->addGroupConcatColumn($select, 'entities', 'entity_id');
        $result = $this->_connection->fetchAll($select);
        return $result;
    }

    protected function _initMode($mode='products') {
        if ($mode === 'categories') {
            $this->_attr = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'url_key');
            $this->_table = Mage::getSingleton('core/resource')->getTableName('catalog_category_entity_varchar');
        } else {
            $this->_attr = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'url_key');
            $this->_table = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_varchar');
        }
    }
    
    protected function _listDupes(array $dupes, $mode='products')
    {
        if (sizeof($dupes) == 0) {
            echo 'No '. $mode . ' with duplicate url keys found! ^^' . "\n";
            die();
        }
        foreach ($dupes as $row) {
            printf("Found %d %s with url_key '%s': %s. Store: %s\n", $row['num'], $mode, $row['url_key'], $row['entities'], $row['store']);
        }
    }

    protected function _fixDupes(array $dupes, $mode='products')
    {
        $this->_initMode($mode);
        $processed = array();
        foreach ($dupes as $row) {
            echo "Processing ids: {$row['entities']} for store{$row['store']}\n";
            $ids = explode(',', $row['entities']);
            foreach ($ids as $idx => $entityId) {
                if (0 === $idx && !$this->_qualifyAttrCode) {
                    continue; // keep the first url key unmodified unless --qualifyByAttrCode is set
                }
                if (isset($processed[$entityId])) {
                    echo "Already processed id: {$entityId}. Skipping.\n";
                    continue;
                }
                $key = $this->_qualifyUrlKey($row['url_key'], $entityId);
                echo "$entityId: $key\n";
                $where = array(
                    'attribute_id=?' => $this->_attr->getId(),
                    'entity_id=?' => $entityId,
                    'store_id=?' => $row['store']
                );

                // If record exists in the new table, update it. If not, insert
                if ($this->_recordInNewTableExists($where)) {
                    $this->_updateRow($key, $where);
                } else {
                    $this->_insertRow($entityId, $row['store'], $key);
                }

                // Just for consistency, update the old url_key eav value table, too
                $this->_connection->update($this->_table, array('value' => $key), $where);
                $processed[$entityId]=true;
            }
        }
    }

    protected function _updateRow($value, $where) {
        echo "Updating\n";
        try {
            $this->_connection->update(
                $this->_attr->getBackend()->getTable(), array('value' => $value), $where
            );
        } catch (Exception $e) {
            echo 'ERROR: ' . $e->getMessage() . "\n";
        }
    }

    protected function _insertRow($entityId, $store, $value) {
        echo "Inserting\n";
        try {
            $this->_connection->insert(
                $this->_attr->getBackend()->getTable(),
                array(
                    'entity_type_id' => $this->_attr->getEntityTypeId(),
                    'attribute_id' => $this->_attr->getId(),
                    'entity_id' => $entityId,
                    'store_id' => $store,
                    'value' => $value
                )
            );
        } catch (Exception $e) {
            echo 'ERROR: ' . $e->getMessage() . "\n";
        }
    }

    protected function _recordInNewTableExists(array $where)
    {
        $select = $this->_connection->select()
            ->from($this->_attr->getBackend()->getTable(), array(
                new Zend_Db_Expr('COUNT(*)'),
            ));
        foreach ($where as $cond => $bind) {
            $select->where($cond, $bind);
        }
        $count = $this->_connection->fetchOne($select);
        return (bool) $count;
    }

    protected function _qualifyUrlKey($key, $entityId)
    {
        $sentry = 0;
        $select = $this->_connection->select()->from($this->_table, array(
            new Zend_Db_Expr('COUNT(*)'),
        ))
            ->where('attribute_id=?', $this->_attr->getId())
            ->where('value=:key');
        $candidateBase = $key;
        do {
            if ($sentry++ == 1000) {
                Mage::throwException(sprintf('Unable to qualify url_key "%s": reached 1000 tries', $key));
            }
            if ($sentry == 1 && $this->_qualifyAttrCode) {
                if ($qualifyValue = $this->_getQualifyAttrValue($entityId)) {
                    $candidate = $candidateBase = $key . '-' . $qualifyValue;
                }
            } else {
                $candidate = $candidateBase . '-'. $sentry;
            }
            $bind = array('key' => $candidate);
        } while ($this->_connection->fetchOne($select, $bind));
        return $candidate;
    }
    
    protected function _getQualifyAttrValue($entityId) {
        $product = Mage::getModel('catalog/product')->load($entityId);
        $attributes = $product->getAttributes();
        $v = $attributes[$this->_qualifyAttrCode]->getFrontend()->getValue($product);
        return iconv("UTF-8", "ASCII//TRANSLIT", strtolower(str_replace(' ', '_', trim($v))));
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f fix-url-keys.php
  listProd               List products with duplicate url keys
  listCat                List categories with duplicate url keys
  fix                    Uniquely qualify duplicate URL keys (default)
                            --qualifyByAttrCode to use attribute value of given attribute code for qualifying
                                    duplicates (will fall back to default number scheme if no value is found)
  help                   This help

USAGE;
    }
}

$shell = new Netzarbeiter_Fix_UrlKeys();
$shell->run();

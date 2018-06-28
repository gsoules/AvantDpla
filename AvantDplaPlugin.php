<?php

class AvantDplaPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_filters = array(
        'oai_pmh_append_record',
        'oai_pmh_repository_metadata_formats'
    );

    public function filterOaiPmhAppendRecord($item)
    {
        $restrictions = $item->getElementTexts('Item Type Metadata', 'Restrictions');
        if (count($restrictions) > 0)
        {
            $item = null;
        }

        return $item;
    }

    public function filterOaiPmhRepositoryMetadataFormats($class)
    {
        // Remove support for all classes.
        $class = array();

        // Return only the class used by this installation for DPLA.
        $class ['oai_dc'] = array(
            'class' => 'SwhplOaiDc',
            'namespace' => OaiPmhRepository_Metadata_OaiDc::METADATA_NAMESPACE,
            'schema' => OaiPmhRepository_Metadata_OaiDc::METADATA_SCHEMA);

        return $class;
    }
}

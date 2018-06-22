<?php

class SwhplOaiDc implements OaiPmhRepository_Metadata_FormatInterface
{
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
    const DC_NAMESPACE_URI = 'http://purl.org/dc/elements/1.1/';
    const DCTERMS_NAMESPACE_URI = 'http://purl.org/dc/terms/';

    public function appendMetadata($item, $metadataElement)
    {
        $document = $metadataElement->ownerDocument;
        $oai_dc = $document->createElementNS(self::METADATA_NAMESPACE, 'oai_dc:dc');
        $metadataElement->appendChild($oai_dc);

        $oai_dc->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
        $oai_dc->setAttribute('xmlns:dcterms', self::DCTERMS_NAMESPACE_URI);
        $oai_dc->declareSchemaLocation(self::METADATA_NAMESPACE, self::METADATA_SCHEMA);

        $dcElementNames = array(
            'Title', 'Creator', 'Subject', 'Description', 'Publisher', 'Date', 'Type', 'Identifier', 'Rights', 'Place');

        $oai_dc->appendNewElement('dc:contributor', 'Southwest Harbor Public Library');

        foreach ($dcElementNames as $elementName)
        {
            $setName = $elementName == 'Place' ? 'Item Type Metadata' : 'Dublin Core';
            $dcElements = $item->getElementTexts($setName, $elementName);
            $text = empty($dcElements) ? '' : $dcElements[0]->text;

            switch ($elementName)
            {
                case 'Identifier':
                    $this->appendIdentifierMetadata($oai_dc, $item);
                    break;

                case 'Subject':
                    $this->appendSubjectMetadata($oai_dc, $dcElements);
                    break;

                case 'Type':
                    $this->appendTypeMetadata($oai_dc, $text);
                    break;

                case 'Date':
                    $this->appendDateMetadata($oai_dc, $text);
                    break;

                case 'Description':
                    $this->appendDescriptionMetadata($oai_dc, $text);
                    break;

                case 'Rights':
                    $this->appendRightsMetadata($oai_dc, $text);
                    break;

                case 'Place':
                    $this->appendPlaceMetadata($oai_dc, $item, $text);
                    break;

                default:
                    foreach ($dcElements as $elementText)
                    {
                        $oai_dc->appendNewElement('dc:' . strtolower($elementName), $elementText->text);
                    }
            }
        }
    }

    protected function appendDateMetadata($oai_dc, $text)
    {
        if (!empty($text))
            $oai_dc->appendNewElement('dcterms:created', $text);
    }

    protected function appendDescriptionMetadata($oai_dc, $text)
    {
        if (!empty($text))
            $oai_dc->appendNewElement('dcterms:abstract', $text);
    }

    protected function appendIdentifierMetadata($oai_dc, $item)
    {
        // Emit the item's URL as its identifier.
        $oai_dc->appendNewElement('dc:identifier', record_url($item, 'show', true));

        // Emit the URL of the item's thumbnail image.
        $files = $item->getFiles();
        if (count($files) >= 1)
        {
            $oai_dc->appendNewElement('dcterms:hasFormat', $files[0]->getWebPath('thumbnail'));
        }
    }

    protected function appendPlaceMetadata($oai_dc, $item, $text)
    {
        $elements = $item->getElementTexts('Item Type Metadata', 'State');
        $state = count($elements) >= 1 ? $elements[0]->text : '';
        $elements = $item->getElementTexts('Item Type Metadata', 'Country');
        $country = count($elements) >= 1 ? $elements[0]->text : '';

        $parts = explode(',', $text);
        $parts = array_map('trim', $parts);

        foreach ($parts as $part)
        {
            $location = $part;
            if ($location == 'MDI')
            {
                $location = 'Mount Desert Island';
            }
            if (!empty($state))
            {
                if ($state == 'ME')
                    $state = 'Maine';
                if (!empty($location))
                    $location .= ', ';
                $location .= $state;
            }
            if (!empty($country) && $country != 'USA')
            {
                if (!empty($location))
                    $location .= ', ';
                $location .= $country;
            }

            $oai_dc->appendNewElement('dcterms:spatial', $location);
        }
    }

    protected function appendRightsMetadata($oai_dc, $text)
    {
        $url = DigitalArchive::convertRightsToUrl($text);
        $oai_dc->appendNewElement('dc:rights', $url);
    }

    protected function appendSubjectMetadata($oai_dc, $dcElements)
    {
        // Create an array of unique subject values from the item's subject element(s).
        // This way, if the item has two subjects e.g. 'Places, Town' and 'Places, Shore',
        // three dc:subject elements will get emitted: 'Places', 'Town', and 'Shore'.
        $subjects = array();

        foreach ($dcElements as $elementText)
        {
            $parts = explode(',', $elementText->text);
            $parts = array_map('trim', $parts);
            foreach ($parts as $part)
            {
                $subjects[] = $part;
            }
        }

        $subjects = array_unique($subjects);
        foreach ($subjects as $subject)
        {
            if ($subject == 'Other')
                continue;
            $oai_dc->appendNewElement('dc:subject', $subject);
        }
    }

    protected function appendTypeMetadata($oai_dc, $text)
    {
        $parts = explode(',', $text);
        $parts = array_map('trim', $parts);

        foreach ($parts as $index => $part)
        {
            if ($index == 0)
            {
                $type = strtolower($parts[0]);
                if ($type == 'article' || $type == 'document' || $type == 'publication')
                {
                    $oai_dc->appendNewElement('dc:type', 'text');
                    if ($type == 'article')
                        break;
                }
                elseif ($type == 'map')
                {
                    $oai_dc->appendNewElement('dc:type', 'image');
                    $oai_dc->appendNewElement('dc:format', 'map');
                    break;
                }
                else
                {
                    $oai_dc->appendNewElement('dc:type', $type);
                }
            }
            else
            {
                $oai_dc->appendNewElement('dc:format', $part);
            }
        }
    }
}

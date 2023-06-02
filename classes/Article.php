<?php

namespace APP\plugins\generic\jatsTemplate\classes;

use APP\core\Application;
use DOMException;

class Article extends \DOMDocument
{
    protected array $rootAttributes = [];

    function __construct(&$record)
    {
        parent::__construct('1.0', 'UTF-8');
        $this->rootAttributes = [
            'xmlns:xlink'=>'http://www.w3.org/1999/xlink',
            'xmlns:mml'=>'http://www.w3.org/1998/Math/MathML',
            'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance'
        ];
        $this->convertToXml($record);
    }

    /**
     * create xml element
     * @param $elementName string
     * @param $elementValue null
     * @param array $attributes
     * @return \DOMElement
     * @throws \DOMException
     */
    public function createDomElement(string $elementName, $elementValue = null, array $attributes = []):\DOMElement
    {
        try {
            $element = $this->createElement($elementName, $elementValue === null?'':$elementValue);
            foreach ($attributes as $key => $attribute) {
                $element->setAttribute($key,$attribute);
            }
            return $element;
        } catch (\DOMException){
            throw new DOMException("create element failed");
        }

    }

    /**
     * append child element to parent element
     * @param $parent \DOMNode
     * @param $child \DOMNode
     */
    public function appendChildToParent($parent, $child)
    {
        if ($parent and $child) {
            $parent->appendChild($child);
        }
    }

    /**
     * @param $record
     * @return bool|\DOMDocument
     * @throws \DOMException
     */
    public function convertToXml(&$record) :bool|\DOMDocument
    {
        $submission =& $record->getData('article');
        $journal =& $record->getData('journal');
        $section =& $record->getData('section');
        $issue =& $record->getData('issue');
        $publication = $submission->getCurrentPublication();


        $request = Application::get()->getRequest();
        $this->rootAttributes['xml:lang'] = substr($submission->getLocale()=== null?'':$submission->getLocale(), 0, 2);

        // create root element article
        $articleElement = $this->createDomElement('article',null,$this->rootAttributes);
        // create element journal-meta
        $articleFront = new ArticleFront();
        $frontElement = $articleFront->create($journal,$submission,$section,$issue,$request,$this);
        // create element body
        $articleBody = new ArticleBody();
        $bodyElement = $articleBody->create($submission,$this);
        // create element back
        $articleBack = new ArticleBack();
        $backElement = $articleBack->create($publication,$this);
        //append element front,body,back to element article
        $this->appendChildToParent($articleElement,$frontElement);
        $this->appendChildToParent($articleElement,$bodyElement);
        $this->appendChildToParent($articleElement,$backElement);
        return $this->loadXml($this->saveXML($articleElement));
    }


    /**
     * Map the specific HTML tags in title/ sub title for JATS schema compability
     * @see https://jats.nlm.nih.gov/publishing/0.4/xsd/JATS-journalpublishing0.xsd
     *
     * @param  string $htmlTitle The submission title/sub title as in HTML
     * @return string
     */
    public function mapHtmlTagsForTitle(string $htmlTitle): string
    {
        $mappings = [
            '<b>' 	=> '<bold>',
            '</b>' 	=> '</bold>',
            '<i>' 	=> '<italic>',
            '</i>' 	=> '</italic>',
            '<u>' 	=> '<underline>',
            '</u>' 	=> '</underline>',
        ];

        return str_replace(array_keys($mappings), array_values($mappings), $htmlTitle);
    }
}

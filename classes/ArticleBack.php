<?php

namespace APP\plugins\generic\jatsTemplate\classes;

use PKP\db\DAORegistry;

class ArticleBack extends \DOMDocument
{
    /**
     * create xml back DOMNode
     * @param $article Article
     * @param $publication
     * @return \DOMNode
     * @throws \DOMException
     */
    public function create($publication, Article $article):\DOMNode
    {
        // create element back
        $backElement = $article->createDomElement('back', null , []);

        $citationDao = DAORegistry::getDAO('CitationDAO');
        $citations = $citationDao->getByPublicationId($publication->getId())->toArray();
        if (count($citations)) {
            // create element ref-list
            $refListElement = $article->createDomElement('ref-list', null , []);
            $i=1;
            foreach ($citations as $citation) {
                // create element ref
                $refElement = $article->createDomElement('ref', null , ['id'=>'R'.$i]);
                // create element mixed-citation
                $mixedCitationElement = $article->createDomElement('mixed-citation', htmlspecialchars($citation->getRawCitation()) , []);
                // append element mixed-citation to ref
                $article->appendChildToParent($refElement,$mixedCitationElement);
                // append element ref to ref-list
                $article->appendChildToParent($refListElement,$refElement);
                $i++;
            }
            // append element ref-list to back
            $article->appendChildToParent($backElement,$refListElement);
        }

        return  $backElement;
    }
}

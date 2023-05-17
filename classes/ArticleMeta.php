<?php

namespace APP\plugins\generic\jatsTemplate\classes;

use APP\journal\Journal;

class ArticleMeta extends \DOMDocument
{

    /**
     * create xml journal-meta DOMNode
     * @param $journal Journal
     * @param Article $article
     * @return \DOMNode
     */
    public function create(Journal $journal, Article $article): \DOMNode
    {
        // create element journal-meta
        $journalMeta = $article->createDomElement('journal-meta');
        // create element journal-id
        $journalIdElement = $article->createDomElement('journal-id', htmlspecialchars($journal->getPath()), ['journal-id-type' => 'ojs']);
        // create element journal-title-group
        $journalTitleGroup = $article->createDomElement('journal-title-group', null, []);
        // create element journal-title
        $journalTitle = $article->createDomElement('journal-title', htmlspecialchars($journal->getName($journal->getPrimaryLocale())), ['xml:lang' => substr($journal->getPrimaryLocale(), 0, 2)]);
        //append element journal-title to element journal-title-group
        $article->appendChildToParent($journalTitleGroup, $journalTitle);
        // Include translated journal titles
        foreach ($journal->getName(null) as $locale => $title) {
            if ($locale == $journal->getPrimaryLocale()) continue;
            $journalTransTitleGroup = $article->createDomElement('trans-title-group', null, ['xml:lang' => substr($locale, 0, 2)]);
            $journalTransTitle = $article->createDomElement('trans-title', htmlspecialchars($title), []);
            //append element trans-title to element trans-title-group
            $article->appendChildToParent($journalTransTitleGroup, $journalTransTitle);
            //append element trans-title-group to element journal-title-group
            $article->appendChildToParent($journalTitleGroup, $journalTransTitleGroup);
        }
        // create element publisher
        $publisher = $article->createDomElement('publisher', null, []);
        // create element publisher-name
        $publisherName = $article->createDomElement('publisher-name', htmlspecialchars($journal->getSetting('publisherInstitution')), []);
        //append element publisher-name to element publisher
        $article->appendChildToParent($publisher, $publisherName);

        //append element publisher,journal-id,journal-title-group to element journal-meta
        $article->appendChildToParent($journalMeta, $journalIdElement);
        $article->appendChildToParent($journalMeta, $journalTitleGroup);
        $article->appendChildToParent($journalMeta, $publisher);

        // create element issn
        if (!empty($journal->getSetting('onlineIssn'))) {
            $issnOnline = $article->createDomElement('issn', htmlspecialchars($journal->getSetting('onlineIssn')), ['pub-type' => 'epub']);
            $article->appendChildToParent($journalMeta, $issnOnline);
        }
        if (!empty($journal->getSetting('printIssn'))) {
            $issnPrint = $article->createDomElement('issn', htmlspecialchars($journal->getSetting('printIssn')), ['pub-type' => 'ppub']);
            $article->appendChildToParent($journalMeta, $issnPrint);
        }
        return $journalMeta;

    }
}

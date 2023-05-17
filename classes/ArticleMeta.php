<?php

namespace APP\plugins\generic\jatsTemplate\classes;

class ArticleMeta extends \DOMDocument
{

    /**
     * create xml journal-meta DOMNode
     * @param $journal Journal
     * @param Article $instance
     * @return \DOMNode
     */
    public function create($journal, Article $instance): \DOMNode
    {
        // create element journal-meta
        $journalMeta = $instance->createDomElement('journal-meta');
        // create element journal-id
        $journalId = $instance->createDomElement('journal-id', htmlspecialchars($journal->getPath()), ['journal-id-type' => 'ojs']);
        // create element journal-title-group
        $journalTitleGroup = $instance->createDomElement('journal-title-group', null, []);
        // create element journal-title
        $journalTitle = $instance->createDomElement('journal-title', htmlspecialchars($journal->getName($journal->getPrimaryLocale())), ['xml:lang' => substr($journal->getPrimaryLocale(), 0, 2)]);
        //append element journal-title to element journal-title-group
        $instance->appendChildToParent($journalTitleGroup, $journalTitle);
        // Include translated journal titles
        foreach ($journal->getName(null) as $locale => $title) {
            if ($locale == $journal->getPrimaryLocale()) continue;
            $journalTransTitleGroup = $instance->createDomElement('trans-title-group', null, ['xml:lang' => substr($locale, 0, 2)]);
            $journalTransTitle = $instance->createDomElement('trans-title', htmlspecialchars($title), []);
            //append element trans-title to element trans-title-group
            $instance->appendChildToParent($journalTransTitleGroup, $journalTransTitle);
            //append element trans-title-group to element journal-title-group
            $instance->appendChildToParent($journalTitleGroup, $journalTransTitleGroup);
        }
        // create element publisher
        $publisher = $instance->createDomElement('publisher', null, []);
        // create element publisher-name
        $publisherName = $instance->createDomElement('publisher-name', htmlspecialchars($journal->getSetting('publisherInstitution')), []);
        //append element publisher-name to element publisher
        $instance->appendChildToParent($publisher, $publisherName);

        //append element publisher,journal-id,journal-title-group to element journal-meta
        $instance->appendChildToParent($journalMeta, $journalId);
        $instance->appendChildToParent($journalMeta, $journalTitleGroup);
        $instance->appendChildToParent($journalMeta, $publisher);

        // create element issn
        if (!empty($journal->getSetting('onlineIssn'))) {
            $issnOnline = $instance->createDomElement('issn', htmlspecialchars($journal->getSetting('onlineIssn')), ['pub-type' => 'epub']);
            $instance->appendChildToParent($journalMeta, $issnOnline);
        }
        if (!empty($journal->getSetting('printIssn'))) {
            $issnPrint = $instance->createDomElement('issn', htmlspecialchars($journal->getSetting('printIssn')), ['pub-type' => 'ppub']);
            $instance->appendChildToParent($journalMeta, $issnPrint);
        }
        return $journalMeta;

    }
}

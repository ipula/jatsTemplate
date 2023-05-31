<?php

namespace APP\plugins\generic\jatsTemplate\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\submissionFile\SubmissionFile;

class ArticleFront extends \DOMDocument
{

    /**
     * @throws \DOMException
     */
    public function create(Journal $journal, $submission, $section, $issue, $request, Article $article): \DOMNode
    {
        $frontElement = $article->createDomElement('front');
        $journalMeta = $this->createJournalMetaElements($journal,$article);
        $articleMeta = $this->createArticleMetaElements($submission,$journal,$section,$issue,$request,$article);
        $article->appendChildToParent($frontElement,$journalMeta);
        $article->appendChildToParent($frontElement,$articleMeta);
        return $frontElement;
    }

    /**
     * create xml journal-meta DOMNode
     * @param $journal Journal
     * @param Article $article
     * @return \DOMNode
     * @throws \DOMException
     */
    public function createJournalMetaElements(Journal $journal, Article $article): \DOMNode
    {
        // create element journal-meta
        $journalMetaElement = $article->createDomElement('journal-meta');
        // create element journal-id
        $journalIdElement = $article->createDomElement('journal-id', htmlspecialchars($journal->getPath()), ['journal-id-type' => 'ojs']);
        // create element journal-title-group
        $journalTitleGroupElement = $article->createDomElement('journal-title-group', null, []);
        // create element journal-title
        $journalTitleElement = $article->createDomElement('journal-title', htmlspecialchars($journal->getName($journal->getPrimaryLocale())), ['xml:lang' => substr($journal->getPrimaryLocale(), 0, 2)]);
        //append element journal-title to element journal-title-group
        $article->appendChildToParent($journalTitleGroupElement, $journalTitleElement);
        // Include translated journal titles
        foreach ($journal->getName(null) as $locale => $title) {
            if ($locale == $journal->getPrimaryLocale()) continue;
            $journalTransTitleGroupElement = $article->createDomElement('trans-title-group', null, ['xml:lang' => substr($locale, 0, 2)]);
            $journalTransTitleElement = $article->createDomElement('trans-title', htmlspecialchars($title), []);
            //append element trans-title to element trans-title-group
            $article->appendChildToParent($journalTransTitleGroupElement, $journalTransTitleElement);
            //append element trans-title-group to element journal-title-group
            $article->appendChildToParent($journalTitleGroupElement, $journalTransTitleGroupElement);
        }
        // create element publisher
        $publisherElement = $article->createDomElement('publisher', null, []);
        // create element publisher-name
        $publisherNameElement = $article->createDomElement('publisher-name', htmlspecialchars($journal->getSetting('publisherInstitution')), []);
        //append element publisher-name to element publisher
        $article->appendChildToParent($publisherElement, $publisherNameElement);

        //append element publisher,journal-id,journal-title-group to element journal-meta
        $article->appendChildToParent($journalMetaElement, $journalIdElement);
        $article->appendChildToParent($journalMetaElement, $journalTitleGroupElement);
        $article->appendChildToParent($journalMetaElement, $publisherElement);

        // create element issn
        if (!empty($journal->getSetting('onlineIssn'))) {
            $issnOnlineElement = $article->createDomElement('issn', htmlspecialchars($journal->getSetting('onlineIssn')), ['pub-type' => 'epub']);
            $article->appendChildToParent($journalMetaElement, $issnOnlineElement);
        }
        if (!empty($journal->getSetting('printIssn'))) {
            $issnPrintElement = $article->createDomElement('issn', htmlspecialchars($journal->getSetting('printIssn')), ['pub-type' => 'ppub']);
            $article->appendChildToParent($journalMetaElement, $issnPrintElement);
        }
        return $journalMetaElement;

    }

    /**
     * create xml article-meta DOMNode
     * @param $submission
     * @param $journal
     * @param $section
     * @param $request
     * @param $issue
     * @param Article $article
     * @return \DOMNode
     * @throws \DOMException
     */
    function createArticleMetaElements($submission,$journal,$section,$issue,$request,Article $article):\DOMNode
    {
        // create element article-meta
        $articleMetaElement = $article->createDomElement('article-meta');
        // create element article-id
        $articleIdElement = $article->createDomElement('article-id',$submission->getId(),['pub-id-type'=>'publisher-id']);
        //append element article-subj-group to element article-categories
        $article->appendChildToParent($articleMetaElement,$articleIdElement);
        // create element article-categories
        $this->createElementArticleCategories($article, $journal, $section, $articleMetaElement);

        // create element title-group
        $titleGroupElement = $article->createDomElement('title-group',null,[]);
        // create element article-title
        $articleTitleElement  = $article->createDomElement('article-title',$article->mapHtmlTagsForTitle($submission->getCurrentPublication()->getLocalizedTitle(null, 'html')),['xml:lang'=>substr($submission->getLocale()=== null?'':$submission->getLocale(), 0, 2)]);
        //append element article-title to element title-group
        $article->appendChildToParent($titleGroupElement,$articleTitleElement);
        // create element subtitle
        if (!empty($subtitle = $article->mapHtmlTagsForTitle($submission->getCurrentPublication()->getLocalizedSubTitle(null, 'html')))) {
            $subtitleElement = $article->createDomElement('subtitle',$subtitle,['xml:lang'=>substr($submission->getLocale(), 0, 2)]);
            //append element subtitle to element title-group
            $article->appendChildToParent($titleGroupElement,$subtitleElement);
        }

        // Include translated submission titles
        foreach ($submission->getCurrentPublication()->getTitles('html') as $locale => $title) {
            if ($locale == $submission->getLocale()) {
                continue;
            }

            if (trim($translatedTitle = $article->mapHtmlTagsForTitle($submission->getCurrentPublication()->getLocalizedTitle($locale, 'html'))) === '') {
                continue;
            }

            // create element trans-title-group
            $transTitleGroupElement = $article->createDomElement('trans-title-group',null,['xml:lang'=>substr($locale, 0, 2)]);
            // create element trans-title
            $transTitleElement = $article->createDomElement('trans-title',$translatedTitle,[]);
            //append element trans-title to element trans-title-group
            $article->appendChildToParent($transTitleGroupElement,$transTitleElement);

            if (!empty($translatedSubTitle = $article->mapHtmlTagsForTitle($submission->getCurrentPublication()->getLocalizedSubTitle($locale, 'html')))) {
                // create element trans-subtitle
                $transSubTitleElement = $article->createDomElement('trans-subtitle',$translatedSubTitle,[]);
                //append element trans-subtitle to element trans-title-group
                $article->appendChildToParent($transTitleGroupElement,$transSubTitleElement);
            }
            //append element trans-title-group to element title-group
            $article->appendChildToParent($titleGroupElement,$transTitleGroupElement);
        }
        //append element title-group to element article-meta
        $article->appendChildToParent($articleMetaElement,$titleGroupElement);

        // create element contrib-group
        $contribGroupElement = $article->createDomElement('contrib-group',null,['content-type'=>'author']);

        // Include authors
        $affiliations = [];
        foreach ($submission->getCurrentPublication()->getData('authors') as $author) {
            $affiliation = $author->getLocalizedAffiliation();
            $affiliationToken = array_search($affiliation, $affiliations);
            if ($affiliation && !$affiliationToken) {
                $affiliationToken = 'aff-' . (count($affiliations) + 1);
                $affiliations[$affiliationToken] = $affiliation;
            }
            $surname = method_exists($author, 'getLastName') ? $author->getLastName() : $author->getLocalizedFamilyName();

            // create element contrib
            $contribElement = $article->createDomElement('contrib', null, $author->getPrimaryContact() ? ['corresp' => 'yes'] : []);
            // If using the CRediT plugin, credit roles may be available.
            $creditPlugin = PluginRegistry::getPlugin('generic', 'creditplugin');
            if ($creditPlugin && $creditPlugin->getEnabled()) {
                $contributorRoles = $author->getData('creditRoles') ?? [];
                $creditRoles = $creditPlugin->getCreditRoles();
                foreach ($contributorRoles as $role) {
                    $roleName = $creditRoles[$role];
                    // create element role
                    $roleElement = $article->createDomElement('role', htmlspecialchars($roleName), ['vocab-identifier' => 'https://credit.niso.org/', 'vocab-term' => htmlspecialchars($roleName), 'vocab-term-identifier' => htmlspecialchars($role)]);
                    // append element role to contrib
                    $article->appendChildToParent($contribElement, $roleElement);
                }
            }

            if ($author->getOrcid()) {
                // create element role
                $contribIdElement = $article->createDomElement('contrib-id', htmlspecialchars($author->getOrcid()), ['contrib-id-type' => 'orcid']);
                // append element contrib-id to contrib
                $article->appendChildToParent($contribElement, $contribIdElement);
            }
            // create element name
            $nameElement = $article->createDomElement('name', null, ['name-style' => 'western']);
            if ($surname != '') {
                // create element surname
                $surnameElement = $article->createDomElement('surname', htmlspecialchars($surname), []);
                // append element surname to name
                $article->appendChildToParent($nameElement, $surnameElement);
                // append element name to contrib
                $article->appendChildToParent($contribElement, $nameElement);
            }
            // create element given-names
            $givenNamesElement = $article->createDomElement(
                'given-names',
                htmlspecialchars(method_exists($author, 'getFirstName') ? $author->getFirstName() : $author->getLocalizedGivenName()) . (((method_exists($author, 'getMiddleName') && $s = $author->getMiddleName()) != '') ? " $s" : ''),
                []);
            // append element given-names,surname to name
            $article->appendChildToParent($nameElement, $givenNamesElement);
            // create element email
            $emailElement = $article->createDomElement('email', htmlspecialchars($author->getEmail()), []);
            // append element email to contrib
            $article->appendChildToParent($contribElement, $emailElement);
            if ($affiliationToken) {
                // create element xref
                $xrefElement = $article->createDomElement('xref', null, ['ref-type' => 'aff', 'rid' => $affiliationToken]);
                // append element $xref to contrib
                $article->appendChildToParent($contribElement, $xrefElement);
            }
            if (($s = $author->getUrl()) != '') {
                // create element uri
                $uriElement = $article->createDomElement('uri', htmlspecialchars($s), ['ref-type' => 'aff', 'rid' => $affiliationToken]);
                // append element contrib-id to contrib
                $article->appendChildToParent($contribElement, $uriElement);
            }
            // append element name to contrib
            $article->appendChildToParent($contribElement, $nameElement);
            // append element contrib to contrib-group
            $article->appendChildToParent($contribGroupElement,$contribElement);
        }

        // append element contrib-group to article-meta
        $article->appendChildToParent($articleMetaElement,$contribGroupElement);

        foreach ($affiliations as $affiliationToken => $affiliation) {
            // create element aff
            $affElement = $article->createDomElement('aff', null, ['id' => $affiliationToken]);
            // create element institution
            $institutionElement = $article->createDomElement('institution', htmlspecialchars($affiliation), ['content-type' => 'orgname']);
            // append element institution to aff
            $article->appendChildToParent($affElement, $institutionElement);
            // append element aff to article-meta
            $article->appendChildToParent($articleMetaElement,$affElement);
        }

        $datePublished = $submission->getDatePublished();
        if (!$datePublished) $datePublished = $issue->getDatePublished();
        if ($datePublished) $datePublished = strtotime($datePublished);

        //include pub dates
        if ($submission->getDatePublished()){
            // create element pub-date
            $pubDateElement = $article->createDomElement('pub-date', null, ['date-type' => 'pub','publication-format'=>'epub']);
            // create element day
            $dayElement = $article->createDomElement('day', strftime('%d', (int)$datePublished), []);
            // create element month
            $monthElement = $article->createDomElement('month', strftime('%m', (int)$datePublished), []);
            // create element year
            $yearElement = $article->createDomElement('year', strftime('%Y', (int)$datePublished), []);
            // append element day,month,year to pub-date
            $article->appendChildToParent($pubDateElement,$dayElement);
            $article->appendChildToParent($pubDateElement,$monthElement);
            $article->appendChildToParent($pubDateElement,$yearElement);
            // append element aff to article-meta
            $article->appendChildToParent($articleMetaElement,$pubDateElement);
        }
        // Include page info, if available and parseable.
        $matches = $pageCount = null;
        $fpageElement = $lpageElement = null;
        if (PKPString::regexp_match_get('/^(\d+)$/', $submission->getPages(), $matches)) {
            $matchedPage = htmlspecialchars($matches[1]);
            // create element fpage
            $fpageElement = $article->createDomElement('fpage', $matchedPage, []);
            // create element lpage
            $lpageElement = $article->createDomElement('lpage', $matchedPage, []);
            $pageCount = 1;
        } elseif (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $submission->getPages(), $matches)) {
            $matchedPage = htmlspecialchars($matches[1]);
            // create element fpage
            $fpageElement = $article->createDomElement('fpage', $matchedPage, []);
            // create element lpage
            $lpageElement = $article->createDomElement('lpage', $matchedPage, []);
            $pageCount = 1;
        } elseif (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $submission->getPages(), $matches)) {
            $matchedPageFrom = htmlspecialchars($matches[1]);
            $matchedPageTo = htmlspecialchars($matches[3]);
            // create element fpage
            $fpageElement = $article->createDomElement('fpage', $matchedPageFrom, []);
            // create element lpage
            $lpageElement = $article->createDomElement('lpage', $matchedPageTo, []);
            $pageCount = $matchedPageTo - $matchedPageFrom + 1;
        } elseif (PKPString::regexp_match_get('/^(\d+)[ ]?-[ ]?(\d+)$/', $submission->getPages(), $matches)) {
            $matchedPageFrom = htmlspecialchars($matches[1]);
            $matchedPageTo = htmlspecialchars($matches[2]);
            $fpageElement = $article->createDomElement('fpage', $matchedPageFrom, []);
            // create element lpage
            $lpageElement = $article->createDomElement('lpage', $matchedPageTo, []);
            $pageCount = $matchedPageTo - $matchedPageFrom + 1;
        }
        // append element aff to article-meta
        if($fpageElement)
        {
            $article->appendChildToParent($articleMetaElement,$fpageElement);
        }
        if ($lpageElement) {
            $article->appendChildToParent($articleMetaElement, $lpageElement);
        }

        $copyrightYear = $submission->getCopyrightYear();
        $copyrightHolder = $submission->getLocalizedCopyrightHolder();
        $licenseUrl = $submission->getLicenseURL();
        $ccBadge = Application::get()->getCCLicenseBadge($licenseUrl, $submission->getLocale())=== null?'':Application::get()->getCCLicenseBadge($licenseUrl, $submission->getLocale());
        if ($copyrightYear || $copyrightHolder || $licenseUrl || $ccBadge){
            // create element permissions
            $permissionsElement = $article->createDomElement('permissions', null, []);
            if($copyrightYear||$copyrightHolder){
                // create element copyright-statement
                $copyrightStatementElement = $article->createDomElement('copyright-statement', htmlspecialchars(__('submission.copyrightStatement', ['copyrightYear' => $copyrightYear, 'copyrightHolder' => $copyrightHolder])), []);
                // append element copyright-statement to permissions
                $article->appendChildToParent($permissionsElement,$copyrightStatementElement);
            }
            if($copyrightYear){
                // create element copyright-year
                $copyrightYearElement = $article->createDomElement('copyright-year', htmlspecialchars($copyrightYear) , []);
                // append element copyright-year to permissions
                $article->appendChildToParent($permissionsElement,$copyrightYearElement);
            }
            if($copyrightHolder){
                // create element copyright-holder
                $copyrightHolderElement = $article->createDomElement('copyright-holder', htmlspecialchars($copyrightHolder), []);
                // append element copyright-holder to permissions
                $article->appendChildToParent($permissionsElement,$copyrightHolderElement);
            }
            if($licenseUrl) {
                // create element license
                $licenseUrlElement = $article->createDomElement('license', null, ['xlink:href' => htmlspecialchars($licenseUrl)]);
                if($ccBadge){
                    // create element license-p
                    $ccBadgeElement = $article->createDomElement('license-p', strip_tags($ccBadge) , []);
                    // append element license-p to license
                    $article->appendChildToParent($licenseUrlElement,$ccBadgeElement);
                }
                // append element copyright-statement to permissions
                $article->appendChildToParent($permissionsElement,$licenseUrlElement);
            }
            // append element permissions to article-meta
            $article->appendChildToParent($articleMetaElement,$permissionsElement);
        }

        // create element self-uri
        $selfUriElement= $article->createDomElement('self-uri', strip_tags($ccBadge) , ['xlink:href'=>htmlspecialchars($request->url($journal->getPath(), 'article', 'view', $submission->getBestArticleId()) != null && $request->url($journal->getPath(), 'article', 'view', $submission->getBestArticleId()))]);
        //append element self-uri to article-meta
        $article->appendChildToParent($articleMetaElement,$selfUriElement);

        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        foreach ($submissionKeywordDao->getKeywords($submission->getCurrentPublication()->getId(), $journal->getSupportedLocales()) as $locale => $keywords) {
            if (empty($keywords)) continue;
            // create element kwd-group
            $kwdGroupElement = $article->createDomElement('kwd-group', null , ['xml:lang'=>substr($locale, 0, 2)]);
            foreach ($keywords as $keyword) {
                // create element kwd
                $kwdElement = $article->createDomElement('kwd', null , ['xml:lang'=>substr($locale, 0, 2)]);
                // append element kwd to kwd-group
                $article->appendChildToParent($kwdGroupElement,$kwdElement);
            }
            // append element kwd-group to article-meta
            $article->appendChildToParent($articleMetaElement,$kwdGroupElement);
        }

        if(isset($pageCount)){
            // create element counts
            $countElement = $article->createDomElement('counts', null , []);
            // create element page-count
            $pageCountElement = $article->createDomElement('page-count', null , ['count'=>(int) $pageCount]);
            // append element page-count to count
            $article->appendChildToParent($countElement,$pageCountElement);
            // append element count to article-meta
            $article->appendChildToParent($articleMetaElement,$countElement);
        }

        $candidateFound = false;
        // create element custom-meta-group
        $customMetaGroupElement = $article->createDomElement('custom-meta-group', null , []);
        $layoutFiles = Repo::submissionFile()->getCollector()
            ->filterBySubmissionIds([$submission->getId()])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY])
            ->getMany();

        foreach ($layoutFiles as $layoutFile) {
            $candidateFound = true;
            $sourceFileUrl = $request->url(null, 'jatsTemplate', 'download', null,
                [
                    'submissionFileId' => $layoutFile->getId(),
                    'fileId' => $layoutFile->getData('fileId'),
                    'submissionId' => $submission->getId(),
                    'stageId' => WORKFLOW_STAGE_ID_PRODUCTION,
                ]
            );
            // create element custom-meta-group
            $customMetaGroupElement = $article->createDomElement('custom-meta', null , []);
            // create element meta-name
            $metaNameElement = $article->createDomElement('meta-name', 'production-ready-file-url' , []);
            // create element meta-value
            $metaValueElement = $article->createDomElement('meta-value', null , []);
            // create element ext-link
            $extLinkElement = $article->createDomElement('ext-link', null , ['ext-link-type'=>'uri','xlink:href'=>htmlspecialchars($sourceFileUrl)]);
            // append element ext-link to meta-value
            $article->appendChildToParent($metaValueElement,$extLinkElement);
            // append element meta-value to meta-name
            $article->appendChildToParent($metaNameElement,$metaValueElement);
            // append element meta-name to custom-meta-group
            $article->appendChildToParent($customMetaGroupElement,$metaNameElement);

        }
        if ($candidateFound){
            // append element custom-meta-group to article-meta
            $article->appendChildToParent($articleMetaElement,$customMetaGroupElement);
        };

        return $articleMetaElement;
    }

    /**
     * @param Article $article
     * @param $journal
     * @param $section
     * @param \DOMElement $articleMetaElement
     * @return void
     * @throws \DOMException
     */
    public function createElementArticleCategories(Article $article, $journal, $section, \DOMElement $articleMetaElement): void
    {
        $articleCategoriesElement = $article->createDomElement('article-categories', null, []);
        // create element article-subj-group
        $subjGroupElement = $article->createDomElement('subj-group', null, ['xml:lang' => $journal->getPrimaryLocale(), 'subj-group-type' => 'heading']);
        // create element article-categories
        $subjectElement = $article->createDomElement('subject', htmlspecialchars($section->getLocalizedTitle()), []);
        //append element subject to element article-subj-group
        $article->appendChildToParent($subjGroupElement, $subjectElement);
        //append element article-subj-group to element article-categories
        $article->appendChildToParent($articleCategoriesElement, $subjGroupElement);
        //append element article-categories to element article-meta
        $article->appendChildToParent($articleMetaElement, $articleCategoriesElement);
    }
}

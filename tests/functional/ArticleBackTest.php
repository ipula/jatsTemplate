<?php

namespace functional;

use APP\author\Author;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\plugins\generic\jatsTemplate\classes\Article;
use APP\plugins\generic\jatsTemplate\classes\ArticleBack;
use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\doi\Doi;
use PKP\galley\Galley;
use PKP\oai\OAIRecord;
use PKP\tests\PKPTestCase;

class ArticleBackTest extends PKPTestCase
{
    private string $xmlFilePath = 'plugins/generic/jatsTemplate/tests/data/';

    /**
     * create article mock instance
     * @throws \DOMException
     */
    private function createArticleMockInstance(OAIRecord &$record)
    {
        $articleMock = $this->getMockBuilder(Article::class)
            ->setConstructorArgs([&$record])
            ->onlyMethods([])
            ->getMock();

        return $articleMock;
    }

    /**
     * create mock OAIRecord object
     * @return OAIRecord
     */
    private function createOAIRecordMockObject(): OAIRecord
    {
        //create test data
        $journalId = 1;

        // Author
        $author = new Author();
        $author->setGivenName('author-firstname', 'en');
        $author->setFamilyName('author-lastname', 'en');
        $author->setAffiliation('author-affiliation', 'en');
        $author->setEmail('someone@example.com');

        // Publication
        /** @var Doi|MockObject */
        $publicationDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $publicationDoiObject->setData('doi', 'article-doi');

        /** @var Publication|MockObject */
        $publication = $this->getMockBuilder(Publication::class)
            ->onlyMethods([])
            ->getMock();
        $publication->setData('id', 1);
        $publication->setData('issueId', 96);
        $publication->setData('pages', 15);
        $publication->setData('type', 'art-type', 'en');
        $publication->setData('title', 'article-title-en', 'en');
        $publication->setData('title', 'article-title-de', 'de');
        $publication->setData('coverage', ['en' => ['article-coverage-geo', 'article-coverage-chron', 'article-coverage-sample']]);
        $publication->setData('abstract', 'article-abstract', 'en');
        $publication->setData('sponsor', 'article-sponsor', 'en');
        $publication->setData('doiObject', $publicationDoiObject);
        $publication->setData('languages', 'en');
        $publication->setData('copyrightHolder', 'article-copyright');
        $publication->setData('copyrightYear', 'year');
        $publication->setData('authors', collect([$author]));

        /** @var Doi|MockObject */
        $galleyDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $galleyDoiObject->setData('doi', 'galley-doi');

        // Galleys
        $galley = Repo::galley()->newDataObject();
        /** @var Galley|MockObject */
        $galley = $this->getMockBuilder(Galley::class)
            ->onlyMethods(['getFileType', 'getBestGalleyId'])
            ->setProxyTarget($galley)
            ->getMock();
        $galley->expects(self::any())
            ->method('getFileType')
            ->will($this->returnValue('galley-filetype'));
        $galley->expects(self::any())
            ->method('getBestGalleyId')
            ->will($this->returnValue(98));
        $galley->setId(98);
        $galley->setData('submissionFileId',98);
        $galley->setData('doiObject', $galleyDoiObject);

        $galleys = [$galley];

        // Article
        /** @var Submission|MockObject */
        $article = $this->getMockBuilder(Submission::class)
            ->onlyMethods(['getBestId', 'getCurrentPublication','getGalleys'])
            ->getMock();
        $article->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(9));
        $article->expects($this->any())
            ->method('getGalleys')
            ->will($this->returnValue($galleys));
        $article->setId(9);
        $article->setData('contextId', $journalId);
        $author->setSubmissionId($article->getId());
        $article->expects($this->any())
            ->method('getCurrentPublication')
            ->will($this->returnValue($publication));

        // Journal
        /** @var Journal|MockObject */
        $journal = $this->getMockBuilder(Journal::class)
            ->onlyMethods(['getSetting'])
            ->getMock();
        $journal->expects($this->any())
            ->method('getSetting')
            ->willReturnMap([
                ['publisherInstitution', null, 'journal-publisher'],
                ['onlineIssn', null, 'onlineIssn'],
                ['printIssn', null, 'printIssn'],
            ]);
        $journal->setName('journal-title', 'en');
        $journal->setPrimaryLocale('en');
        $journal->setPath('journal-path');
        $journal->setData(Journal::SETTING_ENABLE_DOIS, true);
        $journal->setId($journalId);

        // Section
        $section = new Section();
        $section->setIdentifyType('section-identify-type', 'en');
        $section->setTitle('section-identify-type', 'en');

        /** @var Doi|MockObject */
        $issueDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $issueDoiObject->setData('doi', 'issue-doi');

        // Issue
        /** @var Issue|MockObject */
        $issue = $this->getMockBuilder(Issue::class)
            ->onlyMethods(['getIssueIdentification'])
            ->getMock();
        $issue->expects($this->any())
            ->method('getIssueIdentification')
            ->will($this->returnValue('issue-identification'));
        $issue->setId(96);
        $issue->setDatePublished('2010-11-05');
        $issue->setData('doiObject', $issueDoiObject);
        $issue->setJournalId($journalId);

        //
        // Test
        //

        // OAI record
        $record = new OAIRecord();
        $record->setData('article', $article);
        $record->setData('galleys', $galleys);
        $record->setData('journal', $journal);
        $record->setData('section', $section);
        $record->setData('issue', $issue);

        return $record;
    }
    /**
     * test back element if citations table doesn't have records
     * @throws \DOMException
     */
    public function testCreate(){
        $OAIRecord = $this->createOAIRecordMockObject();
        $record =& $OAIRecord;
        $article = $this->createArticleMockInstance($record);
        $submission =& $record->getData('article');
        $publication = $submission->getCurrentPublication();

        $articleBackElement = new ArticleBack();
        $xml = $articleBackElement->create($publication,$article);
        self::assertXmlStringEqualsXmlString('<back/>',$article->saveXML($xml));
    }

    /**
     * test back element if citations table have below records
     * publication_id = 1
     * raw_citation = test
     * seq = 1
     * @throws \DOMException
     */
//    public function testCreateIfRecordsAvailable(){
//        $OAIRecord = $this->createOAIRecordMockObject();
//        $record =& $OAIRecord;
//        $article = $this->createArticleMockInstance($record);
//        $submission =& $record->getData('article');
//        $publication = $submission->getCurrentPublication();
//
//        $articleBackElement = new ArticleBack();
//        $xml = $articleBackElement->create($publication,$article);
//        self::assertXmlStringEqualsXmlFile($this->xmlFilePath.'articleBackElement.xml',$article->saveXML($xml));
//    }
}

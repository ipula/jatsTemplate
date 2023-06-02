<?php

namespace APP\plugins\generic\jatsTemplate\classes;

use APP\core\Services;
use APP\facades\Repo;
use APP\submission\Submission;
use PKP\config\Config;
use PKP\search\SearchFileParser;

class ArticleBody extends \DOMDocument
{
    /**
     * create xml body DOMNode
     * @param $article Article
     * @param $submission Submission
     * @return \DOMNode
     * @throws \DOMException
     */
    public function create(Submission $submission, Article $article):\DOMNode
    {
        // create element body
        $bodyElement = $article->createDomElement('body', null , []);
        $text = '';
        $galleys = $submission->getGalleys();

        // Give precedence to HTML galleys, as they're quickest to parse
        usort($galleys, function($a, $b) {
            return $a->getFileType() == 'text/html'?-1:1;
        });

        // Provide the full-text.
        $fileService = Services::get('file');
        foreach ($galleys as $galley) {
            $galleyFile = Repo::submissionFile()->get($galley->getData('submissionFileId'));
            if (!$galleyFile) continue;

            $filepath = $fileService->get($galleyFile->getData('fileId'))->path;
            $mimeType = $fileService->fs->mimeType($filepath);
            if (in_array($mimeType, ['text/html'])) {
                static $purifier;
                if (!$purifier) {
                    $config = \HTMLPurifier_Config::createDefault();
                    $config->set('HTML.Allowed', 'p');
                    $config->set('Cache.SerializerPath', 'cache');
                    $purifier = new \HTMLPurifier($config);
                }
                // Remove non-paragraph content
                $text = $purifier->purify(file_get_contents(Config::getVar('files', 'files_dir') . '/' . $filepath));
                // Remove empty paragraphs
                $text = preg_replace('/<p>[\W]*<\/p>/', '', $text);
            } else {
                $parser = SearchFileParser::fromFile($galleyFile);
                if ($parser && $parser->open()) {
                    while(($s = $parser->read()) !== false) $text .= $s;
                    $parser->close();
                }
                // create element p
                $paragraphElement = $article->createDomElement('p', htmlspecialchars($text, ENT_IGNORE) , []);
            }
            // Use the first parseable galley.
            if (!empty($text)) break;
        }
        if (!empty($text))
        {
            // append element p to body
            $article->appendChildToParent($bodyElement,$paragraphElement);
        }

        return $bodyElement;
    }
}

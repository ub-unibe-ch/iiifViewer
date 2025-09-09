<?php

/**
 * @file plugins/generic/iiifViewer/IiifViewerPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IiifViewerPlugin
 * @ingroup plugins_generic_iiifViewer
 *
 * @brief Class for IiifViewer plugin
 */

namespace APP\plugins\generic\iiifviewer;

use APP\core\Application;
use APP\core\Request;
use APP\core\Services;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\observers\events\UsageEvent;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\PKPRequest;
use PKP\galley\Galley;
use PKP\plugins\Hook;
use PKP\submissionFile\SubmissionFile;


class IiifViewerPlugin extends \PKP\plugins\GenericPlugin
{

    /**
     * @copydoc LazyLoadPlugin::register()
     *
     * @param null|mixed $mainContextId
     *
     * @return boolean
     */
    public function register($category, $path, $mainContextId = null)
    {
        $context = Application::getName();
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                $request = Application::get()->getRequest();
                $url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/iiifviewer.css';

                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->addStyleSheet('iiifViewerStyles', $url);

                switch (Application::getName()) {
                    case 'ojs2':
                        Hook::add('ArticleHandler::view::galley', [$this, 'articleCallback']);
                        Hook::add('IssueHandler::view::galley', [$this, 'issueCallback']);
                        break;
                    case 'omp':
                        Hook::add('CatalogBookHandler::view', [$this, 'ompViewCallback'], HOOK::SEQUENCE_NORMAL);
                        break;
                    case 'ops':
                        break;
                    default:
                        throw new \Exception('Unsupported application!');
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Install default settings on press creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return String
     */
    public function getDisplayName()
    {
        return __('plugins.generic.iiifViewer.displayName');
    }

    /**
     * Get a description of the plugin.
     *
     * @return String
     */
    public function getDescription()
    {
        return __('plugins.generic.iiifViewer.description');
    }

    /**
     * Callback to view the Image content rather than downloading for an OMP Monograph.
     * @param string $hookName
     * @param array $args
     *
     * @return boolean
     */
    public function ompViewCallback($hookName, $args)
    {
        $submission =& $args[1];
        $publicationFormat =& $args[2];
        $submissionFile =& $args[3];

        $mime_type = $submissionFile->getData('mimetype');

        if ($mime_type == 'application/json') {
            $this->viewImageFile($publicationFormat, $submission, $submissionFile, "display_manifest.tpl");
            return true;

        } elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
            $this->viewImageFile($publicationFormat, $submission, $submissionFile, "display.tpl");
            return true;
        }

        return false;
    }

    function isIIIFManifest(Galley $galley): bool
    {
        $fileId = $galley->getData('submissionFileId');
        if (!$fileId) {
            return false; // No file associated with the galley
        }

        $submissionFile = Repo::submissionFile()->get($fileId);
        if (!$submissionFile) {
            return false; // File could not be retrieved
        }

        $filePath = $submissionFile->getData('path');
        $absolutePath = Config::getVar('files', 'files_dir') . '/' . $filePath;

        if (!file_exists($absolutePath)) {
            return false; // File does not exist
        }

        $contents = file_get_contents($absolutePath);
        if (!$contents) {
            return false; // Failed to read file contents
        }

        $decodedJson = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedJson)) {
            return false; // Invalid or unreadable JSON
        }

        return isset($decodedJson['@context']) && strpos($decodedJson['@context'], 'iiif.io/api/presentation') !== false;
    }


    /**
     * Callback to view the Image content rather than downloading for an OJS Article.
     * @param string $hookName
     * @param array $args
     *
     * @return boolean
     */
    public function articleCallback($hookName, $args)
    {
        $request =& $args[0];
        $issue =& $args[1];
        $galley =& $args[2];
        $submission =& $args[3];

        $router = $request->getRouter();
        $contextPath = $router->getRequestedContextPath($request, 1);

        if ($galley) {
            $galleyPublication = null;
            $galleyTemplate = null;
            foreach ($submission->getData('publications') as $publication) {
                if ($publication->getId() === $galley->getData('publicationId')) {
                    $galleyPublication = $publication;
                    break;
                }
            }
            $mime_type = $galley->getFileType();
            if ($mime_type == 'application/json') {
                if ($this->isIIIFManifest($galley)) {
                    $galleyTemplate = 'article_manifest.tpl';
                } else {
                    return false; // just some other JSON
                }
            } elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                $galleyTemplate = 'article_image.tpl';
            } else {
                return false;
            }

            $isLatestPublication = $submission->getData('currentPublicationId') === $galley->getData('publicationId');
            $bestId = $submission->getBestId();
            $galleyBestId = $galley->getBestGalleyId();
            $galleyFile = $galley->getFile();
            $apiParams['inline'] = 'true';
            $apiPath = [];
            if ($isLatestPublication) {
                $apiPath = [$bestId, $galleyBestId, $galleyFile->getId()];
            } else {
                $apiPath = [$bestId, 'version', $galleyPublication->getId(), $galleyBestId, $galleyFile->getId()];
            }

            $apiUrl = $request->url(null, 'article', 'download', $apiPath, $apiParams);

            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->assign([
                'apiUrl' => $apiUrl,
                'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
                'isLatestPublication' => $isLatestPublication,
            ]);

            $templateMgr->display($this->getTemplateResource($galleyTemplate));
            return true;
        }
        return false;
    }

    /**
     * Callback that renders the issue galley.
     * @param string $hookName
     * @param array $args
     *
     * @return bool
     */
    function issueCallback($hookName, $args)
    {
        $request =& $args[0];
        $issue =& $args[1];
        $galley =& $args[2];

        $router = $request->getRouter();
        $contextPath = $router->getRequestedContextPath($request, 1);
        if ($galley) {
            $galleyTemplate = null;
            $mime_type = $galley->getFileType();

            if ($mime_type == 'application/json') {
                if ($this->isIIIFManifest($galley)) {
                    $galleyTemplate = 'issue_manifest.tpl';
                } else {
                    return false; // just some other JSON
                }
            } elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                $galleyTemplate = 'issue_image.tpl';
            } else {
                return false;
            }

            $issueBestId = $issue->getBestIssueId();
            $galleyBestId = $galley->getBestGalleyId();
            $galleyFile = $galley->getFile();

            $apiPath = [$issueBestId, $galleyBestId, $galleyFile->getId()];
            $apiParams['inline'] = 'true';
            $apiUrl = $request->url(null, 'issue', 'download', $apiPath, $apiParams);

            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->assign([
                'apiUrl' => $apiUrl,
                'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
                'issue' => $issue,
            ]);

            $templateMgr->display($this->getTemplateResource($galleyTemplate));
            return true;
        }
        return false;
    }


    /**
     * function to prepare IIIFViewer data for an image file.
     * @param PublicationFormat $publicationFormat
     * @param Submission $submission
     * @param SubmissionFile $submissionFile
     * @param string $theTemplate
     *
     * @return boolean
     */
    private function viewImageFile($publicationFormat, $submission, $submissionFile, $theTemplate)
    {

        foreach ($submission->getData('publications') as $publication) {
            if ($publication->getId() === $publicationFormat->getData('publicationId')) {
                $filePublication = $publication;
                break;
            }
        }
        $fileService = Services::get('file');
        $imgfile = $fileService->get($submissionFile->getData('fileId'));
        $imgpath = $imgfile->path;

        $fileId = $submissionFile->getId();
        $submissionId = $submission->getId();
        $format = $publicationFormat->getBestId();

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $contextPath = $router->getRequestedContextPath($request, 1);

        $apiPath = [$submissionId, $format, $fileId];
        $apiParams['inline'] = 'true';
        $apiUrl = $request->url(null, 'catalog', 'download', $apiPath, $apiParams);

        $templateMgr = TemplateManager::getManager($request);

        if (method_exists($submission, 'getLocalizedTitle')) {
            $monographTitle = $submission->getLocalizedTitle();
        } else {
            // OMP 3.4+ (titles stored in data));
            $monographTitle = $submission->getData('fullTitle');
        }


        $templateMgr->assign(array(
            'apiUrl' => $apiUrl,
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'isLatestPublication' => $submission->getData('currentPublicationId') === $publicationFormat->getData('publicationId'),
            'monographTitle' => $monographTitle,
        ));


        $templateMgr->display($this->getTemplateResource($theTemplate));

        return true;
    }

    /**
     * Get the plugin base URL.
     * @param PKPRequest $request
     *
     * @return string
     */
    private function _getPluginUrl($request)
    {
        return $request->getBaseUrl() . '/' . $this->getPluginPath();
    }
}

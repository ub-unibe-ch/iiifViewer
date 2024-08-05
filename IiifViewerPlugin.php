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


class IiifViewerPlugin extends \PKP\plugins\GenericPlugin {

	/**
     * @copydoc LazyLoadPlugin::register()
	 *
	 * @param null|mixed $mainContextId
	 *
	 * @return boolean
	 */
	public function register($category, $path, $mainContextId = null) {
        $context = Application::getName();
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
		    	$request = Application::get()->getRequest();
			   	$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/iiifviewer.css';
			    $templateMgr = TemplateManager::getManager($request);
		    	$templateMgr->addStyleSheet('iiifViewerStyles', $url);
                if (str_starts_with($context, 'ojs')) {
				    Hook::add('ArticleHandler::view::galley', [$this, 'articleCallback']);
				    Hook::add('IssueHandler::view::galley', [$this, 'issueCallback']);
                } elseif (str_starts_with($context, 'omp')) {
			    	Hook::add('CatalogBookHandler::view', [$this, 'ompViewCallback'], HOOK::SEQUENCE_NORMAL);
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
	public function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
     *
	 * @return String
	 */
	public function getDisplayName() {
		return __('plugins.generic.iiifViewer.displayName');
	}

	/**
	 * Get a description of the plugin.
     *
	 * @return String
	 */
	public function getDescription() {
		return __('plugins.generic.iiifViewer.description');
	}

	/**
	 * Callback to view the Image content rather than downloading for an OMP Monograph.
	 * @param string $hookName
	 * @param array $args
     *
	 * @return boolean
	 */
	public function ompViewCallback($hookName, $args) {
		$submission =& $args[1];
		$publicationFormat =& $args[2];
		$submissionFile =& $args[3];

		$mime_type = $submissionFile->getData('mimetype');

		if ($mime_type == 'application/json') {
			$this->viewImageFile($publicationFormat, $submission, $submissionFile, "display_manifest.tpl" );
			return true;

		} elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
			$this->viewImageFile($publicationFormat, $submission, $submissionFile, "display.tpl" );
			return true;
		}

		return false;
	}

	/**
	 * Callback to view the Image content rather than downloading for an OJS Article.
	 * @param string $hookName
	 * @param array $args
     *
	 * @return boolean
	 */
    public function articleCallback($hookName, $args) {
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
                $galleyTemplate = 'article_manifest.tpl';
		    } elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                $galleyTemplate = 'article_image.tpl';
		    } else {
                return false;
            }

		    $isLatestPublication = $submission->getData('currentPublicationId') === $galley->getData('publicationId');
			$bestId = $submission->getBestId();
			$galleyBestId = $galley->getBestGalleyId();
			$galleyFile = $galley->getFile();
            $apiUrl = null;
            if ($isLatestPublication) {
		        $apiUrl = $request->getIndexUrl().'/'.$contextPath.'/article/download/'.$bestId.'/'.$galleyBestId.'/'.$galleyFile->getId().'?inline=1';
            } else {
		        $apiUrl = $request->getIndexUrl().'/'.$contextPath.'/article/download/'.$bestId.'/version/'.$galleyPublication->getId().'/'.$galleyBestId.'/'.$galleyFile->getId().'?inline=1';
            }

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
	function issueCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];

		$router = $request->getRouter();
		$contextPath = $router->getRequestedContextPath($request, 1);

		if ($galley) {
			$galleyTemplate = null;
		    $mime_type = $galley->getFileType();
    		if ($mime_type == 'application/json') {
                $galleyTemplate = 'issue_manifest.tpl';
		    } elseif (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                $galleyTemplate = 'issue_image.tpl';
		    } else {
                return false;
            }

            $issueBestId = $issue->getBestIssueId();
			$galleyBestId = $galley->getBestGalleyId();
			$galleyFile = $galley->getFile();

		    $apiUrl = $request->getIndexUrl().'/'.$contextPath.'/issue/download/'.$issueBestId.'/'.$galleyBestId.'/'.$galleyFile->getId().'?inline=1';

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
	private function viewImageFile($publicationFormat, $submission, $submissionFile, $theTemplate) {

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

		$apiUrl = $request->getIndexUrl().'/'.$contextPath.'/catalog/download/'.$submissionId.'/'.$format.'/'.$fileId.'?inline=1';

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'apiUrl' => $apiUrl,
			'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
			'isLatestPublication' => $submission->getData('currentPublicationId') === $publicationFormat->getData('publicationId'),
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
	private function _getPluginUrl($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath();
	}
}



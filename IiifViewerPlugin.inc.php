<?php

/**
 * @file plugins/generic/iiifViewer/IiifViewerPlugin.inc.php
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

import('lib.pkp.classes.plugins.GenericPlugin');

class IiifViewerPlugin extends GenericPlugin {
        /**
         * @copydoc Plugin::register()
         */
        function register($category, $path, $mainContextId = null) {
                if (parent::register($category, $path, $mainContextId)) {
                        if ($this->getEnabled($mainContextId)) {
                                $request = Application::get()->getRequest();
                                $url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/iiifviewer.css';
                                $templateMgr = TemplateManager::getManager($request);
                                $templateMgr->addStyleSheet('iiifViewerStyles', $url);

                                HookRegistry::register('CatalogBookHandler::view', array($this, 'viewCallback'), HOOK_SEQUENCE_NORMAL);
                        }
                        return true;
                }
                return false;
        }

        /**
         * Install default settings on press creation.
         * @return string
         */
        function getContextSpecificPluginSettingsFile() {
                return $this->getPluginPath() . '/settings.xml';
        }

        /**
         * Get the display name of this plugin.
         * @return String
         */
        function getDisplayName() {
                return __('plugins.generic.iiifViewer.displayName');
        }

        /**
         * Get a description of the plugin.
         */
        function getDescription() {
                return __('plugins.generic.iiifViewer.description');
        }

        /**
         * Callback to view the Image content rather than downloading.
         * @param $hookName string
         * @param $args array
         * @return boolean
         */
        function viewCallback($hookName, $args) {
                $submission =& $args[1];
                $publicationFormat =& $args[2];
                $submissionFile =& $args[3];

                $mime_type = $submissionFile->getData('mimetype');
                $format = $publicationFormat->getBestId();

                $contextid = $this->getCurrentContextId();

                if ($mime_type == 'application/json') {
error_log("iiifviewer::viewCallback called with JSON mime type format[".$format."] format id[".$publicationFormat->getId()."]\n");
                        $this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display_manifest.tpl" );
                        return true;

                } elseif ($format == 'iiif_manifest' && (($mime_type == 'application/json') || ($mime_type == 'text/plain'))) {
                        $this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display_manifest.tpl" );
                        return true;

                } elseif ($mime_type == 'image/jpeg') {
                        $this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display.tpl" );
                        return true;
                }

                return false;
        }

        /**
         * function to prepare IIIFViewer data for an image file.
         * @param $publicationFormat PublicationFormat
         * @param $mime_type string
         * @param $submission Submission
         * @param $submissionFile SubmissionFile
         * @param $submission Submission
         * @param $theTemplate string
         * @return boolean
         */
        function viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, $theTemplate) {

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
                $fileStage = $submissionFile->getFileStage();
                $subStageId = $submission->getStageId();
                $submissionId = $submission->getId();
                $format = $publicationFormat->getBestId();

error_log("iiifviewer::viewImageFile called submissionid[".$submissionId."] fileid[".$fileId."] sub stage [".$subStageId."] filestage[".$fileStage."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."] format[".$format."]\n");

                $request = Application::get()->getRequest();
                $router = $request->getRouter();
                $contextPath = $router->getRequestedContextPath($request, 1);

                //$apiUrl = $request->getIndexUrl()."/".$contextPath.'/$$$call$$$/api/file/file-api/download-file?submissionFileId='.$fileId."&submissionId=".$submissionId."&stageId=".$subStageId;

                $apiUrl = $request->getIndexUrl().'/'.$contextPath.'/catalog/download/'.$submissionId.'/'.$format.'/'.$fileId.'?inline=1';

                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->assign(array(
                        'apiUrl' => $apiUrl,
                        'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
                        'isLatestPublication' => $submission->getData('currentPublicationId') === $publicationFormat->getData('publicationId'),
                        'filePublication' => $filePublication,
                        'subId' => $submissionId,
                        'subStageId' => $subStageId,
                        'fileId' => $fileId,
                ));

                $templateMgr->display($this->getTemplateResource($theTemplate));

                return true;
        }

        /**
         * Get the plugin base URL.
         * @param $request PKPRequest
         * @return string
         */
        private function _getPluginUrl($request) {
                return $request->getBaseUrl() . '/' . $this->getPluginPath();
        }
}

<?php

/**
 * @file plugins/generic/iiifViewer/IiifViewerPlugin.inc.php
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

import('lib.pkp.classes.plugins.GenericPlugin');

class IiifViewerPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				$request = Application::get()->getRequest();
				$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/iiifviewer.css';
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->addStyleSheet('iiifViewerStyles', $url);

				HookRegistry::register('CatalogBookHandler::view', array($this, 'viewCallback'), HOOK_SEQUENCE_NORMAL);
			}
			return true;
		}
		return false;
	}

	/**
	 * Install default settings on press creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.iiifViewer.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.iiifViewer.description');
	}

	/**
	 * Callback to view the Image content rather than downloading.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function viewCallback($hookName, $args) {
		$submission =& $args[1];
		$publicationFormat =& $args[2];
		$submissionFile =& $args[3];

		$mime_type = $submissionFile->getData('mimetype');
		$format = $publicationFormat->getBestId();

		$contextid = $this->getCurrentContextId();

<<<<<<< HEAD
		if ($mime_type == 'application/json') {
error_log("iiifviewer::viewCallback called with JSON mime type format[".$format."] format id[".$publicationFormat->getId()."]\n");
			$this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display_manifest.tpl" );
			return true;

		} elseif ($format == 'iiif_manifest' && (($mime_type == 'application/json') || ($mime_type == 'text/plain'))) {
=======
		if ($format == 'iiif_manifest' && (($mime_type == 'application/json') || ($mime_type == 'text/plain'))) {
>>>>>>> e947fd39efd7a71e0ba5ec2b1a1dbd63b09fafad
			$this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display_manifest.tpl" );
			return true;

		} elseif ($mime_type == 'image/jpeg') {
			$this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, "display.tpl" );
			return true;
		}

		return false;
	}

	/**
	 * function to prepare IIIFViewer data for an image file.
	 * @param $publicationFormat PublicationFormat
	 * @param $mime_type string
	 * @param $submission Submission
	 * @param $submissionFile SubmissionFile
	 * @param $submission Submission
	 * @param $theTemplate string
	 * @return boolean
	 */
	function viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile, $theTemplate) {

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
		$fileStage = $submissionFile->getFileStage();
		$subStageId = $submission->getStageId();
		$submissionId = $submission->getId();
		$format = $publicationFormat->getBestId();

error_log("iiifviewer::viewImageFile called submissionid[".$submissionId."] fileid[".$fileId."] sub stage [".$subStageId."] filestage[".$fileStage."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."] format[".$format."]\n");

		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		$contextPath = $router->getRequestedContextPath($request, 1);

		//$apiUrl = $request->getIndexUrl()."/".$contextPath.'/$$$call$$$/api/file/file-api/download-file?submissionFileId='.$fileId."&submissionId=".$submissionId."&stageId=".$subStageId;

		$apiUrl = $request->getIndexUrl().'/'.$contextPath.'/catalog/download/'.$submissionId.'/'.$format.'/'.$fileId.'?inline=1';

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'apiUrl' => $apiUrl,
			'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
			'isLatestPublication' => $submission->getData('currentPublicationId') === $publicationFormat->getData('publicationId'),
			'filePublication' => $filePublication,
			'subId' => $submissionId,
			'subStageId' => $subStageId,
			'fileId' => $fileId,
		));

		$templateMgr->display($this->getTemplateResource($theTemplate));

		return true;
	}

	/**
	 * Get the plugin base URL.
	 * @param $request PKPRequest
	 * @return string
	 */
	private function _getPluginUrl($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath();
	}
}




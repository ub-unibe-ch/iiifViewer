<?php

/**
 * @file plugins/generic/pdfJsViewer/IiifViewerPlugin.inc.php
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

error_log("iiifviewer::viewCallback called [".$hookName."] context[".$contextid."] submissionid[".$submission->getId()."] mimetype [".$mime_type."] format[".$format."] \n");



		if ($format == 'iiif_manifest' && (($mime_type == 'application/json') || ($mime_type == 'text/plain'))) {
			$this->viewManifestFile($publicationFormat, $mime_type, $submission, $submissionFile);
			return true;

		} elseif ($mime_type == 'image/jpeg') {
			$this->viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile);
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
	 * @return boolean
	 */
	function viewImageFile($publicationFormat, $mime_type, $submission, $submissionFile) {

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

error_log("iiifviewer::viewImageFile called submissionid[".$submission->getId()."] fileid[".$fileId."] sub stage [".$subStageId."] filestage[".$fileStage."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."]\n");

		$request = Application::get()->getRequest();

		$contextPath = $request->getRequestedContextPath();
		$apiUrl = $request->getIndexUrl()."/".$contextPath[0].'/$$$call$$$/api/file/file-api/download-file?submissionFileId='.$fileId."&submissionId=".$submissionId."&stageId=".$subStageId;

		$router = $request->getRouter();
		$dispatcher = $request->getDispatcher();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'apiUrl' => $apiUrl,
			'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
			'isLatestPublication' => $submission->getData('currentPublicationId') === $publicationFormat->getData('publicationId'),
			'filePublication' => $filePublication,
			'subId' => $submission->getId(),
			'subStageId' => $submission->getStageId(),
			'fileId' => $fileId,
		));

		$templateMgr->display($this->getTemplateResource('display.tpl'));
//error_log("iiifviewer::viewCallback called [".$hookName."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."] showing template display.tpl for filePublication[".print_r($filePublication)."] filepath[".$imgpath."] returning TRUE\n");
		return true;
	}

	/**
	 * function to prepare IIIFViewer data for a manifest file.
	 * @param $publicationFormat PublicationFormat
	 * @param $mime_type string
	 * @param $submission Submission
	 * @param $submissionFile SubmissionFile
	 * @return boolean
	 */
	function viewManifestFile($publicationFormat, $mime_type, $submission, $submissionFile) {

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

error_log("iiifviewer::viewManifestFile called submissionid[".$submissionId."] fileid[".$fileId."] sub stage [".$subStageId."] filestage[".$fileStage."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."]\n");

		$request = Application::get()->getRequest();

error_log("###iiifviewer context[".print_r($request->getRequestedContextPath(), true)."]\n");
error_log("###iiifviewer baseurl[".print_r($request->getBaseUrl(), true)."]\n");
error_log("###iiifviewer indexurl[".print_r($request->getIndexUrl(), true)."]\n");
		$contextPath = $request->getRequestedContextPath();

		$apiUrl = $request->getIndexUrl()."/".$contextPath[0].'/$$$call$$$/api/file/file-api/download-file?submissionFileId='.$fileId."&submissionId=".$submissionId."&stageId=".$subStageId;

		$router = $request->getRouter();
		$dispatcher = $request->getDispatcher();
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

		$templateMgr->display($this->getTemplateResource('display_manifest.tpl'));
		//$templateMgr->display($this->getTemplateResource('display_manifest_test.tpl'));


//error_log("iiifviewer::viewCallback called [".$hookName."] mimetype [".$submissionFile->getData('mimetype')."] path[".$this->getPluginPath()."] showing template display.tpl for filePublication[".print_r($filePublication)."] filepath[".$imgpath."] returning TRUE\n");
	
		return false;
	}


	/**
	 * Callback for download function
	 * @param $hookName string
	 * @param $params array
	 * @return boolean
	 */
	function downloadCallback($hookName, $params) {
		$submission =& $params[1];
		$publicationFormat =& $params[2];
		$submissionFile =& $params[3];
		$inline =& $params[4];
error_log("iiifviewer::downloadCallback called hookname [".$hookname."] path[".$this->getPluginPath()."]\n");

		$request = Application::get()->getRequest();
		$mimetype = $submissionFile->getData('mimetype');
		if ($mimetype == 'application/pdf' && $request->getUserVar('inline')) {
			// Turn on the inline flag to ensure that the content
			// disposition header doesn't foil the PDF embedding
			// plugin.
			$inline = true;
		}

		// Return to regular handling
		return false;
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



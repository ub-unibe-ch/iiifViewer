{**
 * plugins/generic/iiifViewer/templates/display.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of an IIIF image file.
 *}

{include file="frontend/components/header.tpl" pageTitle="viewer.iiif"}

<div class="page page_viewer">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="navigation.iiifviewer"}

	<h1>{translate key="viewer.iiif"}</h1>

	{capture assign="submissionUrl"}{url op="book" path=$publishedSubmission->getBestId()}{/capture}

	<div class="viewer_return">
		<a href="{$submissionUrl}" class="return">
			<span class="pkp_screen_reader">
				{translate key="catalog.viewableFile.return" monographTitle=$publishedSubmission->getLocalizedTitle()|escape}
			</span>
			<span class="iiifviewer_return">
				{translate key="catalog.viewableFile.return" monographTitle=$publishedSubmission->getLocalizedTitle()|escape}
			</span>
		</a>
	</div>

	<span class="title">
		Filename: {$submissionFile->getLocalizedData('name')|escape}
	</span>

	<div id="openseadragon1" style="width: 800px; height: 600px;"></div>
<!--	<div id="openseadragon2" style="width: 800px; height: 600px;"></div>
-->
	<script type="text/javascript" src="{$pluginUrl}/openseadragon/openseadragon.min.js"></script>

	<!-- an example simple image -->
	<script type="text/javascript">
	    var viewer = OpenSeadragon({
        	id: "openseadragon1",
        	prefixUrl: "{$pluginUrl}/openseadragon/images/",

    		preserveViewport: true,
    		visibilityRatio:    1,
    		minZoomLevel:       1,
    		defaultZoomLevel:   1,
    		sequenceMode:       true,
    		tileSources:   [{
			"type": "image",
			<!--"url": "http://localhost/emono/index.php/emono/$$$call$$$/api/file/file-api/download-file?submissionFileId={$fileId}&submissionId={$subId}&stageId={$subStageId}" -->
			"url": "{$apiUrl}"
		}]
	    });
	</script>

	<!-- an example IIIF image -->
<!--	<script type="text/javascript">
	    var viewer = OpenSeadragon({
        	id: "openseadragon2",
        	prefixUrl: "{$pluginUrl}/openseadragon/images/",

    		preserveViewport: true,
    		visibilityRatio:    1,
    		minZoomLevel:       1,
    		defaultZoomLevel:   1,
    		sequenceMode:       true,
    		tileSources:   [{
      			"@context": "http://iiif.io/api/image/2/context.json",
      			"@id": "https://libimages1.princeton.edu/loris/pudl0001%2F4609321%2Fs42%2F00000001.jp2",
      			"height": 7200,
      			"width": 5233,
      			"profile": [ "http://iiif.io/api/image/2/level2.json" ],
      			"protocol": "http://iiif.io/api/image",
      			"tiles": [{
        			"scaleFactors": [ 1, 2, 4, 8, 16, 32 ],
        			"width": 1024
      			}]		
		}]
	    });
	</script>
-->
</div>
{include file="frontend/components/footer.tpl"}

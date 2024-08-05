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

	<div id="openseadragon1" style="width: 800px; height: 600px;"></div>

	<script type="text/javascript" src="{$pluginUrl}/openseadragon/openseadragon.min.js"></script>

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
			"url": "{$apiUrl}"
		}]
	    });
	</script>
</div>
{include file="frontend/components/footer.tpl"}

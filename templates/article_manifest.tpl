{**
 * plugins/generic/iiifViewer/templates/display.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of an IIIF image file.
 *}

{include file="frontend/components/header.tpl" pageTitle="viewer.mirador.iiif"}

<div class="page page_viewer">

	<!-- By default uses Roboto font. Be sure to load this or change the font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
	<!-- Container element of Mirador whose id should be passed to the instantiating call as "id" -->

	<div id="my-mirador" style="width: 800px; height: 600px;"></div>

	<script src="https://unpkg.com/mirador@latest/dist/mirador.min.js"></script>

	<script type="text/javascript">
	  var mirador = Mirador.viewer({
	    "id": "my-mirador",
	    "manifests": {
		"{$apiUrl}" :{
		  //"provider": "Basel University"
	      }
	    },
	    "windows": [
	      {
	        "loadedManifest": "{$apiUrl}",
	        "canvasIndex": 2,
	        "thumbnailNavigationPosition": 'far-bottom'
	      }
	    ]
	  });
	</script>

</div>
{include file="frontend/components/footer.tpl"}

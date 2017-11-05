<?php
defined("ABSPATH") or die("");
DUP_PRO_U::hasCapability('manage_options');
?>

<style>
form#dpro-migration-form { padding:15px; border: 1px solid silver; border-radius: 5px; background:#ffffff; min-height:375px}
div#dpro-dd-target {margin:5px 0; text-align: center }
div.fs-upload-target {border:3px dashed silver !important; border-radius:8px !important;  color:#000 !important; height:200px; padding: 10px !important; }

div.step-state {display:block}
div.step-err {display:none; color:maroon; font-size: 18px; font-style: italic; line-height: 26px}
div#dpro-step-1 {padding:5px;}
input#dpro-step-1-btn	{margin:15px auto; display:block}
div#dpro-step-1-target {font-size:18px; line-height:26px; font-weight: bold; color:#555}
div#dpro-step-2 {display:none;}
div#dpro-step-3 {display:none; font-size:14px; text-align: left; width:800px; margin:auto;}
div#dpro-step-3 h2 {text-align: center; font-size:18px; color:green; line-height: 22px; font-weight: bold}
button#dpro-launch-btn {font-weight: bold; font-size: 16px}

.filelists {margin:0}
.filelists .cancel_all {color: red;	cursor: pointer; clear: both; font-size: 10px; margin: 0; text-transform: uppercase;}
.filelist {margin: 0; padding:0;}
.filelist li {background: #fff;	border-bottom: 1px solid #ECEFF1; font-size: 16px; list-style: none; padding:15px; position: relative; border:1px solid silver; border-radius: 3px}
.filelist li:before {display: none !important;}
.filelist li .bar {background: #CCCCCC; content: ''; height: 100%; left: 0; position: absolute; top: 0;	width: 0; z-index: 0;  transition: width 0.1s linear;}
.filelist li .content {display: block; overflow: hidden; position: relative; z-index: 1; font-weight: bold; color:#000}
.filelist li .file {color: #000;	float: left;display: block;	overflow: hidden;text-overflow: ellipsis; max-width: 50%; white-space: nowrap;}
.filelist li .progress {color: #000;	display: block;	float: right; font-size: 14px;text-transform: uppercase;}
.filelist li .cancel {color: red;cursor: pointer;display: block;float: right;font-size: 14px;margin: 0 0 0 10px;text-transform: uppercase;}
.filelist li.error .file {color: red;}
.filelist li.error .progress {color: red;}
.filelist li.error .cancel {display: none;}
</style>

<h2><i class="fa fa-upload"></i> <?php DUP_PRO_U::_e("Import Site"); ?></h2>
<form id="dpro-migration-form">

	<!-- STEP 1: Select File -->
	<div id="dpro-step-1" class="step-state">
		<?php DUP_PRO_U::_e("Use the box below to upload a duplicator archive file (zip/daf)."); ?><br />
		<div id="dpro-dd-target">
			<div id="dpro-step-1-target">

				<i class="fa fa-cloud-upload fa-3x" ></i><br>
				<div id="dpro-step-1-label">
					<?php DUP_PRO_U::_e("Drag &amp; Drop to Upload<br/> Duplicator Archive"); ?>
				</div>


				<!-- ERROR MESSAGES:  -->
				<div id="dpro-step-10" class="step-state step-err">
					<i class="fa fa-warning"></i>
					<?php DUP_PRO_U::_e("Only file types .zip &amp; .daf are supported!<br/> Please try again!"); ?>
				</div>

				<div id="dpro-step-11" class="step-state step-err">
					<i class="fa fa-warning"></i>
					<?php DUP_PRO_U::_e("Upload request aborted by user!<br/> Please try again!"); ?>
				</div>
                
                <div id="dpro-step-12" class="step-state step-err">
					<i class="fa fa-warning"></i>
					<?php DUP_PRO_U::_e("Error uploading file!<br/> Please try again!"); ?>
				</div>
				<input id="dpro-step-1-btn" type="button" class="button button-large" name="dpro-files" id="dpro-daf-upload-btn" value="<?php DUP_PRO_U::_e("Select File"); ?>">
			</div>

		</div>
	</div>

	<!-- STEP 2: Progress Bar -->
	<div id="dpro-step-2" class="step-state">
		<div class="filelists">
			<!-- <h5>Complete</h5>
			<ol class="filelist complete"></ol>-->
			<h2><?php DUP_PRO_U::_e("Uploading Please Wait..."); ?></h2>
			<ol class="filelist queue"></ol>
			<!-- <span class="cancel_all">Cancel All</span>-->
		</div>
	</div>

	<!-- STEP 3: Complete Message -->
	<div id="dpro-step-3" class="step-state">
		<h2>
			<i class="fa fa-check" ></i>
			<?php DUP_PRO_U::_e("Archive Ready for Install!"); ?></h2>
			<?php DUP_PRO_U::_e("The archive is fully uploaded and ready to be installed over this site.  This process will <u><b>overwrite</b></u> this entire site you are "
			. "currently logged into.  All plugins, themes, content and data will be replaced with the content found in the archive file.  The following database "
			. "credential below will be used to for the database overwrite.  The values can be changed at install time if needed."); ?> <br/><br/>

		<div style="margin:auto; text-align: center;">
			<div style="text-align: left;">
				<table style="margin:auto">
					<tr>
						<td colspan="2"><b><u><?php DUP_PRO_U::_e("Database"); ?></u></b></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Host:"); ?></td>
						<td><?php echo DB_HOST ?></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Name:"); ?></td>
						<td><?php echo DB_NAME  ?></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("User:"); ?></td>
						<td><?php echo DB_USER ?></td>
					</tr>
				</table>
			</div>
			<br/><br/>
			<button id="dpro-launch-btn" type="button" class="button button-large button-primary"><i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e("Launch Installer"); ?></button>
			<br/><br/>

			<small><a href="javascript:void(0)" onclick="location.reload()">[<?php DUP_PRO_U::_e("Cancel Import &amp; Refresh"); ?>]</a></small>
		</div>
            
    <!-- STEP 4: Error Message -->
	<div id="dpro-step-4" class="step-state">
		<h2>
			<i class="fa fa-check" ></i>
			<?php DUP_PRO_U::_e("Error Uploading Archive!"); ?></h2>

			<small><a href="javascript:void(0)" onclick="location.reload()">[<?php DUP_PRO_U::_e("Reset"); ?>]</a></small>
		</div>
	</div>
</form>


<?php
$ajax_nonce	= wp_create_nonce('DUP_PRO_CTRL_Tools_migrationUploader');
//$chunk_size = 48576;
$chunk_size = 2048;
$chunk_mode = 'chunked'; //chunked, direct
$max_size   = 107374182400; //100GB
?>

<script>
jQuery(document).ready(function ($)
{
	var DPRO_UPLOAD_STEP = 1;
	var DPRO_UPLOADER;
	var DPRO_DEBUG = true;

    DupPro.Tools.lastFileUploaded = null;

	DupPro.Tools.initUploader = function()
	{
		var data = {action		: 'DUP_PRO_CTRL_Tools_migrationUploader',
					nonce		: '<?php echo $ajax_nonce; ?>',
					chunk_size	: '<?php echo $chunk_size; ?>',
					chunk_mode	: <?php echo "'{$chunk_mode}'"; ?>};

		var url = "<?php echo get_admin_url(); ?>admin-ajax.php?action=DUP_PRO_CTRL_Tools_migrationUploader";
		var $steps = $('#dpro-step-1-target');
		
		//Create uploader
		DPRO_UPLOADER = $("div#dpro-dd-target").upload({
				autoUpload: true,
				multiple: false,
				maxSize: <?php echo $max_size; ?>,
				maxFiles: 1,
				postData : data,
				chunkSize: <?php echo $chunk_size; ?>,
				action:url,
				chunked: <?php echo $chunk_mode == 'chunked' ? 'true' : 'false'; ?>,
				label: $steps.parent().html(),
				beforeSend: DupPro.Tools.onBeforeSend
			});

		//Attach to internal events
		DPRO_UPLOADER
			.on("start.upload", DupPro.Tools.onStart)
			.on("complete.upload", DupPro.Tools.onComplete)
			.on("filestart.upload", DupPro.Tools.onFileStart)
			.on("fileprogress.upload", DupPro.Tools.onFileProgress)
			.on("filecomplete.upload", DupPro.Tools.onFileComplete)
			.on("fileerror.upload", DupPro.Tools.onFileError)
			.on("chunkstart.upload", DupPro.Tools.onChunkStart)
			.on("chunkprogress.upload", DupPro.Tools.onChunkProgress)
			.on("chunkcomplete.upload", DupPro.Tools.onChunkComplete)
			.on("chunkerror.upload", DupPro.Tools.onChunkError)
			.on("queued.upload", DupPro.Tools.onQueued);

		$(".filelist.queue").on("click", ".cancel", DupPro.Tools.onCancel);
		$(".cancel_all").on("click", DupPro.Tools.onCancelAll);

		$steps.detach();
		DupPro.Tools.toggleStep(1);
	};


	DupPro.Tools.toggleStep = function(num)
	{
		DPRO_UPLOAD_STEP = num;

		$('#dpro-step-1-label').show();
		$('div.step-err').hide();
		switch (DPRO_UPLOAD_STEP) {				
			case 2:
				DPRO_UPLOADER.upload("disable");
				$('#dpro-step-2').show();
				break;
				
			case 3:
				$('div.step-state').hide();
				$('#dpro-step-3').show();
				break;
				
			default:
				DPRO_UPLOADER.upload("enable");
				$('div.step-state').hide();
				$('#dpro-step-1').show();
				break;
		}
		
		if (DPRO_UPLOAD_STEP >= 10) {
			$('#dpro-step-1-label').hide();
			$('#dpro-step-' + DPRO_UPLOAD_STEP).show(200);
		}
	};


	DupPro.Tools.onCancel = function(e)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "Cancel");
		var index = $(this).parents("li").data("index");
		DPRO_UPLOADER.upload("abort", parseInt(index, 10));
		DupPro.Tools.toggleStep(11);
	};

	DupPro.Tools.onBeforeSend = function (formData, file)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "Before Send");
		var validTypes = (file.name.indexOf(".daf") > 1 || file.name.indexOf(".zip") > 1)
		formData.append("test_field", "test_value");
		if (validTypes) {
			return  formData;
		} else {
			DupPro.Tools.toggleStep(10);
			return false;
		}
	};

	DupPro.Tools.onQueued = function (e, files)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "Queued");
		var html = '';
		var size = 0;
		for (var i = 0; i < files.length; i++) {
			size = DupPro.humanFileSize(files[i].size)
			html += '<li data-index="' + files[i].index + '"><span class="content">';
			html += '<span class="file">' + files[i].name + ' (' + size + ')' + '</span><span class="cancel">Cancel</span><span class="progress">Queued</span>';
			html += '</span><span class="bar"></span></li>';
		}
		$(this).parents("form").find(".filelist.queue").append(html);
	};

	DupPro.Tools.onStart = function (e, files)
    {
		DupPro.Tools.upDebug(DPRO_DEBUG, "Start");
		DupPro.Tools.toggleStep(2);
		//$(this).parents("form").find(".filelist.queue").find("li").find(".progress").text("Waiting");
	};

	DupPro.Tools.onFileStart = function (e, file)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "File Start");
		$(this).parents("form").find(".filelist.queue").find("li[data-index=" + file.index + "]").find(".progress").text("0%");
	};

	DupPro.Tools.onFileProgress = function (e, file, percent)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "File Progress");
		var $file = $(this).parents("form").find(".filelist.queue").find("li[data-index=" + file.index + "]");

		$file.find(".progress").text(percent + "%")
		$file.find(".bar").css("width", percent + "%");
	};

	DupPro.Tools.onFileComplete = function (e, file, response)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "File Complete");
		if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {
			$(this).parents("form").find(".filelist.queue")
				.find("li[data-index=" + file.index + "]").addClass("error")
				.find(".progress").text(response.trim());
		}
		else {
			var $target = $(this).parents("form").find(".filelist.queue").find("li[data-index=" + file.index + "]");
			$target.find(".file").text(file.name);
			$target.find(".progress").remove();
			$target.find(".cancel").remove();
			$target.appendTo($(this).parents("form").find(".filelist.complete"));

            DupPro.Tools.lastFileUploaded = file.name;
		}
	};

	DupPro.Tools.onFileError = function (e, file, error)
	{
        DupPro.Tools.upDebug(DPRO_DEBUG, "File Error");
		var index = $(this).parents("li").data("index");
		DPRO_UPLOADER.upload("abort", parseInt(index, 10));
		DupPro.Tools.toggleStep(12);       
	};

	DupPro.Tools.onChunkError = function (e, file, error)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "Chunk Error Toggle step 4");
	};
    
	DupPro.Tools.onComplete = function(e)
	{
		DupPro.Tools.upDebug(DPRO_DEBUG, "Complete");
		if (DPRO_UPLOAD_STEP < 10) {
			DupPro.Tools.toggleStep(3);
		}
	};

	//Empty Handles
	DupPro.Tools.onCancelAll = function(e) { DupPro.Tools.upDebug(DPRO_DEBUG, "Cancel All");}
	DupPro.Tools.onChunkStart = function (e, file) {DupPro.Tools.upDebug(DPRO_DEBUG, "Chunk Start");}
	DupPro.Tools.onChunkProgress = function (e, file, percent) {DupPro.Tools.upDebug(DPRO_DEBUG, "Chunk Progress");}
	DupPro.Tools.onChunkComplete = function (e, file, response){DupPro.Tools.upDebug(DPRO_DEBUG, "Chunk Complete");}
	DupPro.Tools.upDebug = function (enable, object) { if (enable) console.log(object);}

    DupPro.Tools.LaunchInstaller = function()
    {
        if(DupPro.Tools.lastFileUploaded != null) {            
            // RSR TODO: figure out url based on what was just uploaded
            var installerName = DupPro.Tools.lastFileUploaded.replace(/\.[^/.]+$/, "");
            installerName = installerName.replace("_archive", "_installer");            
            installerName = installerName + ".php";

            var installerUrl = "<?php echo DUPLICATOR_PRO_IMPORTS_URL; ?>" + "/" + installerName;
            alert("Launching " + installerUrl);
//            var win = window.open(installerUrl, 'installer');

  //          win.focus();
        } else {
            DupPro.Tools.upDebug(DPRO_DEBUG, "Trying to launch installer when last file uploaded is null!");
        }
    }

	//Init
	$('#dpro-daf-upload-btn').click(function() {$('.fs-upload-target"').trigger('click');});
    $('#dpro-launch-btn').click(DupPro.Tools.LaunchInstaller);

	DupPro.Tools.initUploader();


 });
</script>



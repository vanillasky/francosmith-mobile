
function getPrevImg(fileName, imgUrl){
	if(fileName.indexOf('.')>0){
		_fileExt = fileName.substring(fileName.lastIndexOf('.'),fileName.length).toLowerCase();
		fileExt = _fileExt.replace('.','');
	}

	switch (fileExt)
	{
	case 'gif':case 'jpg':case 'bmp':case 'png':case 'jpeg':
		previewUrl = imgUrl;
		break;
	case 'doc':case'docx':
		fileName = 'icon_file_doc.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
		break;
	case 'ppt':case'pptx':
		fileName = 'icon_file_ppt.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
		break;
	case 'pdf':
		fileName = 'icon_file_pdf.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
		break;
	case 'xls':case'xlsx':
		fileName = 'icon_file_xls.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
		break;
	case 'txt':
		fileName = 'icon_file_txt.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
		break;
	default :
		fileName = 'icon_file_etc.png';
		previewUrl = rootDir+'/data/skin_mobileV2/'+mobileSkin+'/common/img/new/'+fileName;
	}

	return previewUrl;
}

var chkForm2 = function(form)
{
	if(jQuery('.secret_button').hasClass('on')){
		form.secret.value='o';
	}
	else{
		form.secret.value='';
	}

	form.subject.value = htmlspecialchars(form.subject.value);
	form.contents.value = htmlspecialchars(form.contents.value);

	if(jQuery('input[name=notice]').length>0){
		if(jQuery('.notice_button').hasClass('on')){
			form.notice.value='o';
		}
		else{
			form.notice.value='';
		}
	}

	if (form.subject.value.trim().length < 1) {
		alert("제목을 입력해주세요");
		return false;
	}

	if (form.contents.value.trim().length < 1) {
		alert("내용을 입력해주세요");
		return false;
	}

	if (chkForm(form) === false) {
		return false;
	}

	return true;
};

function initFileUpload(){
	var maxUploadFile = maxFileNumber;
	maxUploadFile = maxUploadFile ? maxUploadFile : 0;
	if(mode == 'modify'){
		for(i=0 ; i<prvFilePath.length ; i++){
			prevImg = getPrevImg(prvFileName[i],prvFilePath[i]);
			$newItem = jQuery("#board-attach li.item.template").clone();
			jQuery("#board-attach li.item.template").before($newItem);
			$newItem.removeClass("template");
			$prvFileFace = $newItem.find('.file-face');
			prewFileFace = $prvFileFace[0];
			$prvFileFace.addClass("preview").css({
				"background-image" : "url('"+prevImg+"')"
			});
		}

		jQuery("#board-attach li.item:not(.template)").each(function(index){
			this.onclick = function()
			{
				if (confirm("첨부된 파일을 삭제하시겠습니까?")) {
					this.remove();
				}
				document.getElementsByName('del_file['+index+']')[0].value='on';
			};
		});
	}

	jQuery("#board-attach li.item.template button.file-face").live("click", function(){

		var templateContainer = this.parentNode;
		var $container = jQuery(templateContainer);
		var $fileFace = $container.find("button.file-face"), fileFace = $fileFace[0];
		var $fileHidden = $container.find("input.file-hidden"), fileHidden = $fileHidden[0];
		if (jQuery("#board-attach li.item:not(.template) input.file-hidden").length >= maxUploadFile) {
			alert("첨부파일은 최대 " + maxUploadFile.toString() + "개 까지 업로드 가능합니다.");
			return false;
		}
		else {
			fileHidden.onchange = function()
			{
				var fileReader = new FileReader();
				fileName = this.files[0].name;

				fileReader.readAsDataURL(this.files[0]);
				fileReader.onload = function()
				{
					previewUrl = getPrevImg(fileName, this.result);
					jQuery(templateContainer.cloneNode(true)).appendTo(jQuery("#board-attach"));
					$container.removeClass("template");

					$fileFace.addClass("preview").css({
						"background-image" : "url('" + previewUrl + "')",
						"background-position" : "center",
						"background-size" : "cover",
						"background-repeat" : "no-repeat",
						"-webkit-background-size" : "cover",
						"-moz-background-size" : "cover",
						"-o-background-size" : "cover"
					});

					fileFace.onclick = function()
					{
						if (confirm("첨부된 파일을 삭제하시겠습니까?")) {
							$container.remove();
						}
					};
				};

				fileReader.onerror = function()
				{
					alert("이미지 로드중 에러가 발생하였습니다.");
				};
			};
			$fileHidden.trigger("click");
		}
	});
}

function htmlspecialchars (string) {
	return string.replace(/&/g, "&amp;").replace(/\"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function viewContent(contentUrl,isSecret,m_no,_member){
	if(ici_admin!=''){
		location.href=contentUrl;
		return true;
	}

	if (isSecret == 'o')
	{
		if (sess_no == '')	//비회원이 회원이 쓴글을 보려고하면
		{
			if(parseInt(m_no)>0 && parseInt(_member) != 0){
				alert('비밀글입니다.\n접근 권한이 없습니다	');
				return false;
			}
		}
		else{
			if(parseInt(m_no)<1){	//비회원 글일때
				alert('비밀글입니다.\n접근 권한이 없습니다	');
				return false;
			}
			else if(parseInt(sess_no) != parseInt(m_no) && parseInt(sess_no) != parseInt(_member)){	//본인이 작성한 글이 아닐때
				alert('비밀글입니다.\n접근 권한이 없습니다	');
				return false;
			}
		}
	}

	location.href=contentUrl;
}
var arr_swipe_obj = []; 

$(document).ready(function() {
	
	/* 상품 스크롤형 리스트 액션 */
	$(".swipe_gs").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			    auto: 0,
				callback: function(event, index, elem) {
					// do something cool
					$("#"+obj.id+"-page").text(index+1);	
				}
		});
		arr_swipe_obj[obj.id] = objSwipe;		
	});
	
	/* 이미지 스크롤형 리스트 액션 */
	$(".swipe_is").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			    auto: 0,
				callback: function(event, index, elem) {
					// do something cool
					$("#"+obj.id+"-page").text(index+1);	
				}
		});
		arr_swipe_obj[obj.id] = objSwipe;	
	});	
	
	/* 매거진형 리스트 액션 */
	$(".swipe_mg").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			    auto: 0,
				callback: function(event, index, elem) {
					// do something cool
					$("#"+obj.id+"-page").text(index+1);	
				}
		});
		arr_swipe_obj[obj.id] = objSwipe;	
	});	
	
	/* TAB형 리스트 액션 */
	// TAB형은 로딩타임에 실시간으로 만들어지므로,  다른곳에서 처리

	$(".swipe_tab").each(function(i, obj) {
		var str_id = obj.id.toString(); 
		var str_list_content_id = str_id.replace("swipe_tab", "tabcontent");
		
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			    auto: 0,
				callback: function(event, index, elem) {
					// do something cool
					$("#"+obj.id+"-page").text(index+1);	
				}
		});
		
		arr_swipe_obj[obj.id] = objSwipe;
	});
	
	/* 매거진형 리스트 액션 */
	$(".swipe_ban").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			    auto: 3000,
				callback: function(event, index, elem) {
					var aitem = elem.id.toString().split("-"); 
					var list_id = aitem[0]+"-"+aitem[1];
					$("[id^="+list_id+"-page-box]").removeClass("now_page");
					$("#"+list_id+"-page-box-"+(index+1)).addClass("now_page");
				}
		});
		arr_swipe_obj[obj.id] = objSwipe;	
	});

	/* 상품상세 이미지 */
	$(".swipe_detail").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {			
				startSlide: 0,
				speed: 400,
			  auto: 0,
				callback: function(event, index, elem) {
					var aitem = elem.id.toString().split("-"); 
					var list_id = aitem[0]+"-"+aitem[1];
					$("[id^="+list_id+"-page-box]").removeClass("now_page");
					$("#"+list_id+"-page-box-"+(index+1)).addClass("now_page");
				}
		});
		arr_swipe_obj[obj.id] = objSwipe;	
	});

	/* 탭형 리스트 액션 */
/*
	$(".list_tabcontent").live('swipeleft', function(e) {
		var list_id = this.id;

		var n_page = parseInt($("#"+list_id+"-page").text());
		var total_page = parseInt($("#"+list_id+"-tpage").text());
		
		if(n_page >= total_page) {
			return;
		}
		else {
			var next_page = n_page + 1;

			$("#"+list_id+"-"+ next_page.toString()).css("margin-left","20%");
			$("#"+list_id+"-"+ next_page.toString()).removeClass("hidden");
			$("#"+list_id+"-"+ n_page.toString()).animate({"margin-left":"-20%"},100);
			$("#"+list_id+"-"+ next_page.toString()).animate({"margin-left":"0%"},100);
			$("#"+list_id+"-"+ n_page.toString()).addClass("hidden");
			$("#"+list_id+"-page").text(next_page);
		}
	});

	$(".list_tabcontent").live('swiperight', function(e) {
		var list_id = this.id;
		var n_page = parseInt($("#"+list_id+"-page").text());
		
		if(n_page == 1) {
			return;
		}
		else {
			var prev_page = n_page - 1;
			$("#"+list_id+"-"+ prev_page.toString()).css("margin-left","-20%");
			$("#"+list_id+"-"+ prev_page.toString()).removeClass("hidden");
			$("#"+list_id+"-"+ n_page.toString()).animate({"margin-left":"20%"},100);
			$("#"+list_id+"-"+ prev_page.toString()).animate({"margin-left":"0%"},100);
			$("#"+list_id+"-"+ n_page.toString()).addClass("hidden");
			$("#"+list_id+"-page").text(prev_page);
		}
	});
*/
	if (typeof checkVersion == 'undefined') {
		if(navigator.userAgent.match(/Android/i)){
			window.scrollTo(0,1);
		}
	}
	if (("standalone" in window.navigator) && window.navigator.standalone){ 
		$('body').css("padding-top", "19px");
	}

});

function scroll_btn(obj_id, direct) {
	if (arr_swipe_obj[obj_id] != null && arr_swipe_obj[obj_id] != undefined) {
		if (direct == 'left') {
			arr_swipe_obj[obj_id].prev(); 
		} else {
			arr_swipe_obj[obj_id].next(); 
		}
	}
	return;	
}

function scroll_cf_btn(obj_id, direct) {
	var action_nm = ""; 
	if (direct == 'left') {
		action_nm = 'swiperight'; 
	} else {
		action_nm = 'swipeleft'; 
	}
	$("#"+obj_id).trigger(action_nm); 
}

function tab_click(obj_id, seq, tabidx, tabcnt) {

	if (typeof checkVersion == 'undefined') {
		$("[id^=title-tabcontent-"+seq+"]").removeClass("title_active");
		$("[id=title-"+obj_id+"]").addClass("title_active");

		var sub_html = ""; 
		sub_html = createHtmlSubTab(seq, tabidx); 
		$("#tabcontent-"+seq.toString()).html(sub_html);

		$(".swipe_tab").each(function(i, obj) {
			var str_id = obj.id.toString(); 
			var str_list_content_id = str_id.replace("swipe_tab", "tabcontent");

			var objSwipe = new Swipe(obj, {			
					startSlide: 0,
					speed: 400,
					auto: 0,
					callback: function(event, index, elem) {
						// do something cool
						$("#"+obj.id+"-page").text(index+1);	
					}
			});
			var idx = 0; 
			arr_swipe_obj[obj.id] = objSwipe;		

		});
	} else {
		// @qnibus 2015-07 탭형(tpl_05.html) 클릭용
		$("[id^=title-tabcontent-"+seq+"-"+"]").removeClass("title_active");
		$("[id=title-"+obj_id+"]").addClass("title_active");
		$("[id=title-"+obj_id+"]").parent().parent().find(".list_tabcontent").each(function(i, obj) { 
			if (i == tabidx) {
				$(this).show();
				arr_swipe_obj[$(obj).find('.swipe_tab').attr('id')].setup();// Swipe 재세팅
			}
			else $(this).hide();
		});
	}
	setGoodsImageSoldoutMask(); // 품절처리 내역 출력 (기존 버그 수정)
}

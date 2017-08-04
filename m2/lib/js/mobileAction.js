var arr_swipe_obj = []; 
var more_btn_cnt = [];

/* 상품 스크롤형 리스트 액션 정의 */
function set_swipe_gs(obj) {
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
}

/* 이미지 스크롤형 리스트 액션 정의 */
function set_swipe_is(obj) {
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
}

/* 매거진형 리스트 액션 정의 */
function set_swipe_mg(obj) {
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
}

/* TAB형 리스트 액션 정의 */
function set_swipe_tab(obj) {
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
}

/* 이벤트형 리스트 액션 정의 */
function set_swipe_ban(obj, startSlide) {
	var objSwipe = new Swipe(obj, {
			startSlide: (startSlide ? startSlide : 0),
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
}

$(document).ready(function() {

	/* 상품 스크롤형 리스트 액션 */
	$(".swipe_gs").each(function(i, obj) {
		set_swipe_gs(obj);
	});

	/* 이미지 스크롤형 리스트 액션 */
	$(".swipe_is").each(function(i, obj) {
		set_swipe_is(obj);
	});

	/* 매거진형 리스트 액션 */
	$(".swipe_mg").each(function(i, obj) {
		set_swipe_mg(obj);
	});

	/* TAB형 리스트 액션 */
	$(".swipe_tab").each(function(i, obj) {
		set_swipe_tab(obj);
	});
	
	/* 이벤트형 리스트 액션 */
	$(".swipe_ban").each(function(i, obj) {
		set_swipe_ban(obj);
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

	/* 상품 더보기형 리스트 액션 */
	$(".swipe_more").each(function(i, obj) {
		var objSwipe = new Swipe(obj, {});
	});

	if (typeof checkVersion == 'undefined') {
		if(navigator.userAgent.match(/Android/i)){
			window.scrollTo(0,1);
		}
	}
	if (("standalone" in window.navigator) && window.navigator.standalone){ 
		$('body').css("padding-top", "19px");
	}

});

function reset_swipe(obj_id, direct, sThis) {
	// 세션 스토리지 있는 경우
	if (loadSession('main') != 'main' && loadSession('main') != 'init') {
		if (arr_swipe_obj[obj_id].container != $("#" + obj_id)[0]) { // 다름
			var obj = $("#" + obj_id)[0];
			sThis.stop();

			/* 상품 스크롤형 리스트 액션 */
			if ($(obj).hasClass( "swipe_gs" ) === true) {
				set_swipe_gs(obj);
			}
			/* 이미지 스크롤형 리스트 액션 */
			else if ($(obj).hasClass( "swipe_is" ) === true) {
				set_swipe_is(obj);
			}
			/* 매거진형 리스트 액션 */
			else if ($(obj).hasClass( "swipe_mg" ) === true) {
				set_swipe_mg(obj);
			}
			/* TAB형 리스트 액션 */
			else if ($(obj).hasClass( "swipe_tab" ) === true) {
				set_swipe_tab(obj);
			}
			/* 이벤트형 리스트 액션 */
			else if ($(obj).hasClass( "swipe_ban" ) === true) {
				var no = obj.id.replace('swipe_ban-','');
				var startSlide = 0;
				if ($("[id^=banner-"+no+"-page-box].now_page").length == 1) {
					var idStr = $("[id^=banner-"+no+"-page-box].now_page")[0].id;
					startSlide = idStr.replace("banner-"+no+"-page-box-", '') - 1;
				}
				set_swipe_ban(obj, startSlide);
				return false;
			}

			if ($("#"+obj.id+"-page").text() != '') {
				arr_swipe_obj[obj_id].index = $("#"+obj.id+"-page").text() - 1;
				if (direct == 'prev') {
					arr_swipe_obj[obj_id].prev();
				} else {
					arr_swipe_obj[obj_id].next();
				}
			}
			return false;
		}
	}
	return true;
}

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

// 상품 더보기형 더보기 버튼
function more_btn(design_no,page_cnt) {
	if (!more_btn_cnt[design_no]) {
		if ($("[id^=more-"+design_no+"-].hidden").length > 0) {
			var idStr = $("[id^=more-"+design_no+"-].hidden")[0].id;
			more_btn_cnt[design_no] = idStr.replace("more-"+design_no+"-", '');
		}
		else {
			more_btn_cnt[design_no] = 2;
		}
	}
	$("#more-"+design_no+"-"+more_btn_cnt[design_no]).removeClass("hidden");

	more_btn_cnt[design_no]++;

	if (more_btn_cnt[design_no] > page_cnt) {
		$("#more_btn_"+design_no).addClass("hidden");
	}
	setGoodsImageSoldoutMask();
}
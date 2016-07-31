var mobile_root = getMobileHomepath();

$(document).ready(function() {

	if (typeof checkVersion == 'undefined') {
		if (!document.location.hash) {
			if (navigator.userAgent.indexOf('iPhone') != -1 || navigator.userAgent.indexOf('iPad') != -1) {
				addEventListener("load", function() {
					setTimeout(hideURLbar, 0);
				}, false);
			}

			if (navigator.userAgent.indexOf('Linux') != -1) {
				addEventListener("load", function() {
					setTimeout(hideURLbar, 0);
				}, false);
			}
		}
	}

	$("[id$=btn]").live('onClick', function(e) {
		$(e.target).addClass('no_focus');
	});

	$("[id$=btn]").live('touchstart', function(e) {
		$(e.target).addClass('active');
	});

	$("[id$=btn]").live('touchend', function(e) {
		$(e.target).removeClass('active');
	});

	$("#more-view-btn").click(function(event){
		$("#more-view-menu").toggle();
		event.stopPropagation();
	});

	$(document).click(function(){
		$("#more-view-menu").css("display", "none");
	});

	$("#more-view-menu .goods-review").click(function(){
		document.location.href = "/" + getMobileHomepath() + "/goods/review.php";
	});

	$("#more-view-menu .goods-qna").click(function(){
		document.location.href = "/" + getMobileHomepath() + "/goods/goods_qna_list.php?isAll=Y";
	});

	$("#more-view-menu .community").click(function(){
		document.location.href = "/" + getMobileHomepath() + "/board/index.php";
	});

	$("#more-view-menu .wishlist").click(function(){
		document.location.href="/" + getMobileHomepath() + "/myp/wishlist.php";
	});

	$(window).bind("orientationchange", function() {
		var $viewport = $('head').children('meta[name="viewport"]');

			if(window.orientation == 90 || window.orientation == -90 || window.orientation == 270) {
				//landscape
				$viewport.attr('content', 'height=device-width,width=device-height,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=yes');
			} else {
				//portrait
				$viewport.attr('content', 'height=device-height,width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=yes');
			}
	});

	if (typeof checkVersion == 'undefined') {
		if(location.pathname.indexOf("/goods/list") != -1) {
			$("input[name='item_cnt']").each(function(i, obj) {
				obj.value = 0;
			});
			getGoodsListData();
		}
	}

	// @qnibus 2015-07 슬라이드 메뉴 작동
	if($('.gd-flipcover-btn').length)flipOffCanvas();

	// @qnibus 2015-07 슬라이드 메뉴 (_off_canvas.htm)
	if($('.gd-gnb').length)offCanvasGnb(); // gnb btn link
});


function goUrl(url) {

	$.mobile.changePage(url, "fade");
}

function hideURLbar() {
	window.scrollTo(0, 1);
}

var view_type_gallery1 = "Y";
var view_type_gallery2 = "Y";
function getGoodsListData() {

	var category = $("[name=category]").val();


	var kw = $("[name=kw]").val();

	var view_type = $.cookie('goods_view_type');
	var sort_type = $.cookie('sort_type');

	if(sort_type == "" || sort_type == "undefined") {
		sort_type = "sort";
	}
	var item_cnt = 0;

	var data_param;
	data_param = "mode=get_goods";

	if(kw) {
		data_param += "&kw=" + kw;
	}

	if(view_type == 'gallery') {
		item_cnt = $(".goods-item").length;
		data_param += "&view_type=" + view_type
	}
	else if(view_type == 'gallery1') {
		item_cnt = $(".goods-gallery1-item").length;
		data_param += "&view_type=" + view_type
	}
	else if(view_type == 'gallery2') {
		item_cnt = $(".goods-gallery2-item").length;
		data_param += "&view_type=" + view_type
	}
	else if(view_type == 'list') {
		item_cnt = $(".goods-list-item").length;
		data_param += "&view_type=" + view_type
	}
	else {
		item_cnt = $("[name=item_cnt]").val();
	}

	data_param += "&category=" + category;
	data_param += "&sort_type=" + sort_type;

	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res == null) {
					$(".more-btn").hide();
				}
				makeGoodsList(res.goods_data, kw, category);
				setGoodsImageSoldoutMask();
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}

}

function makeGoodsList(goods_data, kw, category) {

	if($(".goods-sort").length > 0) {

		$(".goods_item_list").hide();

		var goods_src = "";

		if(kw) {
			goods_src = '../goods/view.php?kw=' + kw;
		}
		else {
			goods_src = '../goods/view.php?category=' + category;
		}


		var view_type = $.cookie('goods_view_type');

		if(view_type == 'gallery') {

			if(goods_data.length > 0) {

				var add_html = "";

				for(var i=0; i<goods_data.length; i++) {

					if((i+1) % 3 == 1) {
						add_html += '<div class="goods-row">';
					}

					add_html += '<div class="goods-item"';
					if((i+1) % 3 == 1 || (i+1) % 3 == 2) {
						add_html += ' style="margin-right:5%;" ';
					}
					add_html += '>';
					var speachDescription = "";
					if (goods_data[i].tts_url) {
						speachDescription = '<div class="goods-speach-description" style="display: none;">'
								  + '<span class="speach-description-play" data-src="' + goods_data[i].tts_url + '">재생</span>'
								  + '<span class="speach-description-timer"></span>'
								  + '</div>';
					}
					var couponImage = "";
					if (goods_data[i].coupon_discount != "0" && goods_data[i].coupon_discount != "" && typeof goods_data[i].coupon_discount != "undefined") {
						couponImage = '<div class="goods-coupon"></div>';
					}
					add_html += '<div class="goods-img">' + couponImage + '<a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].img_html+'</a>' + speachDescription + '</div>';

					add_html += '<div class="goods-nm"><a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].goodsnm+'</a></div>';
					
					if(goods_data[i].strprice == null || goods_data[i].strprice == ""){
						if (goods_data[i].consumer != null && goods_data[i].consumer.trim().length != "" && goods_data[i].consumer != 0) {
							add_html += '<div class="goods-consumer" style="display: none;"><strike>' + comma(goods_data[i].consumer) + '원</strike>↓</div>';
						}
					}
					if (goods_data[i].strprice != null && goods_data[i].strprice.trim().length > 0) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '">' + goods_data[i].strprice + '</a></div>';
					}
					else if (goods_data[i].price != null) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '"><span class="red">' + comma(goods_data[i].price) + '원</span></a></div>';
					}

					if (goods_data[i].special_discount != "0" && goods_data[i].special_discount != "" && typeof goods_data[i].special_discount != "undefined") {
						var dcMark = '';
						if (goods_data[i].special_discount.indexOf("원") > -1) dcMark = '원';
						else if (goods_data[i].special_discount.indexOf("%") > -1) dcMark = '%';
						add_html += '<div class="goods-discount" style=\"display: none;\"> ' + comma(goods_data[i].special_discount) + dcMark + ' 할인</div>';
					}
					if (goods_data[i].coupon != null) {
						add_html += '<div class="goods-coupon-price" style="display: none;">'+comma(goods_data[i].coupon)+'원 <div class="goods-coupon-icon"></div></div>';
					}
					//add_html += '<div class="goods-btn"><div class="del-btn" onClick="javascript:delGoods(\''+goods_data[i].goodsno+'\');"></div><div class="cart-order-btn"></div></div>';
					add_html += '</div>';
					if((i+1) % 3 ==0 || (i+1) == goods_data.length) {
						add_html += '<div style="width:100%; height:0px; clear:both;"></div>';
						add_html += '</div>';
					}
				}
			}

			if(goods_data.length < 9) {
				$(".more-btn").hide();
			}


			$(".goods-content").append(add_html);
		}
		else if(view_type == 'gallery1') {

			if(goods_data.length > 0) {

				var add_html = "";

				for(var i=0; i<goods_data.length; i++) {

					add_html += '<div class="goods-row">';
					add_html += '<div class="goods-gallery1-item">';
					var speachDescription = "";
					if (goods_data[i].tts_url) {
						speachDescription = '<div class="goods-speach-description" style="display: none;">'
								  + '<span class="speach-description-play" data-src="' + goods_data[i].tts_url + '">재생</span>'
								  + '<span class="speach-description-timer"></span>'
								  + '</div>';
					}
					var couponImage = "";
					if (goods_data[i].coupon_discount != "0" && goods_data[i].coupon_discount != "" && typeof goods_data[i].coupon_discount != "undefined") {
						couponImage = '<div class="goods-coupon"></div>';
					}
					add_html += '<div class="goods-img">' + couponImage + '<a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].img_html_m+'</a>' + speachDescription + '</div>';
					add_html += '<div class="goods-nm"><a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].goodsnm+'</a></div>';
					if (goods_data[i].strprice != null && goods_data[i].strprice.trim().length > 0) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '">' + goods_data[i].strprice + '</a></div>';
					}
					else if (goods_data[i].price != null) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '">' + comma(goods_data[i].price) + '원</a></div>';
					}

					if (goods_data[i].special_discount != "0" && goods_data[i].special_discount != "" && typeof goods_data[i].special_discount != "undefined") {
						var dcMark = '';
						if (goods_data[i].special_discount.indexOf("원") > -1) dcMark = '원';
						else if (goods_data[i].special_discount.indexOf("%") > -1) dcMark = '%';
						add_html += '<div class="goods-discount" style=\"display: none;\"> ' + comma(goods_data[i].special_discount) + dcMark + ' 할인</div>';
					}
					//add_html += '<div class="goods-btn"><div class="del-btn" onClick="javascript:delGoods(\''+goods_data[i].goodsno+'\');"></div><div class="cart-order-btn"></div></div>';
					add_html += '</div>';
					add_html += '<div style="width:100%; height:0px; clear:both;"></div>';
					add_html += '</div>';
				}
			}

			if(goods_data.length < 10) {
				$(".more-btn").hide();
			}

			$(".goods-content").append(add_html);
		}
		else if(view_type == 'gallery2') {

			if(goods_data.length > 0) {

				var add_html = "";

				for(var i=0; i<goods_data.length; i++) {

					if((i+1) % 2 == 1) {
						add_html += '<div class="goods-row">';
					}

					add_html += '<div class="goods-gallery2-item"';
					if((i+1) % 2 == 1 || (i+1) % 2 == 2) {
						add_html += ' style="margin-right:4%;" ';
					}
					add_html += '>';
					var speachDescription = "";
					if (goods_data[i].tts_url) {
						speachDescription = '<div class="goods-speach-description" style="display: none;">'
								  + '<span class="speach-description-play" data-src="' + goods_data[i].tts_url + '">재생</span>'
								  + '<span class="speach-description-timer"></span>'
								  + '</div>';
					}
					var couponImage = "";
					if (goods_data[i].coupon_discount != "0" && goods_data[i].coupon_discount != "" && typeof goods_data[i].coupon_discount != "undefined") {
						couponImage = '<div class="goods-coupon"></div>';
					}
					add_html += '<div class="goods-img">' + couponImage + '<a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].img_html_i+'</a>' + speachDescription + '</div>';
					add_html += '<div class="goods-nm"><a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].goodsnm+'</a></div>';
					if (goods_data[i].strprice != null && goods_data[i].strprice.trim().length > 0) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '">' + goods_data[i].strprice + '</a></div>';
					}
					else if (goods_data[i].price != null) {
						add_html += '<div class="goods-price"><a href="' + goods_src + '&goodsno=' + goods_data[i].goodsno + '">' + comma(goods_data[i].price) + '원</a></div>';
					}

					if (goods_data[i].special_discount != "0" && goods_data[i].special_discount != "" && typeof goods_data[i].special_discount != "undefined") {
						var dcMark = '';
						if (goods_data[i].special_discount.indexOf("원") > -1) dcMark = '원';
						else if (goods_data[i].special_discount.indexOf("%") > -1) dcMark = '%';
						add_html += '<div class="goods-discount" style=\"display: none;\"> ' + comma(goods_data[i].special_discount) + dcMark + ' 할인</div>';
					}
					//add_html += '<div class="goods-btn"><div class="del-btn" onClick="javascript:delGoods(\''+goods_data[i].goodsno+'\');"></div><div class="cart-order-btn"></div></div>';
					add_html += '</div>';
					if((i+1) % 2 ==0 || (i+1) == goods_data.length) {
						add_html += '<div style="width:100%; height:0px; clear:both;"></div>';
						add_html += '</div>';
					}
				}
			}

			if(goods_data.length < 10) {
				$(".more-btn").hide();
			}

			$(".goods-content").append(add_html);
		}
		else {

			if(goods_data.length > 0) {

				var add_html = "";

				// 네이버 마일리지 적립률 확인
				try {
					var
					naverMileageBaseAccumRate = wcs.getBaseAccumRate().toString().trim(),
					naverMileageAddAccumRate = wcs.getAddAccumRate().toString().trim(),
					naverMileageAccumRate = (parseFloat(naverMileageBaseAccumRate) + parseFloat(naverMileageAddAccumRate)).toString();
				}
				catch (exception) {

				}

				for(var i=0; i<goods_data.length; i++) {

					if((i+1) % 2 == 0) {
						add_html += '<div class="goods-list-item goods-list-item-gray">';
					}
					else {
						add_html += '<div class="goods-list-item ">';
					}

					var speachDescription = "";
					if (goods_data[i].tts_url) {
						speachDescription = '<div class="goods-speach-description" style="display: none;">'
							          + '<span class="speach-description-play" data-src="' + goods_data[i].tts_url + '">재생</span>'
								  + '<span class="speach-description-timer"></span>'
							          + '</div>';
					}

					add_html += '<div class="goods-list-img"><a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].img_html+'</a>' + speachDescription + '</div>';
					if (goods_data[i].coupon_discount != "0" && goods_data[i].coupon_discount != "" && typeof goods_data[i].coupon_discount != "undefined") {
						add_html += '<div class="goods-list-coupon"></div>';
					}
					add_html += '	<div class="goods-list-info"><a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">';
					add_html += '		<div class="goods-nm">'+goods_data[i].goodsnm+'</div>';

					if (goods_data[i].strprice != null && goods_data[i].strprice.trim().length > 0) {
						add_html += '<div class="goods-price">상품가격 : <span class="red">' + goods_data[i].strprice + '</span></div>';
						if (goods_data[i].reserve != "0" && goods_data[i].reserve != "" && goods_data[i].reserve != "undefined") {
							add_html += '		<div class="goods-reserve">적립금 : ' + comma(goods_data[i].reserve) + '원</div>';
						}
					}
					else if (goods_data[i].price != "" && goods_data[i].price != null) {
						var specialDiscount = "";
						if (goods_data[i].special_discount != "0" && goods_data[i].special_discount != "" && typeof goods_data[i].special_discount != "undefined") {
							var dcMark = '';
							if (goods_data[i].special_discount.indexOf("원") > -1) dcMark = '원';
							else if (goods_data[i].special_discount.indexOf("%") > -1) dcMark = '%';
							specialDiscount = ' <span class="goods-discount"  style=\"display: none;\">(' + comma(goods_data[i].special_discount) + dcMark + ' 할인)</span>';
						}

						var consumerDiscount = "";
						if(goods_data[i].strprice == null || goods_data[i].strprice == ""){
							if (goods_data[i].consumer != null && goods_data[i].consumer.trim().length != "" && goods_data[i].consumer != 0 ) {
								add_html += '<div class="goods-consumer" style="display: none;">상품가격 : <strike>' + comma(goods_data[i].consumer) + '원</strike>↓</div>';
								add_html += '		<div class="goods-price">' + consumerDiscount + '<span class="red">' + comma(goods_data[i].price) + '원</span>' + specialDiscount + '</div>';
							}
							else
								add_html += '		<div class="goods-price">' + consumerDiscount + '<span class="goods-consumer" style="display: none;"> 상품가격 : </span> <span class="red" style="margin-left:0px;">' + comma(goods_data[i].price) + '원</span>' + specialDiscount + '</div>';
						}
						else if (goods_data[i].strprice != null && goods_data[i].strprice.trim().length > 0) {
							add_html += '		<div class="goods-price">' + consumerDiscount + '<span class="goods-consumer" style="display: none;"> 상품가격 : </span> <span class="red" style="margin-left:0px;">' + comma(goods_data[i].price) + '원</span>' + specialDiscount + '</div>';
						}

						if (goods_data[i].coupon != null) {
							add_html += '<div class="goods-coupon-price" style="display: none;">쿠폰할인 : <span class="red">'+comma(goods_data[i].coupon)+'원</span> <div class="goods-coupon-icon"></div></div>';
						}

						if (goods_data[i].reserve != "0" && goods_data[i].reserve != "" && goods_data[i].reserve != "undefined") {

							add_html += '		<div class="goods-reserve">적립금 : ' + comma(goods_data[i].reserve) + '원</div>';
						}
						if (goods_data[i].NaverMileageAccum) {
							var naver_mileage_accum_rate = "<span class=\"naver-mileage-accum-rate\" style=\"font-weight: bold; color: #1ec228;\">" + naverMileageAccumRate + "%</span>";
							naver_mileage_accum_rate = naver_mileage_accum_rate + " 적립 <img src=\"/shop/proc/naver_mileage/images/n_mileage_on.png\"/>";
							add_html += "<div class=\"goods-nvmileage naver-mileage-accum\">네이버 마일리지 : " + naver_mileage_accum_rate + "</div>";
						}
					}
					else {
						add_html += '		<div class="goods-price"></div>';
					}

					add_html += '	</a></div>';
					add_html += '	<a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'"><div class="goods-list-arrow"></div></a>';
					add_html += '</div>';
				}

			}


			if(goods_data.length < 10) {

				$(".more-btn").hide();
			}

			$(".goods-content").append(add_html);
		}

		// 읽어주는 상품설명
		$(".goods-content").find(".speach-description-play").unbind("click").bind("click", function(){
			var $player = $("#speach-description-player");
			if (!$player.length) return false;
			$player.trigger("$play", [$(this).parent()]);
		});

	}
	else {
		if($("#goods-item-list").length > 0) {

			var item_cnt = $("[name=item_cnt]").val();

			if(item_cnt == 0 && goods_data.length <1) {
				var no_goods_html = "";
				no_goods_html = "<li class=\"more\">검색 결과가 없습니다</li>";
				$("#goods-item-list").append(no_goods_html);
			}
			else {

				$(".more").addClass('hidden');

				var j = 0;
				for(var i=0; i<goods_data.length; i++) {

					var goods_html ="";
					goods_html = "<li><dl><dt class=\"hidden\">상품이미지</dt><dd class=\"gl_img\"><a href=\"../goods/view.php?goodsno=" + goods_data[i].goodsno + "\">" + goods_data[i].img_html +"</a></dd>";
					if (goods_data[i].coupon_discount != "0" && goods_data[i].coupon_discount != "" && typeof goods_data[i].coupon_discount != "undefined") {
						goods_html += "<dt class=\"hidden\">쿠폰이미지</dt>";
						goods_html += '<dd class="gl_coupon"></dd>';
					}
					goods_html += "<dt class=\"hidden\">상품정보</dt>";
					goods_html += "<dd class=\"gl_goods\">";
					goods_html += "<div class=\"hidden\">상품명</div>";
					goods_html += "<div class=\"gl_name\"><a href=\"../goods/view.php?goodsno="+goods_data[i].goodsno+"\">"+goods_data[i].goodsnm+"</a></div>";

					if (goods_data[i].price != "") {
						var specialDiscount = "";
						if (goods_data[i].special_discount != "0" && goods_data[i].special_discount != "" && typeof goods_data[i].special_discount != "undefined") {
							specialDiscount = ' <span class="goods-discount"  style=\"display: none;\">(' + goods_data[i].special_discount + ' 할인)</span>';
						}
						goods_html += "<div class=\"gl_price\">상품가격 : <span class=\"r_price\">"+goods_data[i].price+"원</span>" + specialDiscount + "</div>";
						goods_html += "<div class=\"gl_reserve\">적립금 : " + goods_data[i].reserve + "원</div>";
						if (goods_data[i].NaverMileageAccum) {
							var naver_mileage_accum_rate = "<span class=\"naver-mileage-accum-rate\" style=\"font-weight: bold; color: #1ec228;\"></span>";
							naver_mileage_accum_rate = naver_mileage_accum_rate + " 적립 <img src=\"/shop/proc/naver_mileage/images/n_mileage_on.png\"/>";
							goods_html += "<div class=\"gl_naver_mileage goods-nvmileage\">네이버 마일리지 : " + naver_mileage_accum_rate + "</div>";
						}
					}
					else {
						goods_html += "<div class=\"gl_price\"></div>";
					}

					goods_html += "<dt class=\"gl_arrow\" onClick=\"javascript:document.location.href='../goods/view.php?goodsno="+goods_data[i].goodsno+"';\"></dt></dl></li>";
					$("#goods-item-list").append(goods_html);

					j++;
				}

				$("[name=item_cnt]").val(parseInt($("[name=item_cnt]").val()) + j);

				if(j == 10) {
					var more_html = "";
					more_html += "<li class=\"more\" onClick=\"javascript:getGoodsListData();\">더 보기...</li>";
					$("#goods-item-list").append(more_html);

				}
			}
		}
	}

}

function getCategoryData(now_cate) {

	var data_param;

	data_param = "mode=get_category";
	data_param += "&now_cate=" + now_cate;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if (res.child_res != null) {
					if (res.child_res.length > 0) {
						makeCateList(res.child_res);
					}
					else {
						showCategoryMsg("하위카테고리가 없습니다");
					}
				}
				else {
					showCategoryMsg("하위카테고리가 없습니다");
				}

				if(res.cate_path.length >0) {
					makePath(res.cate_path);
				}
				else {

					if($("#cate-area div.top_path").length > 0) {
						$("#cate-area div.top_path").show();
						$("#cate-area div.top_path").html("<div class='now_path'><div class='pathitem activeitem allpath' onClick='javascript:cateSelect(\"\");'>전체카테고리</div></div>");
					}
					else {
						$(".cate_path").html("");
						$("#cate-area div.top_title").html("카테고리 ");
					}
				}


			},
			dataType:'json'
		});
	}
	catch(e) {
		alert(e);
	}
}

function makePath(path_data) {
	/*
	if($(".cate_path").length > 0) {
		$(".cate_path").html("");
		var path_html = "";
		path_html += "<div class='now_path'>";
		for(var i=0; i<path_data.length; i++) {
			path_html+=path_data[i];

			if(path_data[i+1] != "undefined" && path_data[i+1]) {
				path_html+= " > ";
			}
		}
		path_html += "</div>";
		$(".cate_path").append(path_html);

		var item_html = "";
		item_html += "<div class='cate_path_item'><div class='cate_path_nm' onclick='javascript:upCateSelect();'>이전 카테고리 보기</div></div>";
		$(".cate_path").append(item_html);
		$(".cate_path_nm").animate({"margin-left":"0%"},300);
		$(".cate_path_item").animate({"margin-right":"0%"},300);
		$(".cate_path").animate({"margin-left":"0%"},300);
	}
	*/
	//$("#cate-area div.top_title").text());


		//$("#cate-area div.top_title").html("");

		if($("#cate-area div.top_path").length > 0) {
			var path_items = $("#cate-area div.top_path div.now_path div.pathitem");

			var now_cate = $("[name=now_cate]").val();

			if (path_items.length > path_data.length) {
				var path_html = "";
				for (var i = 0; i < path_items.length; i++) {

					var active_item = "";
					var first_item = "";
					var arrow_html = "";
					var go_now_cate = "";

					if(i == path_data.length) {
						active_item = " activeitem";
					}

					if(i == 0) {
						first_item = " allpath";
					}
					else {
						arrow_html = "<div class='patharrow'></div>";
					}

					go_now_cate = now_cate.substr(0, i*3);

					path_html += arrow_html+"<div class='pathitem"+active_item+first_item+"' onClick='javascript:cateSelect(\"" + go_now_cate + "\");'>" + $(path_items[i]).html() + "</div>";

					if(i == path_data.length) {
						break;
					}
				}

				$("#cate-area div.top_path").slideDown(100);
			}
			else {

				$(".pathitem").removeClass('activeitem');

				var path_html = "";

				path_html = $("#cate-area div.top_path div.now_path").html();


				for (var i = path_items.length-1; i < path_data.length; i++) {

					var go_now_cate = "";
					go_now_cate = now_cate.substr(0, (i+1)*3);

					var active_item = "";
					if(i == path_data.length-1) {
						active_item = " activeitem";
					}

					path_html += "<div class='patharrow'></div><div class='pathitem"+active_item+"' onClick='javascript:cateSelect(\"" + go_now_cate + "\");'>" + path_data[i] + "</div>";
				}

				$("#cate-area div.top_path").slideDown(100);
			}


			$("#cate-area div.top_path div.now_path").html(path_html);
		}
		else {
			var path_html = "";
			path_html += "<div class='now_path'>";
			for(var i=0; i<path_data.length; i++) {
				path_html+= "<div class='pathitem longtextdot'>"+path_data[i]+"</div>";

				if(path_data[i+1] != "undefined" && path_data[i+1]) {
					path_html+= "<div class='patharrow'></div>";
				}
			}
			path_html += "</div>";
			var item_html = "";
			if (path_data.length > 0) item_html += "<div class='btnimg' onclick='javascript:upCateSelect();'></div>";
			path_html += item_html;
			$("#cate-area div.top_title").html(path_html);
		}

}

function makeCateList(cate_data) {
	if($(".cate_list").length > 0) {
		$(".cate_list").html("");
		for(var i=0; i<cate_data.length; i++) {
			var item_html = "";
			if (cate_data[i].sub_count>0) {

				if(i % 2 == 1) {
					item_html += "<div class='cate_item cate_item2'><div class='cate_nm'  onclick='javascript:cateSelect(\""+cate_data[i].category+"\");'>"+cate_data[i].catnm+"</div><div class='cate_nm_arrow'></div><div class='cate_prd_btn' onclick='javascript:goCate(\""+cate_data[i].category+"\");'></div></div>"
				}
				else {
					item_html += "<div class='cate_item'><div class='cate_nm'  onclick='javascript:cateSelect(\""+cate_data[i].category+"\");'>"+cate_data[i].catnm+"</div><div class='cate_nm_arrow'></div><div class='cate_prd_btn' onclick='javascript:goCate(\""+cate_data[i].category+"\");'></div></div>"
				}

			} else {

				if (i % 2 == 1) {
					item_html += "<div class='cate_item cate_item2'><div class='cate_nm' onclick='javascript:showCategoryMsg(\"하위카테고리가 없습니다\")'>" + cate_data[i].catnm + "</div><div class='cate_prd_btn' onclick='javascript:goCate(\"" + cate_data[i].category + "\");'></div></div>"
				}
				else {
					item_html += "<div class='cate_item'><div class='cate_nm' onclick='javascript:showCategoryMsg(\"하위카테고리가 없습니다\")'>" + cate_data[i].catnm + "</div><div class='cate_prd_btn' onclick='javascript:goCate(\"" + cate_data[i].category + "\");'></div></div>"
				}

			}
			$(".cate_list").append(item_html);
		}

		$(".cate_item_arrow").animate({"margin-right":"0%"},300);
		$(".cate_nm").animate({"margin-left":"0%"},300);
		$(".cate_item").animate({"margin-right":"0%"},300);
		$(".cate_item2").animate({"margin-right":"0%"},300);
		$(".cate_list").animate({"margin-left":"0%"},300);
	}
}

function upCateSelect() {
	var now_cate = $("[name=now_cate]").val();
	var tmp_cate = now_cate.substr(0, now_cate.length -3);
	$("[name=now_cate]").val(tmp_cate);
	getCategoryData(tmp_cate);

}

function cateSelect(category) {
	$("[name=now_cate]").val(category);
	getCategoryData(category);
}

function goCate(category) {
	document.location.href="/"+ mobile_root + "/goods/list.php?category=" + category;
}

function goGoods(goodsno) {
	document.location.href="/"+ mobile_root + "/goods/view.php?goodsno=" + goodsno;
}

function getMobileHomepath() {
	// 각 URL 최상위 홈PATH를 구한다. 모바일의 홈이 여러 종류일수 있으므로  2012-09-20 khs
	var path1 = document.location.pathname;

	if (path1.charAt(0) == '/')	{
		path1 = path1.substring(1);
	}
	var x = path1.split("/");

	return x[0];
}

/* FAQ 스크립트 시작*/
$(function() {

	var article = $('.faq .article');
	//article.addClass('hide');
	//article.find('.a').slideUp(100);

	$('.faq .article .q').live("click", function(e){
		var article = $('.faq .article');
		var myArticle = $(this).parents('.article:first');

		if(myArticle.hasClass('hide')) {
			//article.addClass('hide').removeClass('show');
			//article.find('.a').slideUp(100);
			myArticle.removeClass('hide').addClass('show');
			myArticle.find('.a').slideDown(100);
		} else {
			myArticle.removeClass('show').addClass('hide');
			myArticle.find('.a').slideUp(100);
		}
	});
});

function getFaqListData(flag) {

	var itemcd = $("[name=faq_cate]").val();
	var item_cnt = $("[name=item_cnt]").val();

	var data_param;
	data_param = "mode=get_faq";

	data_param += "&itemcd=" + itemcd;

	if(flag == 1)		$("[name=item_cnt]").val(0);
	else if(flag == 2)	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				makeFaqList(flag, res.faq_data);
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}

}

function makeFaqList(flag, faq_data) {

	if($("#faq-item-list").length > 0) {

		var item_cnt = $("[name=item_cnt]").val();

		if(item_cnt == 0 && faq_data.length <1) {
			var no_faq_html = "";
			no_faq_html += "<li class=\"article\">";
			no_faq_html += "<div class=\"q nodata\">검색 결과가 없습니다.</div>";
			no_faq_html += "</li>";
			if(flag == 1) $("#faq-item-list").html(no_faq_html);
			else if(flag == 2) $("#faq-item-list").append(no_faq_html);
		}
		else {
			$("#faq-item-list").find("[class=\"q more\"]").remove();

			var j = 0;
			var buffer = "";
			for(var i=0; i<faq_data.length; i++) {
				var faq_html ="";
				faq_html = "<li class=\"article\">";
				faq_html += "<div class=\"q trigger\"><div class=\"arrow down\"></div><div class=\"title\">"+faq_data[i].question+"</div></div>";
				faq_html += "<div class=\"a\">";
				if(faq_data[i].descant) {
					faq_html += "<div class=\"block\"><div class='question'></div> "+faq_data[i].descant+"</div>";
				}
				faq_html += "<div><div class='answer'></div> "+faq_data[i].answer+"</div></div>";
				faq_html += "</li>";

				buffer += faq_html;

				j++;
			}

			if(flag == 1) {
				$("#faq-item-list").html(buffer);

				$("[name=item_cnt]").val(j);
			} else if(flag == 2) {
				$("#faq-item-list").append(buffer);

				$("[name=item_cnt]").val(parseInt($("[name=item_cnt]").val()) + j);
			}
/*
			if(j == 10) {
				var more_html = "";
				more_html += "<li class=\"q more\" onClick=\"javascript:getFaqListData(2);\">더 보기...</li>";
				$("#faq-item-list").append(more_html);

			}
*/
			if(j == 10) $("#btn_faq_more_box").show();
			else $("#btn_faq_more_box").hide();
		}
	}
	var article = $('.faq .article');
	article.addClass('hide');
}
/* FAQ 스크립트 종료*/

//나머지주소 수정시, 도로명/지번 나머지 주소가 같아지도록
function SameAddressSub(text) {
	var div_road_address	 = document.getElementById('div_road_address');
	var div_road_address_sub = document.getElementById('div_road_address_sub');

	if(div_road_address.innerHTML == "") {
		div_road_address_sub.style.display="none";
	} else {
		div_road_address_sub.style.display="";
		div_road_address_sub.innerHTML = text.value;
	}
}

/*** Cookie 생성 ***/
function setCookieMobile(name, value, expire_day, path, domain, secure) {
	var expires = new Date();
	expires.setDate(expires.getDate() + parseInt(expire_day));

	var curCookie = name + "=" + escape(value) +
		((expires) ? "; expires=" + expires.toGMTString() : "") +
		((path) ? "; path=" + path : "") +
		((domain) ? "; domain=" + domain : "") +
		((secure) ? "; secure" : "");

	document.cookie = curCookie;
}

/*** 슬라이드 메뉴 작동 ***/
function flipOffCanvas(){
	function e()
	{
		var e = false;
		(function (t)
		{
			if (
				/(android|ipad|playbook|silk|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i
				.test(t) ||
				/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i
				.test(t.substr(0, 4))) e = true
		})(navigator.userAgent || navigator.vendor ||
			window.opera);
		return e
	}

	// i = e() ? "touchend" : "click";
	// $('.js-navtoggle').bind(i,function(){
	$('.js-navtoggle').bind('click',function(){
		 if(navigator.userAgent.match(/Android/i)){
  	  		window.scrollTo(0,1);
 		}
		var gnb = $('.gd-flipcover');
		var gnbbg = $('.gd-flipbg');
		var gnbclosebtn = $('.gd-gnb-close');
		gnb.css('min-height',$(document).height()+'px');
		$('.gd-flipcover, .gd-flipbg').css('min-height',$(document).height()+'px');
		if($('body').is('.gd-open')){
			$('body').removeClass('gd-open');
			// $(document).bind('touchmove',function(e){
			// 	e.preventDefault();
			// });
			$('.gd-flipcover').bind('touchmove',function(e){
				e.stopPropagation();
			});
			// $('html,body').css('overflow','hidden');
		}else{

			$('body').addClass('gd-open');
			// $('html,body').css('overflow','auto');
			// $(document).unbind('touchmove');
		}
	});
}

/*** 슬라이드 메뉴 본문 클릭 이벤트 ***/
function offCanvasGnb(){
	$(".gd-gnb .goods-review").bind('click',function(){
	document.location.href = "/" + getMobileHomepath() + "/goods/review.php";
	});
	$(".gd-gnb .goods-qna").bind('click',function(){
		document.location.href = "/" + getMobileHomepath() + "/goods/goods_qna_list.php?isAll=Y";
	});
	$(".gd-gnb .community").bind('click',function(){
		document.location.href = "/" + getMobileHomepath() + "/board/index.php";
	});
	$(".gd-gnb .wishlist").bind('click',function(){
		if($(this).hasClass('login')) {
			alert('로그인이 필요한 메뉴입니다.');
			document.location.href="/" + getMobileHomepath() + "/mem/login.php";
		} else {
			document.location.href="/" + getMobileHomepath() + "/myp/wishlist.php";
		}
	});
	showCateMenu($('#category-menu'), '');
	var dep1length = $('.dep1>li').length;
	var onoffbtn = '<button type="button" class="btn-reset gnb-arr" ><span class="sprite-icon icon-arr-b-white"></span></button>';

	for(var i = 0; i<dep1length-1; i++){
		var self = $('.dep1>li').eq(i);
		var selfDep2 = self.find('.dep2');
		if(selfDep2.length > 0){
			self.prepend(onoffbtn);
			if(selfDep2.css('display')=='block'){
			selfDep2.siblings('button').addClass('block');
			}
		}
	}

	$('.gd-gnb .dep1 .board-nav a,.gd-gnb .dep1 .board-nav button').bind('click',function(e){
		var currentItem = $(this.parentNode);
		var self = $(this);
		togglenav(self,currentItem);
	});

	function onoffclass(state,self){
		self.parent().siblings('li').find('> button,.open').removeClass('block');
		self[0].tagName == 'A' ? self.prev().addClass(state) : self.addClass(state); // a태그 일 때는 this.parent , 아니면 본인 (button)
		// self[0].tagName == 'A' ? alert(1) : alert(2);
		if(self.parent('.on').length==0){
			self.parent().find('> button,.open').removeClass('block');
		}
	}
}

jQuery(function(){
	$("#speach-description-player").bind("$play", function(event, $container, $timer){
		event.preventDefault();
		event.stopPropagation();
		if ($timer) $(this).data("timer", $timer);
		// 재생중이라면 정지
		if ($container.hasClass("playing")) {
			$(this).trigger("$pause");
		}
		// 재생중이 아니라면 플레이
		else {
			this.src = $container.find(".speach-description-play").attr("data-src");
			this.play();
		}
		$(this).data("container", $container);
	}).bind("$pause", function(){
		$(this).data("container").removeClass("playing");
		this.pause();
	}).bind("progress", function(){
		$(this).data("container").addClass("loading");
	}).bind("loadeddata", function(){
		$(this).data("container").removeClass("loading");
	}).bind("playing", function(){
		var $lastPlayedContainer = $(this).data("playingContainer");
		if ($lastPlayedContainer) $lastPlayedContainer.removeClass("playing");
		var $playingContainer = $(this).data("container");
		$playingContainer.addClass("playing");
		$(this).data("playingContainer", $playingContainer);
		$(this).trigger("$updateTimer", [1000]);
	}).bind("error", function(){
		alert("서비스 오류로 인하여 재생할 수 없습니다. 지속적인 오류 발생 시 다른브라우저를 이용하여 주시기 바랍니다.");
	}).bind("ended", function(){
		$(this).data("container").removeClass("playing");
		$(this).trigger("$pause");
		$(this).removeData("container");
		$(this).removeData("playingContainer");
	}).bind("$updateTimer", function(event, interval){
		var $player = $(this);
		if (isNaN(this.duration) || isNaN(this.currentTime) || this.duration <= this.currentTime || !$player.data("timer")) return false;
		if (this.duration.toString().toLowerCase() === "infinity") return false; // Safari
		var sec_num = Math.round(this.duration - this.currentTime);
		var hours   = Math.floor(sec_num / 3600);
		var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
		var seconds = sec_num - (hours * 3600) - (minutes * 60);
		if (minutes < 10) minutes = "0" + minutes;
		if (seconds < 10) seconds = "0" + seconds;
		var time = minutes + ':' + seconds;
		if (hours > 0) {
			if (hours < 10) hours = "0" + hours;
			time = hours + ':' + time;
		}
		$player.data("timer").text(time);
		if (interval) {
			setTimeout(function(){$player.trigger("$updateTimer", [interval])}, interval);
		}
	});
	if($(".speach-description-play").length) {
		$(".speach-description-play").unbind("click").bind("click", function(event){
			var $player = $("#speach-description-player");
			if (!$player.length) return false;
			$player.trigger("$play", [$(this).parent()]);
			event.preventDefault();
			event.stopPropagation();
		});
	}
});
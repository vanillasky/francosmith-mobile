var get_design_data_url = "/"+mobile_root+"/eAPI/mGetDesignData.php";
var get_display_data_url = "/"+mobile_root+"/eAPI/mGetDisplayData.php";
var get_design_data_url_event = "/"+mobile_root+"/eAPI/mGetDesignDataEvent.php";
var get_display_data_url_event = "/"+mobile_root+"/eAPI/mGetDisplayDataEvent.php";

var aTabDesignData = new Array(); 
var aTabDisplayData = new Array(); 

/*
 * displayGoods - 상품진열하기
 */
function displayGoods(mdesign_no, page_type) {
	var design_data;
	design_data = getDesignData(mdesign_no, page_type);

	if(design_data.mdesign_no) {
		var disp_data;
		disp_data = getDisplayData(design_data.mdesign_no, design_data.display_type);
		
		var displayHtml = "";
		
		if(page_type == "cate") {
			if(design_data.text_temp1) {
				displayHtml += "<div>" + design_data.event_body + "</div>";	
			}
		}
		
		displayHtml = createHtml(design_data, disp_data);
		document.write(displayHtml);
		$(".speach-description-play").unbind("click").bind("click", function(event){
			var $player = $("#speach-description-player");
			if (!$player.length) return false;
			$player.trigger("$play", [$(this).parent()]);
			event.preventDefault();
			event.stopPropagation();
		});
	}
	else {
		return false;
	}
}

/*
 * displayGoodsEvent - 상품진열하기(이벤트)
 */
function displayGoodsEvent(mevent_no) {
	var design_data;
	design_data = getDesignDataEvent(mevent_no);
	if(design_data.mevent_no) {
		var disp_data;
		disp_data = getDisplayDataEvent(design_data.mevent_no);
		var displayHtml = "";
		if(design_data.event_body) {
			displayHtml += "<div>" + design_data.event_body + "</div>";
		}
		displayHtml = createHtml(design_data, disp_data);
		document.write(displayHtml);
		$(".speach-description-play").unbind("click").bind("click", function(event){
			var $player = $("#speach-description-player");
			if (!$player.length) return false;
			$player.trigger("$play", [$(this).parent()]);
			event.preventDefault();
			event.stopPropagation();
		});
	}
	else {
		return false;
	}
}

/*
 * createHtml - html 생성
 */
function createHtml(design_data, disp_data) {
	
	var ret_html = "";

	switch(design_data.tpl) {
		case "tpl_01" :		// 모바일 리뉴얼 버전에서 지원하지 않음. 
			//ret_html = createHtmlGallery(design_data, disp_data);
			break;
		case "tpl_02" :		// 모바일 리뉴얼 버전에서 지원하지 않음. 
			//ret_html = createHtmlList(design_data, disp_data);
			break;
		case "tpl_03" :
			ret_html = createHtmlGoodsScroll(design_data, disp_data);
			break;
		case "tpl_04" :
			ret_html = createHtmlImageScroll(design_data, disp_data);
			break;
		case "tpl_05" :
			ret_html = createHtmlTab(design_data, disp_data);
			break;
		case "tpl_06" :
			ret_html = createHtmlMagazine(design_data, disp_data);
			break;
		case "tpl_07" :
			ret_html = createHtmlBannerRolling(design_data, disp_data);
			break;
	}

	return ret_html;
}

function createHtmlGallery(design_data, disp_data) {

	var page_goods_cnt = parseInt(design_data.line_cnt) * parseInt(design_data.disp_cnt);
	var page_cnt = disp_data.length / page_goods_cnt;
	var remain_cnt = disp_data.length % page_goods_cnt;

	var r_goods_cnt = 0;
	
	if(remain_cnt > 0) page_cnt ++;

	var tmp_html = "";
	for(var i=0; i<page_cnt; i++) {
		tmp_html += "<table>";
		for(var j=0; j<design_data.line_cnt; j++) {
			tmp_html += "<tr>";
			for(var k=0; k<design_data.disp_cnt; k++) { 
				tmp_html += "<td>";

				if(r_goods_cnt < disp_data.length) {
					tmp_html += "<div>" +  disp_data[r_goods_cnt].goodsnm + "</div>";
					var img_nm = "";
					if(disp_data[r_goods_cnt].img_mobile) {
						img_nm = disp_data[r_goods_cnt].img_mobile;
					}
					else {
						img_nm = disp_data[r_goods_cnt].img_l;
					}

					tmp_html += "<div>" +  img_nm + "</div>";

					tmp_html += "<div>" +  disp_data[r_goods_cnt].price + "</div>";
					tmp_html += "</td>";
				}
				r_goods_cnt ++;
			}
			tmp_html += "</tr>";
		}
		tmp_html += "</table>";
		
	}
	return tmp_html;
}

function createHtmlList(design_data, disp_data) {

	var page_goods_cnt = parseInt(design_data.line_cnt) * parseInt(design_data.disp_cnt);
	var page_cnt = disp_data.length / page_goods_cnt;
	var remain_cnt = disp_data.length % page_goods_cnt;

	var r_goods_cnt = 0;
	if(remain_cnt > 0) page_cnt ++;

	var tmp_html = "";
	for(var i=0; i<page_cnt && r_goods_cnt<disp_data.length; i++) {
		tmp_html += "<table>";
		for(var j=0; j<design_data.line_cnt && r_goods_cnt<disp_data.length; j++) {
			tmp_html += "<tr>";
			for(var k=0; k<design_data.disp_cnt && r_goods_cnt<disp_data.length; k++) { 
				tmp_html += "<td>";
				tmp_html += "<div>" +  disp_data[r_goods_cnt].goodsnm + "</div>";
				var img_nm = "";
				if(disp_data[r_goods_cnt].img_mobile) {
					img_nm = disp_data[r_goods_cnt].img_mobile;
				}
				else {
					// img_nm = disp_data[r_goods_cnt].img_l;
					img_nm = disp_data[r_goods_cnt].goods_img;
				}

				tmp_html += "<div>" +  img_nm + "</div>";
				tmp_html += "<div>" +  disp_data[r_goods_cnt].shortdesc + "</div>";
				//tmp_html += "<div>" +  disp_data[r_goods_cnt].price + "</div>";
				tmp_html += "<div>" +  disp_data[r_goods_cnt].goods_price + "</div>";
				tmp_html += "<div>" +  disp_data[r_goods_cnt].reserve + "</div>";
				tmp_html += "<div>" +  "상세페이지 바로가기" + "</div>";
				tmp_html += "</td>";
				r_goods_cnt ++;
				if (r_goods_cnt >= disp_data.length) break; 
			}
			tmp_html += "</tr>";
		}
		tmp_html += "</table>";
		
	}
	return tmp_html;

}

function createHtmlGoodsScroll(design_data, disp_data) {
	
	var page_goods_cnt = parseInt(design_data.line_cnt) * parseInt(design_data.disp_cnt);

	var page_cnt = Math.floor(disp_data.length / page_goods_cnt);
	
	var remain_cnt = disp_data.length % page_goods_cnt;
	
	if(remain_cnt > 0) {
		page_cnt = page_cnt + 1;
	}
	
	var item_width = Math.floor(99 / parseInt(design_data.disp_cnt));
	var item_style = "style=\"width:"+item_width+"%\"";

	var item_name_width = Math.floor(document.body.clientWidth / parseInt(design_data.disp_cnt));  
	var item_name_style = "style=\"width:"+(item_name_width-26)+"px;\"";

	var r_goods_cnt = 0;
	
	var tmp_html = "";
	
	tmp_html += "<div class=\"list_goodsscroll\" id=\"goodsscroll-"+design_data.mdesign_no+"\">";
	
	if(design_data.title == null || design_data.title == "undefined") {
		design_data.title = ' ';
	}
	tmp_html += "<div class=\"list_title\"><div class=\"bullet\"></div><div class=\"title\">"+design_data.title+"</div></div>";
	
	var goods_cnt = 0;
	tmp_html += "<div class=\"swipe_gs\" id=\"swipe_gs-"+design_data.mdesign_no+"\"><div>";
	for(var i=0; i<page_cnt; i++) {
		var hidden_class = "";
		if(i != 0) {
			hidden_class = " hidden";
		}

		tmp_html += "<div class=\"list_content"+hidden_class+"\" id=\"goodsscroll-"+design_data.mdesign_no+"-"+ (i + 1).toString() +"\">";
		tmp_html += "<div class=\"list_content_border\"></div>";
			
			for(var j=0; j<page_goods_cnt; j++) {
				var goodsno = "";
				var goods_img = "";
				var goodsnm = "";
				var goods_price = "";
				var special_discount = "";
				var couponImage = "";
				var couponPrice = "";
				var js_html = "";
				var speach_description = "";
				var goods_strprice = "";
				var consumer = "";
				var css_selector = "";

				if(disp_data[goods_cnt] != undefined) {
					
					if(disp_data[goods_cnt].goodsno != null && disp_data[goods_cnt].goodsno != "undefined") {
						goodsno = disp_data[goods_cnt].goodsno;
					}
					
					if(disp_data[goods_cnt].goods_img != null && disp_data[goods_cnt].goods_img != "undefined") {
						goods_img = disp_data[goods_cnt].goods_img;
					}

					if(disp_data[goods_cnt].goodsnm != null && disp_data[goods_cnt].goodsnm != "undefined") {
						goodsnm = disp_data[goods_cnt].goodsnm;
					}

					if(disp_data[goods_cnt].goods_price != null && disp_data[goods_cnt].goods_price != "undefined") {
						goods_price = disp_data[goods_cnt].goods_price;
					}

					if(disp_data[goods_cnt].special_discount != null && disp_data[goods_cnt].special_discount != "undefined") {
						special_discount = disp_data[goods_cnt].special_discount;
					}

					if(disp_data[goods_cnt].coupon_discount != null && disp_data[goods_cnt].coupon_discount != "undefined") {
						couponImage = "<div class=\"item_coupon_img\"></div>";
					}

					if(design_data.display_type == '3') {
						js_html = "javascript:goCate('"+goodsno+"');"
					}
					else {
						js_html = "javascript:goGoods('"+goodsno+"');"
					}
					
					if (disp_data[goods_cnt].tts_url) {
						speach_description = '<div class="goods-speach-description" style="display: none;">'
								  + '<span class="speach-description-play" data-src="' + disp_data[goods_cnt].tts_url + '">재생</span>'
								  + '<span class="speach-description-timer"></span>'
								  + '</div>';
					}

					if(disp_data[goods_cnt].goods_strprice != null && disp_data[goods_cnt].goods_strprice != "undefined") {
						goods_strprice = disp_data[goods_cnt].goods_strprice;
					}

					if(disp_data[goods_cnt].consumer != null && disp_data[goods_cnt].consumer != "undefined") {
						consumer = disp_data[goods_cnt].consumer;
					}

					if(disp_data[goods_cnt].coupon != null && disp_data[goods_cnt].coupon != "undefined") {
						couponPrice = disp_data[goods_cnt].coupon;
					}

					if(disp_data[goods_cnt].css_selector != null && disp_data[goods_cnt].css_selector != "undefined") {
						css_selector = disp_data[goods_cnt].css_selector;
					}

				}

				tmp_html += "<div class=\"list_item\" "+item_style+" onClick=\""+js_html+"\">";
				if(goods_img != "") {
					tmp_html += "<div class=\"item_img\">" + couponImage + "<img src=\""+goods_img+"\""+css_selector+" />" + speach_description + "</div>";
				}
				else {
					tmp_html += "<div class=\"item_img\">" + couponImage + "</div>";
				}
				tmp_html += "<div class=\"item_name\" " + item_name_style + " >"+goodsnm+"</div>";

				if( goods_strprice == "" && consumer != "" ) {
					tmp_html += "<div class=\"item_consumer\" style=\"display: none;\"><strike>"+comma(consumer)+" 원</strike>↓</div>";
				}
				tmp_html += "<div class=\"item_price\"><b>"+goods_price+"</b></div>";
				if( special_discount ) {
					tmp_html += "<div class=\"item_discount\" style=\"display: none;\">"+special_discount+" 할인</div>";
				}
				if( couponPrice ) {
					tmp_html += "<div class=\"item_coupon_price\" style=\"display: none;\">"+comma(couponPrice)+" 원 <div class=\"item_coupon_icon\"></div></div>";
				}
				tmp_html += "</div>		";

				goods_cnt++;
			}
		tmp_html += "<div class=\"list_content_border\"></div>";
		tmp_html += "</div>";
		
		
	}
	tmp_html += "</div></div>";
	tmp_html += "<div class=\"list_page\">";
	tmp_html += "<div class=\"list_page_wrap\">";
	tmp_html += "<div class=\"list_page_left\" onclick=\"javascript:scroll_btn('swipe_gs-"+design_data.mdesign_no+"', 'left');\"></div>";
	tmp_html += "<div class=\"list_page_num\"><span id=\"swipe_gs-"+design_data.mdesign_no+"-page\" class=\"n_page\">1</span> / <span id=\"goodsscroll-"+design_data.mdesign_no+"-tpage\">"+(page_cnt).toString()+"</span></div>";
	tmp_html += "<div class=\"list_page_right\" onclick=\"javascript:scroll_btn('swipe_gs-"+design_data.mdesign_no+"', 'right');\"></div>";
	tmp_html += "</div>";
	tmp_html += "</div>";
	tmp_html += "<div class=\"list_margin\"></div>";
	tmp_html += "</div>";
	
	return tmp_html;
}

function createHtmlImageScroll(design_data, disp_data) {
	
	var page_goods_cnt = parseInt(design_data.line_cnt) * parseInt(design_data.disp_cnt);
	var page_cnt = Math.floor(disp_data.length / page_goods_cnt);
	var remain_cnt = disp_data.length % page_goods_cnt;

	if(remain_cnt > 0) {
		page_cnt = page_cnt + 1;
	}
	
	var item_width = Math.floor(99 / parseInt(design_data.disp_cnt));
	var item_style = "style=\"width:"+item_width+"%\"";
	var r_goods_cnt = 0;
	var tmp_html = "";
	
	tmp_html += "<div class=\"list_imgscroll\" id=\"imgscroll-"+design_data.mdesign_no+"\">";
	
	if(design_data.title != null && design_data.title != "undefined") {
		
		tmp_html += "<div class=\"list_title\"><div class=\"bullet\"></div><div class=\"title\">"+design_data.title+"</div>";
	}
	else {
		tmp_html += "<div class=\"list_title\"><div class=\"title\"></div>";
	}

	tmp_html += "<div class=\"list_page\"><span id=\"swipe_is-"+design_data.mdesign_no+"-page\" class=\"n_page\">1</span> / <span id=\"swipe_is-"+design_data.mdesign_no+"-tpage\">"+page_cnt+"</span></div>";
	tmp_html += "</div>";
	
	tmp_html += "<div class=\"list_content_wrap\">";

	var goods_cnt = 0;
	
	tmp_html += "<div class=\"swipe_is\" id=\"swipe_is-"+design_data.mdesign_no+"\"><div>";
	for(var i=0; i<page_cnt && disp_data.length > goods_cnt; i++) {
		var hidden_class = "";

		if(i != 0) {
			hidden_class = " hidden";
		}

		tmp_html += "<div class=\"list_content"+hidden_class+"\" id=\"imgscroll-"+design_data.mdesign_no+"-"+ (i + 1).toString() +"\">";
		tmp_html += "<div class=\"list_content_border\"></div>";
		
			for(var j=0; j<page_goods_cnt && disp_data.length > goods_cnt; j++) {
				var goodsno = "";
				var goods_img = "";
				var goodsnm = "";
				var goods_price = "";
				var special_discount = "";
				var couponImage = "";
				var js_html = "";
				var speach_description = "";
				var strprice = "";
				var consumer = "";
				var couponPrice = "";
				var css_selector = "";

				if(disp_data[goods_cnt] != undefined) {
					
					if(disp_data[goods_cnt].goodsno != null && disp_data[goods_cnt].goodsno != "undefined") {
						goodsno = disp_data[goods_cnt].goodsno;
					}
					
					if(disp_data[goods_cnt].goods_img != null && disp_data[goods_cnt].goods_img != "undefined") {
						goods_img = disp_data[goods_cnt].goods_img;
					}

					if(disp_data[goods_cnt].goodsnm != null && disp_data[goods_cnt].goodsnm != "undefined") {
						goodsnm = disp_data[goods_cnt].goodsnm;
					}

					if(disp_data[goods_cnt].goods_price != null && disp_data[goods_cnt].goods_price != "undefined") {
						goods_price = disp_data[goods_cnt].goods_price;
					}

					if(disp_data[goods_cnt].special_discount != null && disp_data[goods_cnt].special_discount != "undefined") {
						special_discount = disp_data[goods_cnt].special_discount;
					}

					if(disp_data[goods_cnt].strprice != null && disp_data[goods_cnt].strprice != "undefined") {
						strprice = disp_data[goods_cnt].strprice;
					}

					if(disp_data[goods_cnt].consumer != null && disp_data[goods_cnt].consumer != "undefined") {
						consumer = disp_data[goods_cnt].consumer;
					}

					if(disp_data[goods_cnt].coupon != null && disp_data[goods_cnt].coupon != "undefined") {
						couponPrice = disp_data[goods_cnt].coupon;
					}

					if(disp_data[goods_cnt].coupon_discount != null && disp_data[goods_cnt].coupon_discount != "undefined") {
						couponImage = "<div class=\"item_coupon_img\"></div>";
					}

					if(disp_data[goods_cnt].css_selector != null && disp_data[goods_cnt].css_selector != "undefined") {
						css_selector = disp_data[goods_cnt].css_selector;
					}

					if(design_data.display_type == '3') {
						js_html = "javascript:goCate('"+goodsno+"');"
					}
					else {
						js_html = "javascript:goGoods('"+goodsno+"');"
					}
					if (disp_data[goods_cnt].tts_url) {
						speach_description = '<div class="goods-speach-description" style="display: none;">'
								   + '<span class="speach-description-play" data-src="' + disp_data[goods_cnt].tts_url + '">재생</span>'
								   + '<span class="speach-description-timer"></span>'
								   + '</div>';
					}

				}

				tmp_html += "<div class=\"list_item\" "+item_style+" onClick=\""+js_html+"\">";
				if(goods_img != "") {
					tmp_html += "<div class=\"item_img\">" + couponImage + "<img src=\""+goods_img+"\""+css_selector+" />" + speach_description + "</div>";
				}
				else {
					tmp_html += "<div class=\"item_img\">" + couponImage + "</div>";
				}
				tmp_html += "<div class=\"item_text-wrap\">";
				tmp_html += "<div class=\"item_text\">";
				tmp_html += "<div class=\"item_name\">"+goodsnm+"</div>";
			
				if( strprice == "" && consumer != "" ) {
					tmp_html += "<div class=\"item_consumer\" style=\"display: none;\"><strike>"+comma(consumer)+" 원</strike>↓</div>";
				}

				tmp_html += "<div class=\"item_price\">"+goods_price+"</div>";
				if( special_discount ) {
					tmp_html += "<div class=\"item_discount\" style=\"display: none;\">"+special_discount+" 할인</div>";
				}
				if( couponPrice ) {
					tmp_html += "<div class=\"item_coupon_price\" style=\"display: none;\">"+comma(couponPrice)+" 원 <div class=\"item_coupon_icon\"></div></div>";
				}
				tmp_html += "</div>";
				tmp_html += "</div>";
				tmp_html += "</div>";

				goods_cnt++; 
			}
		tmp_html += "<div class=\"list_content_border\"></div>";
		tmp_html += "</div>";
	}
	tmp_html += "</div></div>";

	tmp_html += "<div class=\"list_page_btn\">";
	tmp_html += "<div class=\"left_btn\">";
	tmp_html += "<div class=\"left_btn_img\" onclick=\"javascript:scroll_btn('swipe_is-"+design_data.mdesign_no+"', 'left');\"></div>";
	tmp_html += "</div>";
	tmp_html += "<div class=\"right_btn\">";
	tmp_html += "<div class=\"right_btn_img\" onclick=\"javascript:scroll_btn('swipe_is-"+design_data.mdesign_no+"', 'right');\"></div>";
	tmp_html += "</div>";
	tmp_html += "</div>";
	tmp_html += "</div>";
	tmp_html += "<div class=\"list_margin\"></div>";
	tmp_html += "</div>";

	return tmp_html;

}

function createHtmlTab(design_data, disp_data) 
{
	var tab_cnt = parseInt(design_data.tpl_opt.tab_num);
	var item_width = Math.floor(99 / parseInt(design_data.disp_cnt));
	var item_style = "style=\"width:"+item_width+"%\"";
	var tmp_html = "";

	var tab_width = Math.floor(98 / tab_cnt);
	var tab_style = "style=\"width:"+tab_width+"%\"";

	aTabDesignData.push( new Array( design_data.mdesign_no, design_data)); 
	aTabDisplayData.push( new Array( design_data.mdesign_no, disp_data )); 

	tmp_html += "<div class=\"list_tab\" id=\"tab-"+design_data.mdesign_no+"\">";
	tmp_html += "<div class=\"tab_title\">";

	for(var t_i=0; t_i<tab_cnt; t_i++) {
		var title_active = "";
		if(t_i == 0) {
			title_active = " title_active";
		}

		tmp_html += "<div class=\"title_wrap"+title_active+"\" "+tab_style+" id=\"title-tabcontent-"+design_data.mdesign_no+"-"+(t_i+1).toString()+"\"><div class=\"title\" onclick=\"javascript:tab_click('tabcontent-"+design_data.mdesign_no+"-"+(t_i+1).toString()+"', '"+design_data.mdesign_no+"', '"+t_i.toString()+"', "+tab_cnt+");\">"+design_data.tpl_opt.tab_name[t_i+1]+"</div></div>";
	}
	tmp_html += "</div>";
	
	tmp_html += "<div class=\"list_tabcontent\" id=\"tabcontent-"+design_data.mdesign_no+"\">";
	
	var sub_html = ""; 
	sub_html = createHtmlSubTab(design_data.mdesign_no, 0); 
	
	tmp_html += sub_html; 
	tmp_html += "</div>";
	return tmp_html;
}

function getDesignDataArray(mdesign_no) 
{
	for (i=0; i<aTabDesignData.length; i++) {
		if (aTabDesignData[i][0] == mdesign_no) {
			return aTabDesignData[i][1]; 
		}
	}
	return undefined; 
}

function getDisplayDataArray(mdesign_no)
{
	for (i=0; i<aTabDisplayData.length; i++) {
		if (aTabDisplayData[i][0] == mdesign_no) {
			return aTabDisplayData[i][1]; 
		}
	}
	return undefined; 
}

//
// createHtmlSubTabl(design_no, tabidx)
//		design_no 	노출해야할 메인진열 구분자 
//		tabidx		노출할 메인진열 (탭형) 내에서 처리대상 탭 idx
// 실질적인 탭 내용에 해당하는 HTML을 생성한다. 
// 탭이 선택될때 실질적인 HTML을 생성한다.
//
// 탭이 선택될때, 이 함수가 호출되어야 한다. 
function createHtmlSubTab(mdesign_no, tabidx)
{ 
	var design_data = getDesignDataArray(mdesign_no); 
	if (design_data == undefined) return;

	var disp_data 	= getDisplayDataArray(mdesign_no);
	if (disp_data == undefined) return;
	
	var tmp_html = "";
	
	// 각 영역별 디스플레이 설정에 따른 페이지당 상품수 계산 
	var page_goods_cnt = parseInt(design_data.line_cnt) * parseInt(design_data.disp_cnt);
	var page_cnt  = Math.floor(disp_data[tabidx].length / page_goods_cnt);
	if (disp_data[tabidx].length % page_goods_cnt > 0) page_cnt += 1;

	var item_name_width = Math.floor(document.body.clientWidth / parseInt(design_data.disp_cnt));  
	var item_name_style = "style=\"width:"+(item_name_width-26)+"px;\"";

	// 현재 처리할 상품의 위치 
	var goods_cnt = 0;

	tmp_html = ""; 
	tmp_html +="<div class=\"swipe_tab\" style=\"clear:both\" id=\"swipe_tab-"+design_data.mdesign_no+"\">";
	tmp_html +="<div>";
	
	// 상품별 영역 표시 
	var item_width = Math.floor(99 / parseInt(design_data.disp_cnt));
	var item_style = "style=\"width:"+item_width+"%\"";
	var hidden_class = ""; 

	//swipe 될 페이지 루핑 
	for(var j=0; j<page_cnt; j++) {
		if (j>0)	hidden_class = " hidden";
		else 		hidden_class = "";
			
		tmp_html += "<div class=\"list_content"+hidden_class+"\" id=\"tabcontent-"+design_data.mdesign_no+"-"+(j + 1).toString()+"\">";
		tmp_html += "<div class=\"list_content_tab_border\"></div>";
		//한페이지내에서 상품노출 처리 
		for(var k=0; k<page_goods_cnt; k++) {
		
			var goodsno = "";
			var goods_img = "";
			var goodsnm = "";
			var goods_price = "";
			var speach_description = "";
			var special_discount = "";
			var couponImage = "";
			var strprice = "";
			var consumer = "";
			var couponPrice = "";
			var css_selector = "";

			if(disp_data[tabidx][goods_cnt] != undefined) {
				if(disp_data[tabidx][goods_cnt].goodsno != null && disp_data[tabidx][goods_cnt].goodsno != "undefined") {
					goodsno = disp_data[tabidx][goods_cnt].goodsno;
				}			
				if(disp_data[tabidx][goods_cnt].goods_img != null && disp_data[tabidx][goods_cnt].goods_img != "undefined") {
					goods_img = disp_data[tabidx][goods_cnt].goods_img;
				}
				if(disp_data[tabidx][goods_cnt].goodsnm != null && disp_data[tabidx][goods_cnt].goodsnm != "undefined") {
					goodsnm = disp_data[tabidx][goods_cnt].goodsnm;
				}
				if(disp_data[tabidx][goods_cnt].goods_price != null && disp_data[tabidx][goods_cnt].goods_price != "undefined") {
					goods_price = disp_data[tabidx][goods_cnt].goods_price;
				}
				if (disp_data[tabidx][goods_cnt].tts_url) {
					speach_description = '<div class="goods-speach-description" style="display: none;">'
							   + '<span class="speach-description-play" data-src="' + disp_data[tabidx][goods_cnt].tts_url + '">재생</span>'
							   + '<span class="speach-description-timer"></span>'
							   + '</div>';
				}
				if(disp_data[tabidx][goods_cnt].special_discount != null && disp_data[tabidx][goods_cnt].special_discount != "undefined") {
					special_discount = disp_data[tabidx][goods_cnt].special_discount;
				}
				if(disp_data[tabidx][goods_cnt].coupon_discount != null && disp_data[tabidx][goods_cnt].coupon_discount != "undefined") {
					couponImage = "<div class=\"item_coupon_img\"></div>";
				}
				if(disp_data[tabidx][goods_cnt].strprice != null && disp_data[tabidx][goods_cnt].strprice != "undefined") {
					strprice = disp_data[tabidx][goods_cnt].strprice;
				}
				if(disp_data[tabidx][goods_cnt].consumer != null && disp_data[tabidx][goods_cnt].consumer != "undefined") {
					consumer = disp_data[tabidx][goods_cnt].consumer;
				}
				if(disp_data[tabidx][goods_cnt].coupon != null && disp_data[tabidx][goods_cnt].coupon != "undefined") {
					couponPrice = disp_data[tabidx][goods_cnt].coupon;
				}
				if(disp_data[tabidx][goods_cnt].css_selector != null && disp_data[tabidx][goods_cnt].css_selector != "undefined") {
					css_selector = disp_data[tabidx][goods_cnt].css_selector;
				}
			}

			tmp_html += "<div class=\"list_item\" "+item_style+" onClick=\"javascript:goGoods("+goodsno+");\">";
			if(goods_img != "") {
				tmp_html += "<div class=\"item_img\">" + couponImage + "<img src=\""+goods_img+"\""+css_selector+" />" + speach_description + "</div>";
			} else {
				tmp_html += "<div class=\"item_img\">" + couponImage + "</div>";
			}
			tmp_html += "<div class=\"item_name\" " + item_name_style + ">"+goodsnm+"</div>";
			if( strprice == "" && consumer != "" ) {
				tmp_html += "<div class=\"item_consumer\" style=\"display: none;\"><strike>"+comma(consumer)+" 원</strike>↓</div>";
			}
			tmp_html += "<div class=\"item_price\">"+goods_price+"</div>";
			if( special_discount ) {
				tmp_html += "<div class=\"item_discount\" style=\"display: none;\">"+special_discount+" 할인</div>";
			}
			if( couponPrice ) {
				tmp_html += "<div class=\"item_coupon_price\" style=\"display: none;\">"+comma(couponPrice)+" 원 <div class=\"item_coupon_icon\"></div></div>";
			}
			tmp_html += "</div>";
			goods_cnt ++;
		
		}

		tmp_html += "<div class=\"list_content_border\"></div>";
		tmp_html += "</div>";
	}
	tmp_html += "</div></div>";

	tmp_html += "<div class=\"list_page\">";
	tmp_html += "<div class=\"list_page_wrap\">";

	tmp_html += "<div class=\"list_page_left\" onclick=\"javascript:scroll_btn('swipe_tab-"+design_data.mdesign_no+"', 'left');\"></div>";
	tmp_html += "<div class=\"list_page_num\"><span id=\"swipe_tab-"+design_data.mdesign_no+"-page\" class=\"n_page\">1</span> / <span id=\"tabcontent-"+design_data.mdesign_no+"-tpage\">"+(page_cnt).toString()+"</span></div>";
	tmp_html += "<div class=\"list_page_right\" onclick=\"javascript:scroll_btn('swipe_tab-"+design_data.mdesign_no+"', 'right');\"></div>";

	tmp_html += "</div>";
	tmp_html += "</div>";
	
	tmp_html += "<div class=\"list_margin\"></div>";
	tmp_html += "</div>";
	
	return tmp_html; 
}

function createHtmlMagazine(design_data, disp_data) 
{
	// page_cnt 는 설정된 상품수임. 
	var page_cnt = disp_data.length;

	var table_width = page_cnt * 70;
	var table_style = "style=\"width:"+table_width+"%\"";
	var td_width = Math.floor(99 / page_cnt);
	var td_style = "style=\"width:"+td_width+"%\"";
	
	var img_width = "style=\"width:"+(document.body.clientWidth)+"px;\" ";
	var img_height = "style=\"height:"+design_data.banner_height+"px;overflow:hidden;\"";
	if (design_data.banner_height == 0)
		img_height = ""; 
	
	var tmp_html = "";
	tmp_html += "<div class=\"list_magazine\" id=\"magazine-"+design_data.mdesign_no+"\">";

	if(design_data.title != null && design_data.title != "undefined") {
		
		tmp_html += "<div class=\"list_title\"><div class=\"bullet\"></div><div class=\"title\">"+design_data.title+"</div></div>";
	}
	else {
		tmp_html += "<div class=\"list_title\"><div class=\"title\"></div></div>";
	}

	tmp_html += "<div class=\"swipe_mg\" id=\"swipe_mg-"+design_data.mdesign_no+"\">";
	tmp_html += "<div>";
	for(var i=0; i<page_cnt; i++) {

		var goodsno = "";
		var goods_img = "";
		var goodsnm = "";
		var goods_price = "";
		var special_discount = "";
		var couponImage = "";
		var js_html = "";
		var hidden_class = ""; 
		var speach_description = "";
		var strprice = "";
		var consumer = "";
		var couponPrice = "";
		var css_selector = "";
		
		if( i != 0) {
			hidden_class = " hidden";
		}

		if(disp_data[i] != undefined) {
			
			if(disp_data[i].goodsno != null && disp_data[i].goodsno != "undefined") {
				goodsno = disp_data[i].goodsno;
			}
			
			if(disp_data[i].goods_img != null && disp_data[i].goods_img != "undefined") {
				goods_img = disp_data[i].goods_img;
			}

			if(disp_data[i].goodsnm != null && disp_data[i].goodsnm != "undefined") {
				goodsnm = disp_data[i].goodsnm;
			}

			if(disp_data[i].goods_price != null && disp_data[i].goods_price != "undefined") {
				goods_price = disp_data[i].goods_price;
			}

			if(disp_data[i].special_discount != null && disp_data[i].special_discount != "undefined") {
				special_discount = disp_data[i].special_discount;
			}
			if(disp_data[i].strprice != null && disp_data[i].strprice != "undefined") {
				strprice = disp_data[i].strprice;
			}

			if(disp_data[i].consumer != null && disp_data[i].consumer != "undefined") {
				consumer = disp_data[i].consumer;
			}

			if(disp_data[i].coupon != null && disp_data[i].coupon != "undefined") {
				couponPrice = disp_data[i].coupon;
			}
			if(disp_data[i].coupon_discount != null && disp_data[i].coupon_discount != "undefined") {
				couponImage = "<div class=\"item_coupon_img\"></div>";
			}
			if(disp_data[i].css_selector != null && disp_data[i].css_selector != "undefined") {
				css_selector = disp_data[i].css_selector;
			}

			if(design_data.display_type == '3') {
				js_html = "javascript:goCate('"+goodsno+"');"
			}
			else {
				js_html = "javascript:goGoods('"+goodsno+"');"
			}
			if (disp_data[i].tts_url) {
				speach_description = '<div class="goods-speach-description" style="display: none;">'
						   + '<span class="speach-description-play" data-src="' + disp_data[i].tts_url + '">재생</span>'
						   + '<span class="speach-description-timer"></span>'
						   + '</div>';
			}
		}
		tmp_html += "<div class=\"list_content" + hidden_class + "\" id=\"magazine-"+design_data.mdesign_no+"-"+(i + 1).toString()+"\">";
		tmp_html += "<div class=\"list_content_border\"></div>";
		tmp_html += "<div class=\"list_item\" onClick=\""+js_html+"\" " + img_height + ">";
		tmp_html += "<div class=\"item_img\">" + couponImage + "<img "+ img_width +"src=\""+goods_img+"\""+css_selector+" />" + speach_description + "</div>";
		tmp_html += "<div class=\"item_text-wrap\">";
		tmp_html += "<div class=\"item_text\">";
		tmp_html += "<div class=\"item_name\" "+ img_width +">"+goodsnm+"</div>";
		if( strprice == "" && consumer != "" ) {
			tmp_html += "<div class=\"item_consumer\" style=\"display: none;\"><strike>"+comma(consumer)+" 원</strike>↓</div>";
		}
		tmp_html += "<div class=\"item_price\">"+goods_price+"</div>";
		if( special_discount ) {
			tmp_html += "<div class=\"item_discount\" style=\"display: none;\">"+special_discount+" 할인</div>";
		}
		if( couponPrice ) {
			tmp_html += "<div class=\"item_coupon_price\" style=\"display: none;\">"+comma(couponPrice)+" 원 <div class=\"item_coupon_icon\"></div></div>";
		}
		tmp_html += "</div>";
		tmp_html += "</div>";
		tmp_html += "</div>";
		tmp_html += "<div class=\"list_content_border\"></div>";		
		tmp_html += "</div>";
		
	}
	tmp_html += "</div></div>";
	tmp_html += "<div class=\"list_page\">";
	tmp_html += "<div class=\"list_page_wrap\">";
	tmp_html += "<div class=\"list_page_left\" onclick=\"javascript:scroll_btn('swipe_mg-"+design_data.mdesign_no+"', 'left');\"></div>";
	tmp_html += "<div class=\"list_page_num\"><span id=\"swipe_mg-"+design_data.mdesign_no+"-page\" class=\"n_page\">1</span> / <span id=\"magazine-"+design_data.mdesign_no+"-tpage\">"+page_cnt+"</span></div>";
	tmp_html += "<div class=\"list_page_right\" onclick=\"javascript:scroll_btn('swipe_mg-"+design_data.mdesign_no+"', 'right');\"></div>";
	tmp_html += "</div>";
	tmp_html += "</div>";
	tmp_html += "<div class=\"list_margin\"></div>";
	tmp_html += "</div>";

	return tmp_html;
}

function createHtmlBannerRolling(design_data, disp_data) {

	var banner_cnt = parseInt(design_data.tpl_opt.banner_num);

	var tmp_html = "";
	var tmp_page_html = "";
	
	var page_width = Math.floor((90 / banner_cnt)-4);
	var page_style = "style=\"width:"+page_width+"%\"";

	var img_height = "style=\"height:"+design_data.banner_height+"px;\"";
	var img_width = "style=\"width:"+design_data.banner_width+"px;\"";
	if (design_data.banner_height >0) {
		img_width = "style=\"width:"+design_data.banner_width+"px;height:"+design_data.banner_height +"px; \" ";
	}

	tmp_html += "<div class=\"list_banner\" id=\"banner-"+design_data.mdesign_no+"\">";

	if(design_data.title != null && design_data.title != "undefined") {
		
		tmp_html += "<div class=\"list_title\"><div class=\"bullet\"></div><div class=\"title\">"+design_data.title+"</div></div>";
	}
	else {
		tmp_html += "<div class=\"list_title\"><div class=\"title\"></div></div>";
	}
	
	tmp_html += "<div class=\"list_content_border\"></div>";
	
	tmp_html += "<div class=\"swipe_ban\" id=\"swipe_ban-"+design_data.mdesign_no+"\">";
	tmp_html += "<div>";
	for(var i=0; i<banner_cnt; i++) {
		
		var hidden_class = "";
		var page_class = "";

		if(i != 0) {
			hidden_class = " hidden";
		}

		if(i == 0) {
			page_class = " now_page";
		}

		var banner_img = "";
		var link_url = "";
		
		if(disp_data[i].banner_img != null && disp_data[i].banner_img != "undefined") {
			banner_img = disp_data[i].banner_img;
		}

		if(disp_data[i].link_url != null && disp_data[i].link_url != "undefined") {
			link_url = disp_data[i].link_url;
		}

		if(banner_img) {
			tmp_html += "<div class=\"list_content"+hidden_class+"\" id=\"banner-"+design_data.mdesign_no+"-"+(i + 1).toString()+"\">";
			tmp_html += "<div class=\"list_item\" "+img_height+" onClick=\"document.location.href='"+disp_data[i].link_url+"'\">";
			tmp_html += "<img "+img_width+" src=\""+disp_data[i].banner_img+"\" />";
			tmp_html += "</div>";
			tmp_html += "</div>";
		}

		tmp_page_html += "<div class=\"list_page_box"+page_class+"\" "+page_style+" id=\"banner-"+design_data.mdesign_no+"-page-box-"+(i + 1).toString()+"\"></div>";		
	}
	tmp_html += "</div></div>";
			
	tmp_html += "<div class=\"list_content_border\"></div>";
	tmp_html += "<div class=\"list_page\">";
	tmp_html += "<div class=\"list_page_wrap\">";
	tmp_html += tmp_page_html;
	tmp_html += "<div class=\"list_page_num hidden\"><span id=\"swipe_ban-"+design_data.mdesign_no+"-page\" class=\"n_page\">1</span> / <span id=\"banner-"+design_data.mdesign_no+"-tpage\">"+banner_cnt+"</span></div>";
	tmp_html += "</div>";
	tmp_html += "</div>";
	tmp_html += "<div class=\"list_margin\"></div>";
	tmp_html += "</div>";
	
	return tmp_html;

}


/*
 * getDisplayData - e나무 DB내 진열 데이터 가져오기
 */
function getDesignData(mdesign_no, page_type) {
	var data_param = "mdesign_no=" + mdesign_no;
	
	if(!page_type) page_type = "main";

	data_param += "&page_type=" + page_type;
	
	var design_data;

	$.ajax({ 
		type : "post",
		url : get_design_data_url,
		cache:false,
		async:false,
		data: data_param,
		success: function (res) {
			design_data = res;
		},
		dataType:"json"
	});
	return design_data;
}

/*
 * getGoodsData - 진열 데이터 내 상품 가져오기
 */
function getDisplayData(mdesign_no, display_type) {
	var data_param = "mdesign_no=" + mdesign_no;	
	data_param += "&display_type=" + display_type;

	var display_data;
	$.ajax({ 
		type : "post",
		url : get_display_data_url,
		cache:false,
		async:false,
		data: data_param,
		success: function (res) {
			display_data = res;
			
		},
		dataType:"json"
	});
	return display_data;
}


/*
 * getDisplayDataEvent - e나무 DB내 진열 데이터 가져오기(이벤트)
 */
function getDesignDataEvent(mevent_no) {
	var data_param = "mevent_no=" + mevent_no;
	
	var design_data;

	$.ajax({ 
		type : "post",
		url : get_design_data_url_event,
		cache:false,
		async:false,
		data: data_param,
		success: function (res) {
			design_data = res;
		},
		dataType:"json"
	});

	return design_data;
}

/*
 * getGoodsDataEvent - 진열 데이터 내 상품 가져오기(이벤트)
 */
function getDisplayDataEvent(mevent_no) {
	var data_param = "mevent_no=" + mevent_no;	
	var display_data;
	$.ajax({ 
		type : "post",
		url : get_display_data_url_event,
		cache:false,
		async:false,
		data: data_param,
		success: function (res) {
			display_data = res;
		},
		dataType:"json"
	});
	return display_data;
}


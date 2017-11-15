function getGoodsListDataOther() {

	var category = $("[name=list_category]").val();


	var kw = $("[name=list_kw]").val();

	var item_cnt = 0;

	var data_param;
	data_param = "mode=get_goods";

	if(kw) {
		data_param += "&kw=" + kw;
	}
	else {
		data_param += "&category=" + category;
	}

	item_cnt = $(".goods-other-item").length;

	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res != null) {
					makeGoodsListOther(res.goods_data, kw, category);
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}

}

var objSwipe = null;

function makeGoodsListOther(goods_data, kw, category) {

	var goods_src = "";

	if(kw) {
		goods_src = '../goods/view.php?kw=' + kw;
	}
	else {
		goods_src = '../goods/view.php?category=' + category;
	}

	if(goods_data.length > 0) {

		var add_html = "";

		for(var i=0; i<goods_data.length; i++) {

			if((i+1) % 5 == 1) {
				add_html += '<div class="goods-other-content" >';
				add_html += '<div class="goods-other-item right-margin left-margin">';
			}
			else if((i+1) % 5 == 0){
				add_html += '<div class="goods-other-item">';
			}
			else {
				add_html += '<div class="goods-other-item right-margin">';
			}

			add_html += '<a href="'+goods_src+'&goodsno='+goods_data[i].goodsno+'">'+goods_data[i].img_html+'</a>';

			add_html += '</div>';

			if((i+1) % 5 ==0 || (i+1) == goods_data.length) {
				add_html += '</div>';
			}
		}
	}


	$("#swipe-other-goods>div").append(add_html);

	var startSlide_idx = 0;
	if(objSwipe != null) {
		startSlide_idx = objSwipe.getPos();
	}

	objSwipe = new Swipe(document.getElementById('swipe-other-goods'), {
		startSlide: startSlide_idx,
		speed: 200,
		auto: 0,
		callback: function(event, index, elem) {

			if(($(".goods-other-item").length - 10) < (index * 5)) {
				getGoodsListDataOther();
			}

		}
	});


}

function getReviewData() {


	var data_param = "mode=get_review";
	var item_cnt = $("#review-table .title").length;
	var goodsno = "";

	if($("[name=goodsno]").length > 0) {
		goodsno = $("[name=goodsno]").val();
	}

	data_param += "&item_cnt=" + item_cnt + "&goodsno="+goodsno;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {

				if(res != null) {
					makeReviewList(res, goodsno);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeReviewList(review_data, goodsno) {


	if(review_data.length > 0) {

		var add_html = "";

		for(var i=0; i<review_data.length; i++) {
			add_html+= '<tr class="title" onclick="view_content(this, '+review_data[i].sno+');">';
			add_html+= '	<td class="first">'+review_data[i].idx+'</td>';
			add_html+= '	<td class="img">'+review_data[i].img_html+'</td>';
			add_html+= '	<td class="left last">';
			add_html+= '		<div class="point-star">';

			for(var k=0; k<5; k++) {
				if(k < review_data[i].point) {
					add_html+= '<span class="active">��</span>';
				}
				else {
					add_html+= '��';
				}
			}

			add_html+= '</div>';

			if(goodsno) {
				add_html+= '	<div>'+review_data[i].review_name+' | '+review_data[i].regdt+'</div>';
			}
			add_html+= '		<div>'+review_data[i].subject+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
			add_html+= '<tr class="content-board" id="content-'+review_data[i].sno+'">';
			add_html+= '	<td colspan=3 class="">';
			add_html+= '		<div class="content-review">'+review_data[i].contents+'</div>';

			if(review_data[i].image) {
				add_html+= '<div>'+review_data[i].image+'</div>';
			}

			if(review_data[i].authdelete === 'Y') {
				add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_review\', \''+review_data[i].m_no+'\', '+review_data[i].sno+');"><div class="del-btn">�� ��</div></a>';
			}

			for(var j=0; j<review_data[i].reply.length; j++) {
				add_html+= '			<div class="content-reply">';
				add_html+= '				<div class="reply-icon"></div>';
				if(review_data[i].reply[j].subject) {
					add_html+= review_data[i].reply[j].subject +'<br />';
				}

				add_html+= review_data[i].reply[j].contents;

				if(review_data[i].reply[j].authdelete === 'Y') {
					add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_review\', \''+review_data[i].reply[j].m_no+'\', '+review_data[i].reply[j].sno+');"><div class="del-btn">�� ��</div></a>';
				}

				add_html+= '			</div>';

			}

			add_html+= '		</td>';
			add_html+= '	</tr>';

		}
	}

	$("#review-table").append(add_html);

	if(review_data.length < 10) {
		$(".more-btn").hide();
	}


}

function getQnaData() {

	var data_param = "mode=get_qna";
	var item_cnt = $("#review-table .title").length;
	var goodsno = "";

	if($("[name=goodsno]").length > 0) {
		goodsno = $("[name=goodsno]").val();
	}

	data_param += "&item_cnt=" + item_cnt + "&goodsno="+goodsno;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res != null) {
					makeQnaList(res, goodsno);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeQnaList(review_data, goodsno) {


	if(review_data.length > 0) {

		var add_html = "";

		for(var i=0; i<review_data.length; i++) {
			add_html+= '<tr class="title" onclick="view_content(this, '+review_data[i].sno+');">';
			add_html+= '	<td class="first">'+review_data[i].idx+'</td>';
			add_html+= '	<td class="img">'+review_data[i].img_html+'</td>';
			add_html+= '	<td class="left last">';
			add_html+= '		<div class="answer-yn">';

			if(review_data[i].reply_cnt > 0) {
				add_html+= '<div class="answer-y"></div>';
			}
			else {
				add_html+= '<div class="answer-n"></div>';
			}

			add_html+= '</div>';

			if(goodsno) {
				add_html+= '	<div>'+review_data[i].review_name+' | '+review_data[i].regdt+'</div>';
			}
			add_html+= '		<div>'+review_data[i].subject+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
			add_html+= '<tr class="content-board" id="content-'+review_data[i].sno+'">';
			add_html+= '	<td colspan=3 class="">';

			if(review_data[i].accessable) {
				add_html+= '	<div class="content-review">';
				add_html+= '		<div class="question-icon"></div>' + review_data[i].contents;
				add_html+= '	</div>';

				for(var j=0; j<review_data[i].reply.length; j++) {
					add_html+= '			<div class="content-reply">';
					add_html+= '				<div class="answer-icon"></div>';
					if(review_data[i].reply[j].subject) {
						add_html+= review_data[i].reply[j].subject +'<br />';
					}

					add_html+= review_data[i].reply[j].contents;
					add_html+= '			</div>';
				}
			}
			else {

				add_html+= '	<div class="content-review">';
				if(review_data[i].m_no > 0) {
					add_html+= '		��б� �Դϴ�.';
				}
				else {
					add_html+= '		��й�ȣ : <input type="password" id="goods-qna-password-'+review_data[i].sno+'" name="password" required="required"/><button type="button" data-sno="'+review_data[i].sno+'"  class="goods-qna-certification">Ȯ��</button>';
				}
				add_html+= '</div>';

			}
			add_html+= '		</td>';
			add_html+= '	</tr>';

		}
	}

	$("#review-table").append(add_html);

	if(review_data.length < 10) {
		$(".more-btn").hide();
	}


}


function getOrderListData() {

	var data_param = "mode=get_order_list_data";
	var item_cnt = $("#norderlist-area table").length;

	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {

				if(res != null) {
					makeOrderList(res);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}


function makeOrderList(order_data) {

	if(order_data.length > 0) {

		var add_html = "";

		for(var i=0; i<order_data.length; i++) {

			add_html+= '<div class="sub_title"><div class="point"></div>�ֹ���ȣ : '+order_data[i].ordno+'<button class="ord_more_btn" onclick="javascript:location.href=\'./orderview.php?ordno='+order_data[i].ordno+'\';">�󼼺���</button></div>';
			add_html+= '<table>';
			add_html+= '<tr>';
			add_html+= '	<th>��ǰ��</th>';
			add_html+= '	<td class="goods-nm">'+order_data[i].goodsnm+'</td>';
			add_html+= '</tr>';
			add_html+= '<tr>';
			add_html+= '	<th>�ֹ��Ͻ�</th>';
			add_html+= '	<td>'+order_data[i].orddt+'</td>';
			add_html+= '</tr>';
			add_html+= '<tr>';
			add_html+= '	<th>�������</th>';
			add_html+= '	<td>'+order_data[i].str_settlekind+'</td>';
			add_html+= '</tr>';
			add_html+= '<tr>';
			add_html+= '	<th>�ֹ��ݾ�</th>';
			add_html+= '	<td class="goods-price">'+order_data[i].str_settleprice+'��</td>';
			add_html+= '</tr>';
			add_html+= '<tr>';
			add_html+= '	<th>�ֹ�����</th>';
			add_html+= '	<td>'+order_data[i].str_step+'</td>';
			add_html+= '</tr>';
			add_html+= '</table>';

		}
	}

	$("#norderlist-area").append(add_html);

	if(order_data.length < 10) {
		$(".more-btn").hide();
	}
}

function getLogEmoney() {

	var data_param = "mode=get_log_emoney";
	var item_cnt = $("#emoney-table .emoney-item").length;

	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {

				if(res != null) {
					makeLogEmoneyList(res);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeLogEmoneyList(log_emoney_data) {

	if(log_emoney_data.length > 0) {

		var add_html = "";

		for(var i=0; i<log_emoney_data.length; i++) {

			add_html+= '<tr class="emoney-item">';
			add_html+= '	<td class="left first">'+log_emoney_data[i].memo+'</td>';

			if(log_emoney_data[i].emoney > 0) {
				add_html+= '	<td class="right">'+log_emoney_data[i].emoney+'��</td>';
			}
			else {
				add_html+= '	<td class="right"></td>';
			}

			if(log_emoney_data[i].emoney < 0) {
				add_html+= '	<td class="right"></td>';
			}
			else {
				add_html+= '	<td class="right">'+log_emoney_data[i].emoney+'��</td>';
			}

			add_html+= '</tr>';
		}
	}

	$("#emoney-table").append(add_html);

	if(log_emoney_data.length < 10) {
		$(".more-btn").hide();
	}
}


function getMemberQnaData() {

	var data_param = "mode=get_member_qna";
	var item_cnt = $("#member-qna-table .title").length;

	data_param += "&item_cnt=" + item_cnt;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res != null) {
					makeMemberQnaList(res);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeMemberQnaList(member_qna_data) {


	if(member_qna_data.length > 0) {

		var add_html = "";

		for(var i=0; i<member_qna_data.length; i++) {
			add_html+= '<tr class="title" onclick="view_content(this, '+member_qna_data[i].sno+');">';
			add_html+= '	<td class="first">'+member_qna_data[i].idx+'</td>';
			add_html+= '	<td class="">'+member_qna_data[i].itemcd+'</td>';
			add_html+= '	<td class="left last">';
			add_html+= '		<div class="answer-yn">';

			if(member_qna_data[i].reply_cnt > 0) {
				add_html+= '<div class="answer-y"></div>';
			}
			else {
				add_html+= '<div class="answer-n"></div>';
			}

			add_html+= '</div>';
			add_html+= '		<div>'+member_qna_data[i].subject+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
			add_html+= '<tr class="content-board" id="content-'+member_qna_data[i].sno+'">';
			add_html+= '	<td colspan=3 class="">';

			add_html+= '	<div class="content-review">';
			add_html+= '		<div class="question-icon"></div>';

			if(member_qna_data[i].ordno >0) {
				add_html+= ' �ֹ���ȣ : '+member_qna_data[i].ordno + '<br />';
			}

			add_html+= member_qna_data[i].contents;

			add_html+= '	</div>';

			for(var j=0; j<member_qna_data[i].reply.length; j++) {
				add_html+= '			<div class="content-reply">';
				add_html+= '				<div class="answer-icon"></div>';
				if(member_qna_data[i].reply[j].subject) {
					add_html+= member_qna_data[i].reply[j].subject +'<br />';
				}

				add_html+= member_qna_data[i].reply[j].contents;
				add_html+= '			</div>';
			}

			add_html+= '		</td>';
			add_html+= '	</tr>';

		}
	}

	$("#member-qna-table").append(add_html);

	if(member_qna_data.length < 10) {
		$(".more-btn").hide();
	}


}

function getGoodsReviewData() {


	var data_param = "mode=get_review";
	var item_cnt = $("#review-table .title").length;

	data_param += "&item_cnt=" + item_cnt + "&all=true";

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {

				if(res != null) {
					makeGoodsReviewList(res);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeGoodsReviewList(review_data) {


	if(review_data.length > 0) {

		var add_html = "";

		for(var i=0; i<review_data.length; i++) {
			add_html+= '<tr class="title" onclick="view_content(this, '+review_data[i].sno+');">';
			add_html+= '	<td class="first img" data-goodsno="' + review_data[i].goodsno + '">'+review_data[i].img_html+'</td>';
			add_html+= '	<td class="left">';
			add_html+= '		<div class="point-star">';

			for(var k=0; k<5; k++) {
				if(k < review_data[i].point) {
					add_html+= '<span class="active">��</span>';
				}
				else {
					add_html+= '��';
				}
			}

			add_html+= '</div>';
			add_html+= '		<div>'+review_data[i].subject+'</div>';
			add_html+= '		<div>'+review_data[i].review_name+' | '+review_data[i].regdt.split(" ")[0].replace(/\-/g, ".")+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
			add_html+= '<tr class="content-board" id="content-'+review_data[i].sno+'">';
			add_html+= '	<td colspan=3 class="">';
			add_html+= '		<div class="content-review">'+review_data[i].contents;

			if(review_data[i].image) {
				add_html+= '<div>'+review_data[i].image+'</div>';
			}

			if(review_data[i].authdelete === 'Y') {
				add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_review\', \''+review_data[i].m_no+ '\', '+review_data[i].sno+' );"><div class="del-btn">�� ��</div></a>';
			}

			for(var j=0; j<review_data[i].reply.length; j++) {
				add_html+= '			<div class="content-reply">';
				add_html+= '				<div class="reply-icon"></div>';
				if(review_data[i].reply[j].subject) {
					add_html+= review_data[i].reply[j].subject +'<br />';
				}

				add_html+= review_data[i].reply[j].contents;
				if(review_data[i].reply[j].authdelete === 'Y') {
					add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_review\', \''+review_data[i].reply[j].m_no+'\', '+review_data[i].reply[j].sno+' );"><div class="del-btn">�� ��</div></a>';
				}
				add_html+= '			</div>';
			}

			add_html+= '		</div>';
			add_html+= '		</td>';
			add_html+= '	</tr>';

		}
	}

	$("#review-table").append(add_html);

	$("#review-table .title .first.img").unbind("click").click(function(event){
		var goodsno = this.getAttribute("data-goodsno");
		if (parseInt(goodsno) > 0) {
			location.href = "./view.php?goodsno=" + goodsno;
			event.stopPropagation();
		}
	});

	if(review_data.length < 10) {
		$(".more-btn").hide();
	}


}

function getQnaM2Data(isAll) {
	var data_param = "mode=get_qna";
	var item_cnt = $("#qna-table .title").length;

	var goodsno = "";

	if($("[name=goodsno]").length > 0) {
		goodsno = $("[name=goodsno]").val();
	}

	data_param += "&item_cnt=" + item_cnt + "&goodsno="+goodsno+'&isAll='+isAll;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res != null) {
					makeQnaM2List(res, goodsno);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeQnaM2List(qna_data, goodsno) {
	if(qna_data.length > 0) {

		var add_html = "";

		for(var i=0; i<qna_data.length; i++) {
			add_html+= '<tr class="title" onclick="view_content(this, '+qna_data[i].sno+');">';
			add_html+= '	<td class="first img" data-goodsno='+qna_data[i].goodsno+'" >'+qna_data[i].img_html+'</td>';
			add_html+= '	<td class="left last" >';
			add_html+= '		<div class="answer-yn">';

			if(qna_data[i].reply_cnt > 0) {
				add_html+= '<div class="answer-y"></div>';
			}
			else {
				add_html+= '<div class="answer-n"></div>';
			}

			add_html+= '</div>';

			add_html+= '		<div style="float:left">'+qna_data[i].subject+'</div>';

			if(qna_data[i].secret == 1){
				add_html+= "<div class='secret-icon' style='float:left'></div>";
			}
			add_html+= '<div style="clear:both"></div>';

			add_html+= '		<div>'+qna_data[i].qna_name+' | '+qna_data[i].regdt.split(" ")[0].replace(/\-/g, ".")+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
			add_html+= '<tr class="content-board" id="content-'+qna_data[i].sno+'">';
			add_html+= '	<td colspan=2 class="">';

			if(qna_data[i].accessable) {
				add_html+= '	<div class="content-qna">';
				add_html+= '		<div class="question-icon"></div>' + qna_data[i].contents;
				if(qna_data[i].authdelete === 'Y') {
					add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_qna\', \''+qna_data[i].m_no+'\', '+qna_data[i].sno+' );"><div class="del-btn">�� ��</div></a>';
				}
				add_html+= '	</div>';

				for(var j=0; j<qna_data[i].reply.length; j++) {
					add_html+= '			<div class="content-reply">';
					add_html+= '				<div class="answer-icon"></div>';
					if(qna_data[i].reply[j].subject) {
						add_html+= qna_data[i].reply[j].subject +'<br /><br />';
					}

					add_html+= qna_data[i].reply[j].contents;
					if(qna_data[i].reply[j].authdelete === 'Y') {
					add_html+= '	<a href="javascript:;" onclick="delete_qnaReview( \'del_qna\', \''+qna_data[i].reply[j].m_no+'\', '+qna_data[i].reply[j].sno+' );"><div class="del-btn">�� ��</div></a>';
					}
					add_html+= '			</div>';
				}
			}
			else {

				add_html+= '	<div class="content-qna">';
				if(qna_data[i].m_no > 0) {
					add_html+= '		��б� �Դϴ�.';
				}
				else {
					add_html+= '		��й�ȣ : <input type="password" id="goods-qna-password-'+qna_data[i].sno+'" name="password" required="required"/><button type="button" data-sno="'+qna_data[i].sno+'"  class="goods-qna-certification">Ȯ��</button>';
				}
				add_html+= '</div>';

			}
			add_html+= '		</td>';
			add_html+= '	</tr>';

		}
	}

	$("#qna-table").append(add_html);

	$("#qna-table .title .first.img").unbind("click").click(function(event){
		var goodsno = this.getAttribute("data-goodsno");
		if (parseInt(goodsno) > 0) {
			location.href = "./view.php?goodsno=" + goodsno;
			event.stopPropagation();
		}
	});

	$(".goods-qna-certification").unbind("click").click(function(event){
		var $this = $(this), sno = $this.attr("data-sno"), password = $("#goods-qna-password-"+sno).val();
		if (!password) {
			alert("��й�ȣ�� �Է����ּ���.");
			return false;
		}
		$.ajax({
			"url" : "ajaxAction.php",
			"type" : "post",
			"data" : "sno="+sno+"&password="+$("#goods-qna-password-"+sno).val()+"&mode=getGoodsQna",
			"dataType" : "json",
			"success" : function(responseData)
			{
				if (!responseData || !responseData.contents) {
					alert("��й�ȣ�� ��ġ���� �ʽ��ϴ�.");
				}
				else {
					var add_html = '';
					add_html +='<div class="content-qna">';
					add_html +='<div class="question-icon"></div>'+responseData.contents+'</div>';

					for(var i=0; i<responseData.reply.length; i++) {
						add_html+= '			<div class="content-reply"><div class="answer-icon"></div>';
						add_html+= '				<div class="reply-icon"></div>';
						if(responseData.reply[i].subject) {
							add_html+= responseData.reply[i].subject +'<br /><br />';
						}
						add_html+= responseData.reply[i].contents;
						add_html+= '			</div>';
					}

					$this.parent().parent().html(add_html);
				}
			}
		});
		return false;
	});

	if(qna_data.length < 10) {
		$(".more-btn").hide();
	}
}

function getBoardData(skin, id, search, isComment, isSubSpeech)
{
	var data_param = "mode=get_board";
	var item_cnt = $("#board-table .title").length;

	data_param += "&item_cnt=" + item_cnt + "&id="+id;
	if(search!=''){
		data_param += "&search[word]="+search;
	}

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {

				if(res != null) {
					if(skin == 'gallery'){
						makeBoardGalleryList(res, id, isComment, isSubSpeech);
					}
					else{
						makeBoardDefaultList(res, id, isComment, isSubSpeech);
					}
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeBoardDefaultList(data, id, isComment , isSubSpeech) {
	if(data.length > 0) {
		var add_html = "";

		for(var i=0; i<data.length; i++) {
			add_html+= '<tr class="data-row" onclick="viewContent(\''+data[i].viewUrl+'\',\''+data[i].secret+'\',\''+data[i].m_no+'\',\''+data[i]._member+'\')" >';
			add_html+= '<td class="title"><div class="data-box">';
			add_html+= '<div class="bullet"></div>';
			add_html+= '<div class="subject">';

			if (data[i].sub!='' && parseInt(data[i].sub) >0 )
			{
				add_html+= data[i].gapReply+'<div class="icon-reply"></div>';
			}

			if (data[i].secret == 'o')
			{
				add_html+= '<div class="icon-secret"></div>';
			}

			add_html+='<div class="subject-text screen-width"><b>';
			if (isSubSpeech !='' &&  data[i].category!='')
			{
				add_html+= '['+data[i].category+']';
			}
			add_html+=data[i].subject+'</b>';

			if (isComment == 'on' && parseInt(data[i].comment) > 0 )
			{
				add_html+= '['+data[i].comment+']';
			}
			add_html+='</div>';

			if (data[i].new == 'y')
			{
				add_html+= '<div class="icon-new"></div>';
			}

			if (data[i].hot == 'y')
			{
				add_html+= '<div class="icon-hot"></div>';
			}
			add_html+='<div style="clear:both"></div>';
			add_html+='</div>';
			add_html+= '<div class="etc">'+data[i].name+' | '+data[i].regdt.split(" ")[0].replace(/\-/g, ".")+'</div>';
			add_html+='</div>';
			add_html+= '</td></tr>';
		}
	}

	$("#board-table").append(add_html);
	if(data.length < 10) {
		$(".more-btn").hide();
	}
}

function makeBoardGalleryList(data, id, isComment, isSubSpeech) {
	if(data.length > 0) {
		var add_html = "<tr>";

		for(var i=0; i<data.length; i++) {
			add_html+= '<td class="title" align="center" valign="top" width="50%">';
			add_html+= '<div class="box" onclick="viewContent(\''+data[i].viewUrl+'\',\''+data[i].secret+'\',\''+data[i].m_no+'\',\''+data[i]._member+'\')" >';
			add_html+= '<div style="width:'+data[i].imgSizeW+'px;height:'+data[i].imgSizeH+'px;background:url(\''+data[i].imgUrl+'\') no-repeat center center;background-size:100%;"></div>';
			add_html+= '<div class="subject screen-width">';
			if (isSubSpeech !='' &&  data[i].category!='')
			{
				add_html+= '['+data[i].category+']';
			}
			add_html+= data[i].subject;
			add_html+= '</div>';
			add_html+= '</div></td>';

			if ( parseInt((i+1) % 2) == 0)
			{
				add_html+= '</tr><tr align="center">';
			}
		}
	}

	add_html+= '<td colspan="2" height="1"></td></tr>';
	$("#board-table").append(add_html);
	if(data.length < 10) {
		$(".more-btn").hide();
	}
}
function addOnloadEvent(fnc)
{
	if ( typeof window.addEventListener != "undefined" )
		window.addEventListener( "load", fnc, false );
	else if ( typeof window.attachEvent != "undefined" ) {
		window.attachEvent( "onload", fnc );
	}
	else {
		if ( window.onload != null ) {
			var oldOnload = window.onload;
			window.onload = function ( e ) {
				oldOnload( e );
				window[fnc]();
			};
		}
		else window.onload = fnc;
	}
}
function setGoodsImageSoldoutMask() {

	function _getSize(el) {

		var size = {};

		size = {'height':el.clientHeight,'width':el.clientWidth};

		// el �� �θ� ��ü�� display = block ��ü�� �ֳ� üũ
		if (size.height == 0) {
			var p;
			for (p=el.parentNode; p.style.display != 'none'; p=p.parentNode);
			p.style.display = 'block';
			size = {'height':el.clientHeight,'width':el.clientWidth};
			p.style.display = 'none';
		}

		return size;

	}

	var i, css, s_img = document.getElementsByTagName('IMG');
	var mask = document.getElementById('el-goods-soldout-image-mask');
	if (!mask) return;

	var div, a, img, size;

	for (i in s_img) {
		img = s_img[i];
		css = img.className;

		if (css && css.indexOf('el-goods-soldout-image') > -1) {

			for (a=img.parentNode; a.nodeType !== 1 && a.tagName !== 'A'; a=a.parentNode);
			for (div=a.parentNode; div.nodeType !== 1 && div.tagName !== 'DIV'; div=div.parentNode);

			if(typeof a.firstChild.id != 'undefined' && a.firstChild.id.indexOf('mask_') > -1 ){ continue; }

			var _mask = mask.cloneNode(true);
			_mask.id = _mask.id + '_' + i;

			size = _getSize(img);

			_mask.style.height = size.height+"px";
			_mask.style.width = size.width+"px";

			_mask.style.display = 'block';
			div.style.position = 'relative';

			a.insertBefore(_mask,img);
		}
	}
}

function getCheckoutReviewData() {

	var data_param = "mode=get_checkout_review";
	var item_cnt = ($("#review-table .title .non-notice").length) ? $("#review-table .title .non-notice").length : $("#review-table .title").length;

	var goodsno = "";
	if($("[name=goodsno]").length > 0) {
		goodsno = $("[name=goodsno]").val();
	}

	data_param += "&item_cnt=" + item_cnt + "&goodsno="+goodsno;

	try {
		$.ajax({
			type: "post",
			url: "/"+ mobile_root + "/proc/mAjaxAction.php",
			cache:false,
			async:false,
			data: data_param,
			success: function (res) {
				if(res != null) {
					makeCheckoutReviewList(res);
				}
				else {
					$(".more-btn").hide();
				}
			},
			dataType:"json"
		});
	}
	catch(e) {
		alert(e);
	}
}

function makeCheckoutReviewList(review_data) {

	if(review_data.length > 0) {
		var add_html = "";

		for(var i=0; i<review_data.length; i++) {
			add_html+= '<tr class="title">';
			if(review_data[i].idx) {
				add_html+= '	<td class="first">'+review_data[i].idx+'</td>';
				add_html+= '	<td class="img">'+review_data[i].img_html+'</td>';
			} 
			else {
				add_html+= '	<td class="first img non-notice" data-goodsno="'+ review_data[i].ProductID + '">'+review_data[i].img_html+'</td>';
			}
			add_html+= '	<td class="left">';
			add_html+= '		<div class="point-star">'+review_data[i].PurchaseReviewScore+'</div>';
			add_html+= '		<div style="word-break: break-all;">'+review_data[i].Title+'</div>';
			add_html+= '		<div><img src='+review_data[i].npayImg+'>'+' 네이버 페이 구매자'+' | '+review_data[i].CreateYmdt.split(" ")[0].replace(/\-/g, ".")+'</div>';
			add_html+= '		</td>';
			add_html+= '</tr>';
		}
	}
	$("#review-table").append(add_html);

	$("#review-table .title .first.img").unbind("click").click(function(event){
		var goodsno = this.getAttribute("data-goodsno");
		if (parseInt(goodsno) > 0) {
			location.href = "./view.php?goodsno=" + goodsno;
			event.stopPropagation();
		}
	});

	if(review_data.length < 10) {
		$(".more-btn").hide();
	}

}
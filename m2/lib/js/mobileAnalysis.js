$(document).ready(function() {
	Analysis.mobileAnalysis();
});


var Analysis = {
	action_url: "/"+mobile_root+"/proc/mAjaxAction.php",
	c_division: {},
	p_division: {},
	mobileAnalysis: function() {
		
		Analysis.getIpAddress();
	},
	getIpAddress: function() {
		var data_param = "mode=get_ipaddress";
		var ip_data;

		$.ajax({ 
			type : "post",
			url : Analysis.action_url,
			cache:false,
			async:true,
			data: data_param,
			success: function (res) {
				
				ip_data = res;
				Analysis.retIpData(ip_data);
			},
			dataType:"json"
		});
	},
	retIpData: function(obj) {

		var bool_compare = false;
		var bool_set = true;
	
		if(getCookieMobile('e_mvisit_idx') != null && getCookieMobile('e_mvisit_idx') != "undefined") {
			
			Analysis.c_division.e_ndate = strDecrypt(getCookieMobile('e_ndate'));
			Analysis.c_division.e_ip = strDecrypt(getCookieMobile('e_ip'));
			Analysis.c_division.e_device = strDecrypt(getCookieMobile('e_device'));
			Analysis.c_division.e_url = strDecrypt(getCookieMobile('e_url'));
			Analysis.c_division.mvisit_idx = strDecrypt(getCookieMobile('e_mvisit_idx'));
			Analysis.c_division.top_mvisit_idx = strDecrypt(getCookieMobile('e_top_mvisit_idx'));
			bool_compare = true;

			
		}
	
		Analysis.p_division.visit_date = obj.e_ndate;
		Analysis.p_division.visit_ip = obj.e_ip;
		Analysis.p_division.visit_device = obj.e_device;
		Analysis.p_division.visit_uri = document.location.pathname;
		
		if(Analysis.c_division.e_ip == Analysis.p_division.visit_ip && Analysis.c_division.e_url == Analysis.p_division.visit_uri ) {
			bool_set = false;
		}
	
		var view_second = 0;
		
		if(bool_compare == true) {
			view_second = timeCalc(Analysis.c_division.e_ndate, Analysis.p_division.visit_date);
			
			Analysis.p_division.view_second = view_second;
			Analysis.p_division.up_mvisit_idx = Analysis.c_division.mvisit_idx;
			
			if(Analysis.c_division.top_mvisit_idx != null && Analysis.c_division.top_mvisit_idx != "undefined") {
				Analysis.p_division.top_mvisit_idx = Analysis.c_division.top_mvisit_idx;
			}
		}

		if(bool_set == true) {
			
			setCookieMobile('e_ndate', null);
			setCookieMobile('e_ip', null);
			setCookieMobile('e_device', null);
			setCookieMobile('e_url', null);
			setCookieMobile('e_mvisit_idx', null);
			setCookieMobile('e_top_mvisit_idx', null);

			setCookieMobile('e_ndate', strEncrypt(Analysis.p_division.visit_date), null, '/');
			setCookieMobile('e_ip', strEncrypt(Analysis.p_division.visit_ip), null, '/');
			setCookieMobile('e_device', strEncrypt(Analysis.p_division.visit_device), null, '/');
			setCookieMobile('e_url', strEncrypt(Analysis.p_division.visit_uri), null, '/');

			if(Analysis.p_division.top_mvisit_idx != null && Analysis.p_division.top_mvisit_idx != "undefined") {
				setCookieMobile('e_top_mvisit_idx', strEncrypt(Analysis.p_division.top_mvisit_idx), null, '/');
			}
			
			
			Analysis.setVisitData(Analysis.p_division);
		}		
	},
	setVisitData: function(obj) {
		obj.mode = 'set_visitdata';
		var data_param = obj;
		$.ajax({ 
			type : "post",
			url : Analysis.action_url,
			cache:false,
			async:true,
			data: data_param,
			success: function (res) {

				var mvisit_idx = res.mvisit_idx;
				setCookieMobile('e_mvisit_idx', strEncrypt(mvisit_idx), null, '/');

				if(getCookieMobile('e_top_mvisit_idx') == null || getCookieMobile('e_top_mvisit_idx') == "undefined" ) {
					setCookieMobile('e_top_mvisit_idx', strEncrypt(mvisit_idx), null, '/');
				}
			},
			dataType:"json"
		});
	},
	setAnalysisData: function(obj) {
		obj.mode = 'set_analysisdata';
		var data_param = obj;
		$.ajax({ 
			type : "post",
			url : Analysis.action_url,
			cache:false,
			async:true,
			data: data_param,
			success: function (res) {
				
			},
			dataType:"json"
		});
	}
	

}

function setCookieMobile(c_name, c_value, c_expire, c_path, c_domain, c_secure, c_raw) {

	var c_option = new Object();

	if(c_expire != null) c_option.expires = c_expire;
	if(c_path != null) c_option.path = c_path;
	if(c_domain != null) c_option.domain = c_domain;
	if(c_secure != null) c_option.secure = c_secure;
	if(c_raw != null) c_option.raw = c_raw;

	$.cookie(c_name, c_value, c_option);
}

function getCookieMobile(c_name) {
	return $.cookie(c_name);
}

function delCookieMobile(c_name, c_path) {
	var c_option = new Object();
	if(c_path != null) c_option.path = c_path;
	$.cookie(c_name, null, c_option);
}

function getDateTimeStr() {
	var n_now = new Date();  

	var n_year = n_now.getFullYear();
	var n_month = n_now.getMonth() + 1;
	var n_date = n_now.getDate();
	var n_hour = n_now.getHours();
	var n_minute = n_now.getMinutes();
	var n_second = n_now.getSeconds();

	var res_now = n_year + "-" + addZero(n_month) + "-" + addZero(n_date) + " " + addZero(n_hour) + ":" + addZero(n_minute) + ":" + addZero(n_second);

	return res_now;
}

function addZero(n) {
	return n < 10 ? "0" + n : n;
}


function strEncrypt(str) {
	var output = new String;
	var temp = new Array();
	var temp2 = new Array();
	var strsize = str.length;

	for(var i=0; i<strsize; i++) {
		rnd = Math.round(Math.random() * 122) + 68;
		temp[i] = str.charCodeAt(i) + rnd;
		temp2[i] = rnd;

		output += String.fromCharCode(temp[i], temp2[i]);
	}

	return output;
}

function strDecrypt(str) {
	
	var output = new String;
	var temp = new Array();
	var temp2 = new Array();
	var strsize = str.length;

	for(var i=0; i<strsize; i++) {
		temp[i] = str.charCodeAt(i)
		temp2[i] = str.charCodeAt(i+1)
	}

	for(var i=0; i<strsize; i=i+2) {
		output += String.fromCharCode(temp[i]-temp2[i]);
	}

	return output;
}

function timeCalc(s_time, e_time) {
	
	var time_s = new Date(s_time.substr(0, 4), s_time.substr(5, 2), s_time.substr(8, 2), s_time.substr(11, 2), s_time.substr(14, 2), s_time.substr(17, 2));
	var time_e = new Date(e_time.substr(0, 4), e_time.substr(5, 2), e_time.substr(8, 2), e_time.substr(11, 2), e_time.substr(14, 2), e_time.substr(17, 2));
	
	var c_time = Math.floor(time_e.getTime() - time_s.getTime()) / 1000;

	return c_time;
}

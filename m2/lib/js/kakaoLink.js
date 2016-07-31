var com = new Object();
com.kakao = new Object();
com.kakao.talk = new Object();

var com = {};
com.kakao = {};
com.kakao.talk = {};

com.kakao.talk.KakaoLink = function(appid, appver, url, msg, appname, metainfo) {
	this.msg = msg;
	this.url = encodeURIComponent(url);
	this.appId = encodeURIComponent(appid);
	this.version = encodeURIComponent(appver);
	this.appname = encodeURIComponent(appname);
	if (typeof metainfo == "undefined") {
		this.type = "link";
	} else {
		this.type = "app";
	}

	this.apiver = "2.0";

	$(document).find("body").append(
				"<iframe id='____kakaolink____'></iframe>");
	$("#____kakaolink____").hide();
	

	try {
		if (isEmptyString(this.appId) || isEmptyString(this.version)
				|| isEmptyString(this.url) || isEmptyString(this.appname)) {
			throw "IllegalArgumentException";
		}
	} catch (e) {
		if (e == "IllegalArgumentException") {
			// error
		}
	}

	var sb = new com.kakao.talk.StringBuilder("kakaolink://sendurl?");
	sb.append("appid=").append(this.appId).append("&appver=").append(
			this.version).append("&url=").append(this.url).append("&type=")
			.append(this.type).append("&apiver=").append(this.apiver).append(
					"&appname=").append(this.appname);
	if (!this.isEmptyString(this.msg)) {
		sb.append("&msg=").append(this.msg);
	}

	if (typeof metainfo != "undefined") {
		sb.append("&metainfo=").append(JSON.KakaoStringify(metainfo));
	}
	this.data = sb.toString();
};

com.kakao.talk.StringBuilder = function(value) {
	this.strings = new Array("");
	this.append(value);
};
com.kakao.talk.StringBuilder.prototype.append = function(value) {
	if (value) {
		this.strings.push(value);
	}
	return this;
};
com.kakao.talk.StringBuilder.prototype.toString = function() {
	return this.strings.join("");
};

com.kakao.talk.KakaoLink.prototype.isEmptyString = function(str) {
	if (str.replace(/^\s*/, "").replace(/\s*$/, "").length == 0)
		return true;
	return false;
};

com.kakao.talk.KakaoLink.prototype.getData = function() {
	return this.data;
};

com.kakao.talk.KakaoLink.prototype.execute = function(callback) {
	var uagent	= navigator.userAgent.toLocaleLowerCase();
	var osBrowser		= "";

	if (uagent.search("android") > -1) {
        osBrowser = "android";
        if (uagent.search("chrome") > -1) {
            osBrowser = "android+chrome";
        }
    } else if (uagent.search("iphone") > -1 || uagent.search("ipod") > -1 || uagent.search("ipad") > -1) {
        osBrowser = "ios";
    }

	var clickedAt = +new Date;
	setTimeout(
			function() {
				if (+new Date - clickedAt < 2000) {					
					// android, iphone not installed kakaotalk
					if (typeof callback == 'function') {
						callback.call(this);
					} else if ( osBrowser == "android" ) {
						$("#____kakaolink____").attr("src",
								"market://details?id=com.kakao.talk");
					} else if ( osBrowser == "ios" ) {
						window.location = "http://itunes.apple.com/app/id362057947";
					}
				}
			}, 500);

	if ( osBrowser == "android+chrome" ) {
		window.location = "intent:" + this.data + "#Intent;package=com.kakao.talk;end;";
    } else {
		$("#____kakaolink____").attr("src", this.data);
	}
};

function MetaInfo(metainfo) {
	this.metainfo = metainfo;
}

JSON.KakaoStringify = JSON.KakaoStringify || function(obj) {
	var t = typeof (obj);
	if (t != "object" || obj === null) {
		// simple data type
		if (t == "string")
			obj = '"' + obj + '"';
		return String(obj);
	} else {
		// recurse array or object
		var n, v, json = [], arr = (obj && obj.constructor == Array);
		for (n in obj) {
			v = obj[n];
			t = typeof (v);
			if (t == "string")
				v = '"' + v + '"';
			else if (t == "object" && v !== null)
				v = JSON.KakaoStringify(v);
			json.push((arr ? "" : '"' + n + '":') + String(v));
		}
		return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
	}
};


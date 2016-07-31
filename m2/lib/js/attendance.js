/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var AttendancePopup = function()
{

	var self = this;
	var $popup = jQuery("#attendance-popup");
	var $mask = jQuery(document.createElement("section")).attr("id", "attendance-mask").css("display", "none");
	var $dateFloor = jQuery("#attendance-date-list").find(".attendance-calendar-date-floor");
	var $dateContainer = $dateFloor.parent();
	var attendanceNo;
	var year, month, day;
	var timeout;

	jQuery(window).load(function(){
		$mask.css("height", jQuery(document).height());
	});

	jQuery("#attendance-calendar-previous-month").click(function(){
		var _month = parseInt(month, 10);
		var _year = parseInt(year, 10);
		if (_month === 1) {
			self.load({"attendanceNo" : attendanceNo, "year" : _year - 1, "month" : 12});
		}
		else {
			self.load({"attendanceNo" : attendanceNo, "year" : _year, "month" : _month - 1});
		}
		self.clearTimeoutClose();
	});

	jQuery("#attendance-calendar-next-month").click(function(){
		var _month = parseInt(month, 10);
		var _year = parseInt(year, 10);
		if (_month === 12) {
			self.load({"attendanceNo" : attendanceNo, "year" : _year + 1, "month" : 1});
		}
		else {
			self.load({"attendanceNo" : attendanceNo, "year" : _year, "month" : _month + 1});
		}
		self.clearTimeoutClose();
	});

	this.getMobileShopRootDir = function()
	{
		return $popup.attr("data-root-dir");
	};

	this.load = function(param)
	{
		jQuery.ajax({
			"url" : self.getMobileShopRootDir() + "/proc/attendance_calendar.php",
			"type" : "post",
			"data" : param ? param : "",
			"dataType" : "json",
			"success" : function(attendanceData)
			{
				if (attendanceData) {
					year = attendanceData.calendar.year;
					month = attendanceData.calendar.month;
					day = parseInt(attendanceData.calendar.day, 10);
					attendanceNo = attendanceData.attendanceNo;
					self.renderContent(attendanceData.attendance);
					self.renderCalendar(attendanceData.calendar);
					self.setTimeoutClose(10);
					self.open();
				}
			}
		});
	};

	this.open = function()
	{
		$popup.css("display", "block");
		$mask.css("display", "block");
		$mask.css("height", jQuery(document).height());
	};

	this.close = function()
	{
		$popup.fadeOut();
		$mask.fadeOut();
	};

	this.renderContent = function(attendance)
	{
		$mask.appendTo(document.body)
		jQuery("#attendance-title").text(attendance.name);
		jQuery("#attendance-close").click(function(){
			self.close();
		});
		jQuery("#attendance-info-" + attendance.condition_type).addClass("active");
		jQuery(".attendance-start-date").text(attendance.start_date);
		jQuery(".attendance-end-date").text(attendance.end_date);
		jQuery(".attendance-condition-period").text(attendance.condition_period);
	};

	this.renderCalendar = function(calendar)
	{
		jQuery("#attendance-calendar-month").children().removeClass("current");
		jQuery("#attendance-calendar-month-" + parseInt(calendar.month, 10).toString()).addClass("current");
		jQuery("#attendance-calendar-year").text(calendar.year);
		$dateContainer.empty();
		for (var weekNum in calendar.dateList) {
			var week = calendar.dateList[weekNum];
			var $_dateFloor = $dateFloor.clone();
			var $dateSpace = $_dateFloor.find(".attendance-calendar-date-space");
			$dateContainer.append($_dateFloor);
			for (var weekdayKey in week) {
				var day = week[weekdayKey];
				if (day) {
					var $calendarDate = jQuery(document.createElement("div")).addClass("attendance-calendar-date").text(parseInt(day, 10));
					jQuery($dateSpace[weekdayKey]).addClass("normal").append($calendarDate);
					if (calendar.checkedDateList[day]) {
						jQuery($dateSpace[weekdayKey]).addClass("stamp");
					}
					if (calendar.current === year.toString() + month.toString() + day.toString()) {
						jQuery($dateSpace[weekdayKey]).addClass("today");
					}
				}
				else {
					jQuery($dateSpace[weekdayKey]).addClass("void");
				}
			}
		}
	};

	this.setTimeoutClose = function(second)
	{
		if (!timeout) timeout = setTimeout(this.close, second * 1000);
	};

	this.clearTimeoutClose = function()
	{
		clearTimeout(timeout);
	};

};

jQuery(function(){
	var attendancePopup = new AttendancePopup();
	attendancePopup.load();
});
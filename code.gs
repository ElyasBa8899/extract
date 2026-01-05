// *************************************************************
// سیستم حقوق و دستمزد پیشرفته - نسخه سفارشی (Strict Logic)
// *************************************************************

// تنظیمات سخت‌گیرانه شیفت کاری
const PENALTY_MULTIPLIER = 3; // جریمه ۳ برابری
const OVERTIME_MULTIPLIER = 1.4; // ضریب ۱.۴ برای کل محاسبات

function doGet() {
  return HtmlService.createTemplateFromFile('Index')
    .evaluate()
    .setTitle('سامانه مدیریت تردد پیشرفته')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

function setupSheet() {
  setupWorkWeekSettings();
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  // 1. Users Sheet (Simplified)
  if (!ss.getSheetByName("Users")) {
    var u = ss.insertSheet("Users");
    // New structure: TotalMonthlySalary and ThursdayHours are added
    u.appendRow(["ID", "Name", "Username", "Password", "Role", "DailyHours", "ThursdayHours", "TotalMonthlySalary", "Shift1Start", "Shift2Start"]);
    u.appendRow([1, "مدیر سیستم", "admin", "123", "admin", 8, 4, 10000000, "08:30", "17:00"]); // Example values
  }

  // 2. Logs
  if (!ss.getSheetByName("Logs")) {
    ss.insertSheet("Logs").appendRow(["ID", "Name", "Action", "Timestamp", "JalaliDate", "Time", "Note", "IsSystem"]);
  }

  // 3. Notifications
  if (!ss.getSheetByName("Notifications")) {
    ss.insertSheet("Notifications").appendRow(["Date", "Message", "Status"]);
  }

  // 4. Holidays
  if (!ss.getSheetByName("Holidays")) {
    ss.insertSheet("Holidays").appendRow(["Date", "Description"]);
  }

  // 5. Settings
  if (!ss.getSheetByName("Settings")) {
    var s = ss.insertSheet("Settings");
    s.appendRow(["YearMonth", "DaysCount"]);
    s.getRange("A:A").setNumberFormat("@");
  }

  // SalaryConfig and LeaveRequests sheets are intentionally removed to simplify the system.
}

// --- توابع محاسباتی هسته (Core Logic) ---

function getMonthlyReportCalc(userId, year, month) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var user = getUserById(userId);
  if (!user) throw new Error("User not found");

  // CRITICAL BUG FIX: Use getDisplayValues() to get time as a string, not a Date object.
  var logsSheet = ss.getSheetByName("Logs");
  var logsDisplay = logsSheet.getDataRange().getDisplayValues(); // Use display values for time strings
  var logsRaw = logsSheet.getDataRange().getValues(); // Use raw values for dates

  var holidays = getHolidaysList();
  var workWeekSettings = getWorkWeekSettings();
  var dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
  var targetYM = year + "/" + month;
  var daysData = {};

  // Process logs into a daily structure
  for (var i = 1; i < logsRaw.length; i++) {
    var d = String(logsDisplay[i][4]).split(' ')[0].trim();
    if (String(logsRaw[i][0]) == String(userId) && d.startsWith(targetYM)) {
      if (!daysData[d]) daysData[d] = { logs: [] };
      daysData[d].logs.push({
        act: logsRaw[i][2],
        ts: new Date(logsRaw[i][3]).getTime(),
        timeStr: logsDisplay[i][5], // HH:mm:ss from display values
        isAuto: logsDisplay[i][7]
      });
    }
  }

  var summary = [];
  var totWork = 0;
  var countDelay = 0;
  var countAbsence = 0;
  var totalPenaltyMins = 0;
  var totalMonthTargetMins = 0; // Will be calculated dynamically

  var daysInMonth = new Date(year, month, 0).getDate();

  for (var d = 1; d <= daysInMonth; d++) {
    var dStr = (d < 10) ? "0" + d : d;
    var k = targetYM + "/" + dStr;
    var isH = holidays.some(h => h.trim() == k);
    var currentDate = jalaliToGregorian(year, month, dStr);
    var dayOfWeek = currentDate.getDay(); // 0=Sun, 5=Fri

    var dayTarget = 0;
    // Fridays (assuming weekend) and holidays have 0 target hours
    if (!isH && dayOfWeek !== 5) { // 5 is Friday
      var dayName = dayNames[dayOfWeek];
      var dayStatus = workWeekSettings[dayName];
      if (dayStatus === "Full Day") {
        dayTarget = (user.dailyHours || 0) * 60;
      } else if (dayStatus === "Half Day") {
        dayTarget = (user.thursdayHours || 0) * 60;
      }
    }
    totalMonthTargetMins += dayTarget;

    var items = daysData[k] ? daysData[k].logs : [];
    var workMs = 0, leaveMs = 0, dailyDelayMins = 0, missingExit = false;

    if (items.length > 0) {
      items.sort((a, b) => a.ts - b.ts);
      var lastTime = null, state = 'OUT';
      items.forEach(function(l) {
        if (l.act == 'Entry') {
          if (state == 'OUT') {
            lastTime = l.ts;
            state = 'IN';
            dailyDelayMins += calculateStrictDelay(l.timeStr, user.shift1Start, user.shift2Start);
          }
        } else if (state == 'IN') {
          if (l.act == 'Exit') { if (lastTime) workMs += (l.ts - lastTime); state = 'OUT'; lastTime = null; }
          else if (l.act == 'LeaveStart') { if (lastTime) workMs += (l.ts - lastTime); state = 'LEAVE'; lastTime = l.ts; }
        } else if (state == 'LEAVE') {
          if (l.act == 'LeaveEnd') { if (lastTime) leaveMs += (l.ts - lastTime); state = 'IN'; lastTime = l.ts; }
          else if (l.act == 'Exit') { if (lastTime) leaveMs += (l.ts - lastTime); state = 'OUT'; lastTime = null; }
        }
      });
      if (state == 'IN' || state == 'LEAVE') missingExit = true;
    }

    var wMin = Math.floor(workMs / 60000);
    totWork += wMin;
    var statusText = "", isAbsent = false;

    if (items.length === 0 && dayTarget > 0) {
      isAbsent = true;
      statusText = "غیبت";
      countAbsence++;
      totalPenaltyMins += (dayTarget * PENALTY_MULTIPLIER);
    } else {
      if (dailyDelayMins > 0) {
        statusText = "تاخیر: " + dailyDelayMins + "دقیقه";
        countDelay++;
        totalPenaltyMins += (dailyDelayMins * PENALTY_MULTIPLIER);
      } else {
        statusText = "تکمیل";
      }
    }

    if (isH) statusText = "تعطیل رسمی";
    if (dayOfWeek === 5 && !isH) statusText = "روز تعطیل";
    if (missingExit) statusText = "⚠️ عدم خروج";

    summary.push({
      date: k,
      work: fmt(wMin),
      delay: dailyDelayMins,
      penalty: fmt(isAbsent ? (dayTarget * 3) : (dailyDelayMins * 3)),
      status: statusText
    });
  }

  // --- Final Salary Calculation (Simplified Model) ---
  var perMinuteRate = 0;
  if (totalMonthTargetMins > 0) {
    perMinuteRate = (user.totalMonthlySalary || 0) / totalMonthTargetMins;
  }

  var rawDifference = totWork - totalMonthTargetMins;
  var finalDifference = rawDifference - totalPenaltyMins;

  // Apply the 1.4 multiplier to the final time balance (positive or negative)
  var adjustmentAmount = (finalDifference * OVERTIME_MULTIPLIER) * perMinuteRate;

  var finalPay = (user.totalMonthlySalary || 0) + adjustmentAmount;

  return {
    details: summary.reverse(),
    stats: {
      daysConfig: getMonthDays(year, month),
      totalSalary: Math.round(finalPay).toLocaleString(),
      totalWork: fmt(totWork),
      totalPenalty: fmt(totalPenaltyMins),
      countDelay: countDelay,
      countAbsence: countAbsence,
      netBalance: fmt(Math.abs(finalDifference)),
      netSign: finalDifference >= 0 ? "+" : "-",
      // BUG FIX: Add totalTarget to the return object
      totalTarget: fmt(totalMonthTargetMins)
    }
  };
}

// محاسبه دقیق تاخیر بر اساس شیفت
function calculateStrictDelay(timeStr, shift1Start, shift2Start) {
  // timeStr format: HH:mm:ss
  if (!timeStr) return 0;
  var parts = timeStr.split(':');
  var h = parseInt(parts[0]);
  var m = parseInt(parts[1]);
  var timeMins = h * 60 + m;

  var delay = 0;
  var s1 = shift1Start ? timeToMins(shift1Start) : 0;
  var s2 = shift2Start ? timeToMins(shift2Start) : 0;

  // If there is no second shift, or the entry is before the second shift starts
  if (s2 === 0 || timeMins < s2 - 30) {
    if (s1 > 0 && timeMins > s1) {
      delay = timeMins - s1;
    }
  }
  // If there is a second shift and the entry is after it starts
  else {
    if (s2 > 0 && timeMins > s2) {
      delay = timeMins - s2;
    }
  }

  return delay;
}

function timeToMins(t) {
  var p = t.split(':');
  return parseInt(p[0])*60 + parseInt(p[1]);
}

// Unused function `getSalaryConfig` is removed.

function getUserById(id) {
  var users = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users").getDataRange().getValues();
  for (var i = 1; i < users.length; i++) {
    if (String(users[i][0]) == String(id)) {
      return {
        id: users[i][0],
        name: users[i][1],
        dailyHours: users[i][5],
        thursdayHours: users[i][6],
        totalMonthlySalary: users[i][7],
        shift1Start: users[i][8],
        shift2Start: users[i][9]
      };
    }
  }
  return null;
}

// Unused leave request functions `submitLeaveRequest` and `getMyLeaveRequests` are removed.

// --- Core App Functions ---
function loginUser(u, p) {
  setupSheet();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var users = ss.getSheetByName("Users").getDataRange().getValues();
  for (var i = 1; i < users.length; i++) {
    if (users[i][2] == u && users[i][3] == p) {
      return { success: true, id: users[i][0], name: users[i][1], role: users[i][4], summary: getTodayUserSummary(users[i][0]) };
    }
  }
  throw new Error("اطلاعات ورود اشتباه است");
}

function registerAction(id, name, act) {
  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch (e) { throw new Error("Busy"); }
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Logs");
  var now = new Date();
  var jalali = getJalaliDate(now);
  var time = Utilities.formatDate(now, "Asia/Tehran", "HH:mm:ss");

  var last = getLastStatus(id);
  if (act == 'Entry' && (last == 'Entry' || last == 'LeaveEnd')) { lock.releaseLock(); throw new Error("ورود تکراری"); }
  if (act == 'Exit' && last == 'Exit') { lock.releaseLock(); throw new Error("خروج تکراری"); }

  sheet.appendRow([id, name, act, now, jalali, time, "", "User"]);

  var finalAct = act;
  if (act == 'HourlyLeave') {
    finalAct = (last == 'LeaveStart') ? 'LeaveEnd' : 'LeaveStart';
    sheet.getRange(sheet.getLastRow(), 3).setValue(finalAct);
  }
  SpreadsheetApp.flush(); lock.releaseLock();
  return { success: true, newStatus: finalAct, summary: getTodayUserSummary(id) };
}

function getLastStatus(id) {
  var d = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Logs").getDataRange().getValues();
  for (var i = d.length - 1; i > 0; i--) if (d[i][0] == id) return d[i][2];
  return "Exit";
}

function getTodayUserSummary(userId) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues();
  var now = new Date();
  var jalali = getJalaliDate(now);
  var list = [];
  var lastStatus = "Exit";
  for (var i = 1; i < logs.length; i++) {
    var d = String(logs[i][4]).split(' ')[0].trim();
    if (String(logs[i][0]) == String(userId)) {
      if (d == jalali) list.push({ time: logs[i][5], action: translateAction(logs[i][2]), raw: logs[i][2] });
      lastStatus = logs[i][2];
    }
  }
  return { history: list, status: lastStatus };
}

// --- Date and Formatting Helpers ---
function jalaliToGregorian(j_y, j_m, j_d) {
  j_y = parseInt(j_y); j_m = parseInt(j_m); j_d = parseInt(j_d);
  var jy = j_y - 979;
  var j_day_no = (j_m - 1) * 31;
  if (j_m > 6) j_day_no = 6 * 31 + (j_m - 7) * 30;
  j_day_no = j_day_no + j_d - 1;
  var g_day_no = 365 * jy + parseInt(jy / 33) * 8 + parseInt((jy % 33 + 3) / 4) + 78 + j_day_no;
  var gy = 1600 + 400 * parseInt(g_day_no / 146097);
  g_day_no = g_day_no % 146097;
  var leap = true;
  if (g_day_no >= 36525) { g_day_no--; gy += 100 * parseInt(g_day_no / 36524); g_day_no = g_day_no % 36524; if (g_day_no >= 365) g_day_no++; else leap = false; }
  gy += 4 * parseInt(g_day_no / 1461); g_day_no = g_day_no % 1461;
  if (g_day_no >= 366) { leap = false; g_day_no--; gy += parseInt(g_day_no / 365); g_day_no = g_day_no % 365; }
  var i; for (i = 0; g_day_no >= [31, (gy % 4 == 0 && gy % 100 != 0 || gy % 400 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][i]; i++) { g_day_no -= [31, (gy % 4 == 0 && gy % 100 != 0 || gy % 400 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][i]; }
  return new Date(gy, i, g_day_no + 1, 12, 0, 0);
}

function getJalaliDate(d) {
  var j = new Intl.DateTimeFormat('fa-IR-u-nu-latn', { year: 'numeric', month: '2-digit', day: '2-digit' }).format(d);
  return j;
}

function translateAction(a) { if (a == 'Entry') return 'ورود'; if (a == 'Exit') return 'خروج'; if (a == 'LeaveStart') return 'شروع مرخصی'; return 'پایان مرخصی'; }
function fmt(m) { var h = Math.floor(m / 60); var n = m % 60; return h + ":" + (n < 10 ? "0" + n : n); }

// --- Admin Functions (Updated) ---
function getEmployeesList() {
  var users = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users").getDataRange().getValues();
  var list = [];
  for (var i = 1; i < users.length; i++) {
    list.push({
      id: users[i][0],
      name: users[i][1],
      username: users[i][2],
      password: users[i][3],
      dailyHours: users[i][5],
      thursdayHours: users[i][6],
      totalMonthlySalary: users[i][7],
      shift1Start: users[i][8],
      shift2Start: users[i][9]
    });
  }
  return list;
}

function updateUserInfo(id, name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start) {
  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  var d = s.getDataRange().getValues();
  for (var i = 1; i < d.length; i++) {
    if (String(d[i][0]) == String(id)) {
      s.getRange(i + 1, 2, 1, 9).setValues([[name, username, password, "user", dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start]]);
      return { success: true };
    }
  }
  return { success: false };
}

function adminSaveUser(name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start) {
  SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users").appendRow([new Date().getTime(), name, username, password, 'user', dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start]);
  return { success: true };
}
function adminAddLog(userId, userName, jalaliDateStr, timeStr, action) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Logs");
  try {
    var parts = jalaliDateStr.split('/');
    var timeParts = timeStr.split(':');
    var realDate = jalaliToGregorian(parts[0], parts[1], parts[2]);
    realDate.setHours(parseInt(timeParts[0]), parseInt(timeParts[1]), 0, 0);
    sheet.appendRow([userId, userName, action, realDate, jalaliDateStr, timeStr + ":00", "ثبت دستی مدیر", "Admin"]);
    return { success: true };
  } catch (e) { return { success: false, message: "فرمت اشتباه است" }; }
}
function updateLogTime(r,t) { SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Logs").getRange(r,6).setValue(t); return {success:true}; }
function setMonthDays(y,m,d) { var s=SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Settings"); var k=y+"-"+m; var dt=s.getDataRange().getDisplayValues(); for(var i=1;i<dt.length;i++)if(dt[i][0].trim()==k){s.getRange(i+1,2).setValue(d);return{success:true};} s.appendRow([k,d]); return{success:true}; }
function getMonthDays(y,m) { var s=SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Settings"); if(!s)return 26; var k=y+"-"+m; var d=s.getDataRange().getDisplayValues(); for(var i=1;i<d.length;i++)if(d[i][0].trim()==k)return parseInt(d[i][1]); return 26; }
function getHolidaysList() { var d=SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays").getDataRange().getDisplayValues(); var r=[]; for(var i=1;i<d.length;i++)r.push(d[i][0].trim()); return r; }

function addHolidayToSheet(date) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays");
  sheet.appendRow([date, ""]);
  return { success: true };
}

function removeHolidayFromSheet(date) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (data[i][0] == date) {
      sheet.deleteRow(i + 1);
      return { success: true };
    }
  }
  return { success: false };
}

function setupWorkWeekSettings() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (!ss.getSheetByName("WorkWeekSettings")) {
    var s = ss.insertSheet("WorkWeekSettings");
    s.appendRow(["Day", "Status"]);
    s.appendRow(["Saturday", "Full Day"]);
    s.appendRow(["Sunday", "Full Day"]);
    s.appendRow(["Monday", "Full Day"]);
    s.appendRow(["Tuesday", "Full Day"]);
    s.appendRow(["Wednesday", "Full Day"]);
    s.appendRow(["Thursday", "Half Day"]);
  }
}

function getWorkWeekSettings() {
  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("WorkWeekSettings");
  if (!s) return {};
  var data = s.getDataRange().getValues();
  var settings = {};
  for (var i = 1; i < data.length; i++) {
    settings[data[i][0]] = data[i][1];
  }
  return settings;
}

function saveWorkWeekSettings(settings) {
  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("WorkWeekSettings");
  if (!s) return;
  var data = s.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    var day = data[i][0];
    if (settings[day]) {
      s.getRange(i + 1, 2).setValue(settings[day]);
    }
  }
  return { success: true };
}
function getGroupedLogs(userId, year, month) { var ss = SpreadsheetApp.getActiveSpreadsheet(); var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues(); var targetYM = year + "/" + month; var grouped = {}; for (var i = 1; i < logs.length; i++) { var d = String(logs[i][4]).split(' ')[0].trim(); if (String(logs[i][0]) == String(userId) && d.startsWith(targetYM)) { if(!grouped[d]) grouped[d] = []; grouped[d].push({ row: i+1, actionName: translateAction(logs[i][2]), rawAction: logs[i][2], time: logs[i][5], note: logs[i][6] }); } } var res = []; var keys = Object.keys(grouped).sort().reverse(); keys.forEach(k => { grouped[k].sort((a,b)=>(a.time>b.time)?1:-1); res.push({ date: k, items: grouped[k] }); }); return res; }
function getUnreadNotifications() { var ss = SpreadsheetApp.getActiveSpreadsheet(); var sheet = ss.getSheetByName("Notifications"); if (!sheet) return []; var data = sheet.getDataRange().getValues(); var unreadMessages = []; for (var i = data.length - 1; i > 0; i--) { if (data[i][2] == "Unread") { unreadMessages.push(data[i][1]); } } return unreadMessages; }
function markNotificationsAsRead() { var ss = SpreadsheetApp.getActiveSpreadsheet(); var sheet = ss.getSheetByName("Notifications"); if (!sheet) return; var data = sheet.getDataRange().getValues(); for (var i = 1; i < data.length; i++) { if (data[i][2] == "Unread") { sheet.getRange(i + 1, 3).setValue("Read"); } } }

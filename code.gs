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

  // 1. Users Sheet: Create if it doesn't exist and add a default admin if it's empty.
  var usersSheet = ss.getSheetByName("Users");
  if (!usersSheet) {
    usersSheet = ss.insertSheet("Users");
    usersSheet.appendRow(["ID", "Name", "Username", "Password", "Role", "DailyHours", "ThursdayHours", "TotalMonthlySalary", "Shift1Start", "Shift2Start", "isPartTime"]);
  }

  // Check if the sheet has only the header row (i.e., no actual users)
  if (usersSheet.getLastRow() < 2) {
    usersSheet.appendRow([1, "مدیر سیستم", "admin", "123", "admin", 8, 4, 10000000, "08:30", "17:00", "FALSE"]);
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
  var totalDelay = 0;
  var totalOvertime = 0;

  var daysInMonth = getJalaliDaysInMonth(parseInt(year), parseInt(month));

  for (var d = 1; d <= daysInMonth; d++) {
    var dStr = (d < 10) ? "0" + d : d;
    var k = targetYM + "/" + dStr;
    var isH = holidays.some(h => h.trim() == k);
    var currentDate = jalaliToGregorian(year, month, dStr);
    var dayOfWeek = currentDate.getDay(); // 0=Sun, 5=Fri

    var dayTarget = 0;
    // Fridays (assuming weekend) and holidays have 0 target hours
    if (!isH) {
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
    totalDelay += dailyDelayMins;
    if (wMin > dayTarget) {
      totalOvertime += (wMin - dayTarget);
    }
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

  var finalPay;
  var rawDifference = totWork - totalMonthTargetMins;
  var finalDifference;

  if (user.isPartTime) {
    // Part-time logic: No penalties, simple ratio calculation
    totalPenaltyMins = 0; // Ensure no penalties are applied
    finalDifference = rawDifference; // Net balance is just the raw difference
    if (totalMonthTargetMins > 0) {
      finalPay = (user.totalMonthlySalary || 0) * (totWork / totalMonthTargetMins);
    } else {
      finalPay = 0; // Avoid division by zero if there's no target work time
    }
  } else {
    // Full-time logic (existing logic)
    finalDifference = rawDifference - totalPenaltyMins;

    // Apply the 1.4 multiplier to the final time balance (positive or negative)
    var adjustmentAmount = (finalDifference * OVERTIME_MULTIPLIER) * perMinuteRate;
    finalPay = (user.totalMonthlySalary || 0) + adjustmentAmount;
  }

  return {
    details: summary,
    stats: {
      daysConfig: getMonthDays(year, month),
      totalSalary: Math.round(finalPay).toLocaleString(),
      totalWork: totWork,
      totalPenalty: totalPenaltyMins,
      countDelay: countDelay,
      countAbsence: countAbsence,
      netBalance: Math.abs(finalDifference),
      netSign: finalDifference >= 0 ? "+" : "-",
      totalDelay: totalDelay,
      totalOvertime: totalOvertime,
      totalTarget: totalMonthTargetMins
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
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  if (!sheet) return null;
  var data = sheet.getDataRange().getValues();
  if (data.length < 2) return null;

  var headers = data.shift();
  var headerMap = {};
  headers.forEach((h, i) => { if(h) headerMap[String(h).trim()] = i; });

  const idIndex = headerMap['ID'];
  if (idIndex === undefined) return null; // Can't find anyone without an ID column

  // Handle different naming for salary
  const salaryKey = 'TotalMonthlySalary';
  const fallbackSalaryKey = 'BaseHouryRate';
  if(!headerMap[salaryKey] && headerMap[fallbackSalaryKey]) {
      headerMap[salaryKey] = headerMap[fallbackSalaryKey];
  }

  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    if (String(row[idIndex]) == String(id)) {
      const get = (key) => headerMap[key] !== undefined ? row[headerMap[key]] : undefined;
      return {
        id: get('ID'),
        name: get('Name'),
        dailyHours: get('DailyHours'),
        thursdayHours: get('ThursdayHours'),
        totalMonthlySalary: get(salaryKey),
        shift1Start: get('Shift1Start'),
        shift2Start: get('Shift2Start'),
        isPartTime: get('isPartTime') === true || String(get('isPartTime')).toUpperCase() === 'TRUE'
      };
    }
  }
  return null;
}

// Unused leave request functions `submitLeaveRequest` and `getMyLeaveRequests` are removed.

// --- Core App Functions ---
function loginUser(u, p) {
  setupSheet();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  if (!sheet) throw new Error("User sheet not found.");
  var data = sheet.getDataRange().getValues();
  if (data.length < 2) throw new Error("No user data found.");

  var headers = data.shift();
  var headerMap = {};
  headers.forEach((h, i) => { if(h) headerMap[String(h).trim()] = i; });

  const userIndex = headerMap['Username'];
  const passIndex = headerMap['Password'];
  const idIndex = headerMap['ID'];
  const nameIndex = headerMap['Name'];
  const roleIndex = headerMap['Role'];

  if (userIndex === undefined || passIndex === undefined) {
    throw new Error("Username or Password column not found in Users sheet.");
  }

  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    if (row[userIndex] == u && row[passIndex] == p) {
      const id = idIndex !== undefined ? row[idIndex] : 'N/A';
      const name = nameIndex !== undefined ? row[nameIndex] : 'N/A';
      const role = roleIndex !== undefined ? row[roleIndex] : 'user';
      return { success: true, id: id, name: name, role: role, summary: getTodayUserSummary(id) };
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

function isJalaliLeapYear(year) {
  // The 33-year cycle is a good approximation for Jalali leap years
  const remainder = (year - 474) % 33;
  return [1, 5, 9, 13, 17, 22, 26, 30].indexOf(remainder) !== -1;
}

function getJalaliDaysInMonth(year, month) {
    if (month <= 6) {
        return 31;
    } else if (month <= 11) {
        return 30;
    } else { // Esfand
        return isJalaliLeapYear(year) ? 30 : 29;
    }
}

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
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
    if (!sheet) return [];
    var data = sheet.getDataRange().getValues();
    if (data.length < 2) return [];

    var headers = data.shift();
  var headerMap = {};
  headers.forEach((h, i) => { if(h) headerMap[String(h).trim()] = i; });

  // Handle different naming for salary. User sheet has 'BaseHouryRate'.
  const salaryKey = 'TotalMonthlySalary';
  const fallbackSalaryKey = 'BaseHouryRate';
  if(!headerMap[salaryKey] && headerMap[fallbackSalaryKey]) {
      headerMap[salaryKey] = headerMap[fallbackSalaryKey];
  }

  var list = [];
  data.forEach(row => {
    if (row.join("").trim().length === 0) return; // Skip empty rows
    const get = (key) => headerMap[key] !== undefined ? row[headerMap[key]] : undefined;

    list.push({
      id: get('ID'),
      name: get('Name'),
      username: get('Username'),
      password: get('Password'),
      role: get('Role'),
      dailyHours: get('DailyHours'),
      thursdayHours: get('ThursdayHours'),
      totalMonthlySalary: get(salaryKey),
      shift1Start: get('Shift1Start'),
      shift2Start: get('Shift2Start'),
      isPartTime: get('isPartTime') === true || String(get('isPartTime')).toUpperCase() === 'TRUE'
    });
  });

  return list;
  } catch (e) {
    // Log the error for debugging and return it to the frontend for display.
    console.error("Error in getEmployeesList:", e);
    return { error: true, message: e.message };
  }
}

function updateUserInfo(id, name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start, isPartTime) {
  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  var d = s.getDataRange().getValues();
  for (var i = 1; i < d.length; i++) {
    if (String(d[i][0]) == String(id)) {
      s.getRange(i + 1, 2, 1, 10).setValues([[name, username, password, "user", dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start, isPartTime]]);
      return { success: true };
    }
  }
  return { success: false };
}

function adminSaveUser(name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start, isPartTime) {
  SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users").appendRow([new Date().getTime(), name, username, password, 'user', dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift2Start, isPartTime]);
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
    s.appendRow(["Friday", "Non-Working"]);
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
function getGroupedLogs(userId, year, month) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues();
  var targetYM = year + "/" + month;
  var grouped = {};

  for (var i = 1; i < logs.length; i++) {
    var d = String(logs[i][4]).split(' ')[0].trim();
    if (String(logs[i][0]) == String(userId) && d.startsWith(targetYM)) {
      if (!grouped[d]) grouped[d] = [];
      grouped[d].push({
        row: i + 1,
        actionName: translateAction(logs[i][2]),
        rawAction: logs[i][2],
        time: logs[i][5],
        note: logs[i][6]
      });
    }
  }

  var res = [];
  // Sort keys numerically based on the day, and from latest to earliest date
  var keys = Object.keys(grouped).sort((a, b) => {
    var dayA = parseInt(a.split('/')[2]);
    var dayB = parseInt(b.split('/')[2]);
    return dayB - dayA; // Sort in descending order (e.g., day 31, 30, 29...)
  });

  keys.forEach(k => {
    // Sort items within each day chronologically
    grouped[k].sort((a, b) => (a.time > b.time) ? 1 : -1);
    res.push({ date: k, items: grouped[k] });
  });

  return res;
}
function getUnreadNotifications() { var ss = SpreadsheetApp.getActiveSpreadsheet(); var sheet = ss.getSheetByName("Notifications"); if (!sheet) return []; var data = sheet.getDataRange().getValues(); var unreadMessages = []; for (var i = data.length - 1; i > 0; i--) { if (data[i][2] == "Unread") { unreadMessages.push(data[i][1]); } } return unreadMessages; }
function markNotificationsAsRead() { var ss = SpreadsheetApp.getActiveSpreadsheet(); var sheet = ss.getSheetByName("Notifications"); if (!sheet) return; var data = sheet.getDataRange().getValues(); for (var i = 1; i < data.length; i++) { if (data[i][2] == "Unread") { sheet.getRange(i + 1, 3).setValue("Read"); } } }

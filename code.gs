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
    usersSheet.appendRow(["ID", "Name", "Username", "Password", "Role", "DailyHours", "ThursdayHours", "TotalMonthlySalary", "Shift1Start", "Shift1End", "Shift2Start", "Shift2End", "isPartTime"]);
  } else {
    var headers = usersSheet.getRange(1, 1, 1, usersSheet.getLastColumn()).getValues()[0];
    if (headers.indexOf("Shift1End") === -1) {
      usersSheet.insertColumnAfter(9);
      usersSheet.getRange(1, 10).setValue("Shift1End");
    }
    if (headers.indexOf("Shift2End") === -1) {
      usersSheet.insertColumnAfter(11);
      usersSheet.getRange(1, 12).setValue("Shift2End");
    }
  }

  // Check if the sheet has only the header row (i.e., no actual users)
  if (usersSheet.getLastRow() < 2) {
    // ID, Name, Username, Pwd, Role, D_hr, Th_hr, Sal, S1S, S1E, S2S, S2E, isPT
    usersSheet.appendRow([1, "مدیر سیستم", "admin", "123", "admin", 8, 4, 10000000, "08:30", "", "17:00", "", "FALSE"]);
  }

  // 2. Logs
  if (!ss.getSheetByName("Logs")) {
    ss.insertSheet("Logs").appendRow(["ID", "Name", "Action", "Timestamp", "JalaliDate", "Time", "Note", "IsSystem"]);
  }

  // 3. Notifications
  var notifSheet = ss.getSheetByName("Notifications");
  if (!notifSheet) {
    notifSheet = ss.insertSheet("Notifications");
    notifSheet.appendRow(["Date", "Message", "Status", "TargetUserID"]);
  } else {
    var headers = notifSheet.getRange(1, 1, 1, Math.max(notifSheet.getLastColumn(), 1)).getValues()[0];
    if (headers.indexOf("TargetUserID") === -1) {
      notifSheet.getRange(1, notifSheet.getLastColumn() + 1).setValue("TargetUserID");
    }
  }
  // Ensure ID columns are text
  usersSheet.getRange("A:A").setNumberFormat("@");
  ss.getSheetByName("Logs").getRange("A:A").setNumberFormat("@");

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

  // SalaryConfig sheet is intentionally removed to simplify the system.

  // 6. Leave Requests
  if (!ss.getSheetByName("LeaveRequests")) {
    ss.insertSheet("LeaveRequests").appendRow(["ID", "UserID", "UserName", "StartDate", "EndDate", "Status"]);
  }

  // 7. Monthly Benefits
  if (!ss.getSheetByName("MonthlyBenefits")) {
    ss.insertSheet("MonthlyBenefits").appendRow(["UserID", "Year", "Month", "Amount", "Status"]);
  }

  // 8. Create Hourly Trigger for autoExitCheck if not exists
  var triggers = ScriptApp.getProjectTriggers();
  var triggerExists = triggers.some(t => t.getHandlerFunction() === 'autoExitCheck');
  if (!triggerExists) {
    ScriptApp.newTrigger('autoExitCheck')
      .timeBased()
      .everyHours(1)
      .create();
  }
}

// --- توابع محاسباتی هسته (Core Logic) ---

function getMonthlyReportCalc(userId, year, month) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  userId = String(userId).trim();
  var user = getUserById(userId);
  if (!user) throw new Error("کاربر یافت نشد: " + userId);

  var logsSheet = ss.getSheetByName("Logs");
  if (!logsSheet) return { details: [], stats: { totalSalary: "0", totalWork: 0, totalPenalty: 0, countDelay: 0, countAbsence: 0, netPerformance: 0, totalDelay: 0, totalOvertime: 0, totalTarget: 0, benefitsStatus: "نامشخص", benefitsRevocationReason: "دیتا یافت نشد", breakdown: {} } };

  var holidays = getHolidaysList();
  var approvedLeaves = getApprovedLeavesForUser(userId);
  var workWeekSettings = getWorkWeekSettings();
  var dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  var targetY = parseInt(year);
  var targetM = parseInt(month);
  var daysInMonth = getJalaliDaysInMonth(targetY, targetM);

  // 1. Get ALL logs for the user to pair entries and exits accurately
  var logsRaw = logsSheet.getDataRange().getValues();
  var logsDisplay = logsSheet.getDataRange().getDisplayValues();
  var userLogs = [];
  for (var i = 1; i < logsRaw.length; i++) {
    if (isSameId(logsRaw[i][0], userId)) {
      userLogs.push({
        act: logsRaw[i][2],
        ts: new Date(logsRaw[i][3]).getTime(),
        jalali: String(logsDisplay[i][4]).split(' ')[0], // YYYY/MM/DD
        timeStr: logsDisplay[i][5] // HH:mm:ss
      });
    }
  }
  userLogs.sort((a, b) => a.ts - b.ts);

  // 2. Create paired sessions
  var sessions = [];
  var tempIn = null;
  userLogs.forEach(l => {
    if (l.act === 'Entry' || l.act === 'LeaveEnd') {
      tempIn = l;
    } else if (tempIn && (l.act === 'Exit' || l.act === 'AutoExit' || l.act === 'LeaveStart')) {
      sessions.push({ start: tempIn, end: l, type: tempIn.act === 'LeaveEnd' ? 'IN' : tempIn.act });
      tempIn = (l.act === 'LeaveStart') ? null : null; // Reset unless we need to handle nested stuff
    }
  });
  // Handle open session (current day)
  var now = new Date();
  if (tempIn) {
     var nowJalali = getJalaliDate(now);
     var nowTimeStr = Utilities.formatDate(now, "Asia/Tehran", "HH:mm:ss");
     sessions.push({
       start: tempIn,
       end: { ts: now.getTime(), jalali: nowJalali, timeStr: nowTimeStr, act: 'Open' },
       type: 'IN'
     });
  }

  var dailyStats = {};
  for (var d = 1; d <= daysInMonth; d++) {
    var dStr = (d < 10) ? "0" + d : d;
    var mStr = (targetM < 10) ? "0" + targetM : targetM;
    var k = targetY + "/" + mStr + "/" + dStr;
    dailyStats[k] = { presence: 0, inside: 0, outside: 0, hasLogs: false, missingExit: false };
  }

  // 3. Process sessions and attribute to days
  sessions.forEach(s => {
    var k = s.start.jalali;
    if (dailyStats[k]) {
      dailyStats[k].hasLogs = true;
      if (s.end.act === 'Open') dailyStats[k].missingExit = true;
      if (s.end.act === 'AutoExit') dailyStats[k].isAutoExit = true;

      var duration = (s.end.ts - s.start.ts) / 60000;

      // Only count duration if it's NOT an AutoExit to maintain discipline (forgotten exit = 0 mins for that session)
      // Admin can manually correct this if needed.
      if (s.end.act === 'AutoExit') {
        return;
      }

      // Calculate overlaps with shifts for this day
      var s1S = timeToMins(user.shift1Start);
      var s1E = timeToMins(user.shift1End);
      var s2S = timeToMins(user.shift2Start);
      var s2E = timeToMins(user.shift2End);

      var gregorianDate = jalaliToGregorian(parseInt(k.split('/')[0]), parseInt(k.split('/')[1]), parseInt(k.split('/')[2]));
      var dayOfWeek = gregorianDate.getDay();
      var dayName = dayNames[dayOfWeek];
      var dayStatus = workWeekSettings[dayName];
      var dayTarget = 0;
      if (dayStatus === "Full Day") dayTarget = (parseFloat(user.dailyHours) || 0) * 60;
      else if (dayStatus === "Half Day") dayTarget = (parseFloat(user.thursdayHours) || 0) * 60;

      var insideZones = [];
      if (dayTarget > 0) {
        if (s1E > 0) {
          insideZones.push({ s: s1S, e: s1E });
          if (s2S > 0 && s2E > 0) insideZones.push({ s: s2S, e: s2E });
        } else {
          insideZones.push({ s: s1S, e: s1S + dayTarget });
        }
      }

      var segStartMins = timeToMins(s.start.timeStr);
      var segEndMins = segStartMins + duration; // Using duration to handle past midnight if needed

      var segInside = 0;
      insideZones.forEach(z => {
        var overlapS = Math.max(segStartMins, z.s);
        var overlapE = Math.min(segEndMins, z.e);
        segInside += Math.max(0, overlapE - overlapS);
      });

      dailyStats[k].presence += duration;
      dailyStats[k].inside += segInside;
      dailyStats[k].outside += Math.max(0, duration - segInside);
    }
  });

  var summary = [];
  var totWorkMins = 0, totalOvertimeMins = 0, totalMissingMins = 0, totalTargetMins = 0;
  var countDelay = 0, countAbsence = 0;

  for (var d = 1; d <= daysInMonth; d++) {
    var dStr = (d < 10) ? "0" + d : d;
    var mStr = (targetM < 10) ? "0" + targetM : targetM;
    var k = targetY + "/" + mStr + "/" + dStr;

    var isH = holidays.some(h => h.trim() == k);
    var isOnLeave = approvedLeaves.some(l => k >= l.start && k <= l.end);
    var gregorianDate = jalaliToGregorian(targetY, targetM, d);
    var dayTarget = 0;
    if (!isH && !isOnLeave) {
      var dayStatus = workWeekSettings[dayNames[gregorianDate.getDay()]];
      if (dayStatus === "Full Day") dayTarget = (parseFloat(user.dailyHours) || 0) * 60;
      else if (dayStatus === "Half Day") dayTarget = (parseFloat(user.thursdayHours) || 0) * 60;
    }
    totalTargetMins += dayTarget;

    var ds = dailyStats[k];
    var dayMissing = Math.max(0, dayTarget - ds.inside);

    // AutoExit Penalty: If AutoExit happened, user gets 0 for that day according to guide,
    // but user requested "count time for same day". Let's compromise: count time but mark status.
    // Actually, let's follow the user's wish to "calculate precisely".

    totWorkMins += ds.presence;
    totalOvertimeMins += ds.outside;
    totalMissingMins += dayMissing;

    var statusText = "تکمیل";
    if (dayTarget > 0) {
      if (!ds.hasLogs) { statusText = "غیبت"; countAbsence++; }
      else if (dayMissing > 0) { statusText = "کسر کار: " + Math.round(dayMissing) + "دقیقه"; countDelay++; }
    }
    if (isH) statusText = "تعطیل رسمی";
    if (ds.missingExit) statusText = "⚠️ عدم خروج";
    if (ds.isAutoExit) statusText = "⚠️ خروج خودکار";

    summary.push({
      date: k,
      work: fmt(Math.round(ds.presence)),
      delay: Math.round(dayMissing),
      missing: Math.round(dayMissing),
      penalty: fmt(Math.round(dayMissing * (user.isPartTime ? 1 : PENALTY_MULTIPLIER))),
      status: statusText
    });
  }

  var baseSalary = parseFloat(String(user.totalMonthlySalary).replace(/,/g, '')) || 0;
  var minuteRate = totalTargetMins > 0 ? baseSalary / totalTargetMins : 0;

  var otBonusMins = totalOvertimeMins * OVERTIME_MULTIPLIER;
  var missingPenaltyMins = totalMissingMins * (user.isPartTime ? 1 : PENALTY_MULTIPLIER);
  var netAdjMins = otBonusMins - missingPenaltyMins;
  var salaryFromWork = baseSalary + (netAdjMins * minuteRate);

  var benefitsData = getMonthlyBenefits(userId, year, month);

  // Auto-revocation logic
  var benefitsStatus = "برقرار";
  var autoRevoked = false;
  if (countDelay >= 5 || countAbsence >= 2) {
    autoRevoked = true;
    benefitsStatus = "لغو خودکار (تخطی از قوانین)";
  }

  // Override if admin already approved
  var approvedBenefits = 0;
  if (benefitsData.status === 'تایید شده') {
    approvedBenefits = benefitsData.amount;
    benefitsStatus = "تایید شده (توسط مدیر)";
  } else if (autoRevoked && benefitsData.status !== 'تایید شده') {
    approvedBenefits = 0;
  } else if (benefitsData.status === 'در انتظار تایید') {
    benefitsStatus = "در انتظار تایید مدیر";
  }

  var finalPay = salaryFromWork + approvedBenefits;

  return {
    details: summary,
    stats: {
      totalSalary: Math.round(finalPay).toLocaleString() + " ریال",
      totalWork: Math.round(totWorkMins),
      totalPenalty: Math.round(missingPenaltyMins),
      countDelay: countDelay,
      countAbsence: countAbsence,
      netPerformance: Math.round(totalTargetMins + netAdjMins),
      totalDelay: Math.round(totalMissingMins),
      totalOvertime: Math.round(totalOvertimeMins),
      totalTarget: Math.round(totalTargetMins),
      benefitsStatus: benefitsStatus,
      breakdown: {
        baseSalary: Math.round(baseSalary).toLocaleString() + " ریال",
        workSalary: Math.round(salaryFromWork).toLocaleString() + " ریال",
        benefitAmount: Math.round(approvedBenefits).toLocaleString() + " ریال",
        benefitStatus: benefitsData.status,
        benefitRaw: benefitsData.amount,
        rawOvertime: Math.round(totalOvertimeMins),
        overtimeBonus: Math.round(otBonusMins),
        rawMissing: Math.round(totalMissingMins),
        missingPenalty: Math.round(missingPenaltyMins),
        netAdjustment: Math.round(netAdjMins),
        minuteRate: minuteRate.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
      }
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

  // If the entry is for the first shift (before 14:00)
  if (timeMins < 14 * 60) {
    if (s1 > 0 && timeMins > s1) {
      delay = timeMins - s1;
    }
  }
  // If the entry is for the second shift (after 17:00)
  else if (timeMins >= 17 * 60) {
    if (s2 > 0 && timeMins > s2) {
      delay = timeMins - s2;
    }
  }
  // Entries between 14:00 and 16:59 have no delay.

  return delay;
}

function timeToMins(t) {
  if (!t) return 0;
  t = String(t).trim();
  if (!t.includes(':')) {
    // Try to handle cases like "08" or "8"
    var n = parseInt(t);
    if (!isNaN(n) && n < 24) return n * 60;
    return 0;
  }
  var p = t.split(':');
  var h = parseInt(p[0]) || 0;
  var m = parseInt(p[1]) || 0;
  return h * 60 + m;
}

function getUserById(id) {
    if (id == null) return null;
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
    if (!sheet) return null;
    var data = sheet.getDataRange().getDisplayValues();
    if (data.length < 2) return null;

    var headers = data[0].map(h => String(h).trim());
    var headerMap = {};
    headers.forEach((h, i) => { if (h) headerMap[h] = i; });

    const idIndex = headerMap['ID'];
    if (idIndex === undefined) return null;

    for (var i = 1; i < data.length; i++) {
        if (isSameId(data[i][idIndex], id)) {
            var row = data[i];
            const get = (key) => headerMap[key] !== undefined ? row[headerMap[key]] : undefined;
            return {
                id: get('ID'),
                name: get('Name'),
                username: get('Username'),
                password: get('Password'),
                role: get('Role'),
                dailyHours: get('DailyHours'),
                thursdayHours: get('ThursdayHours'),
                totalMonthlySalary: get('TotalMonthlySalary'),
                shift1Start: get('Shift1Start'),
                shift1End: get('Shift1End'),
                shift2Start: get('Shift2Start'),
                shift2End: get('Shift2End'),
                isPartTime: get('isPartTime') === true || String(get('isPartTime')).toUpperCase() === 'TRUE'
            };
        }
    }
    return null;
}


// --- Core App Functions ---
function loginUser(u, p) {
  setupSheet();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  if (!sheet) throw new Error("User sheet not found.");
  var data = sheet.getDataRange().getDisplayValues();
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

  u = String(u).trim();
  p = String(p).trim();
  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    if (String(row[userIndex]).trim() === u && String(row[passIndex]).trim() === p) {
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
  for (var i = d.length - 1; i > 0; i--) if (isSameId(d[i][0], id)) return d[i][2];
  return "Exit";
}

function getTodayUserSummary(userId) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  userId = String(userId).trim();
  var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues();
  var now = new Date();
  var jalali = getJalaliDate(now);
  var list = [];
  var lastStatus = "Exit";
  for (var i = 1; i < logs.length; i++) {
    var d = String(logs[i][4]).split(' ')[0].trim();
    var logUid = String(logs[i][0]).trim();
    if (isSameId(logUid, userId)) {
      if (d == jalali) list.push({ time: logs[i][5], action: translateAction(logs[i][2]), raw: logs[i][2] });
      lastStatus = logs[i][2];
    }
  }
  return { history: list, status: lastStatus };
}

// --- Date and Formatting Helpers ---

function isSameId(id1, id2) {
  if (id1 === id2) return true;
  if (id1 == null || id2 == null) return false;
  return String(id1).trim() === String(id2).trim() || Number(id1) === Number(id2);
}

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

function translateAction(a) { if (a == 'Entry') return 'ورود'; if (a == 'Exit') return 'خروج'; if (a == 'LeaveStart') return 'شروع مرخصی'; if (a == 'AutoExit') return 'خروج خودکار'; return 'پایان مرخصی'; }
function fmt(m) {
  var isNeg = m < 0;
  m = Math.abs(m);
  var h = Math.floor(m / 60);
  var n = Math.floor(m % 60);
  return (isNeg ? "-" : "") + h + ":" + (n < 10 ? "0" + n : n);
}

// --- Admin Functions (Updated) ---
function getEmployeesList() {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
    if (!sheet) return [];
    // FIX: Use getDisplayValues() to read formatted strings (like '08:30') instead of Date objects.
    var data = sheet.getDataRange().getDisplayValues();
    if (data.length < 2) return [];

    var headers = data.shift().map(h => String(h).trim());
    var headerMap = {};
    headers.forEach((h, i) => { if (h) headerMap[h] = i; });

    // Validate that all essential headers are present
    const requiredHeaders = ["ID", "Name", "Username", "Password", "Role", "DailyHours", "ThursdayHours", "TotalMonthlySalary", "Shift1Start", "Shift1End", "Shift2Start", "Shift2End", "isPartTime"];
    const missingHeaders = requiredHeaders.filter(h => !(h in headerMap));
    if (missingHeaders.length > 0) {
        throw new Error("ستون‌های زیر در شیت Users وجود ندارند: " + missingHeaders.join(', '));
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
        totalMonthlySalary: get('TotalMonthlySalary'),
        shift1Start: get('Shift1Start'),
        shift1End: get('Shift1End'),
        shift2Start: get('Shift2Start'),
        shift2End: get('Shift2End'),
        isPartTime: get('isPartTime') === true || String(get('isPartTime')).toUpperCase() === 'TRUE'
      });
    });

    return list;
  } catch (e) {
    console.error("Error in getEmployeesList:", e);
    // Re-throw the error so the frontend can display it.
    throw new Error("خطا در خواندن لیست کارمندان: " + e.message);
  }
}


function updateUserInfo(id, name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift1End, shift2Start, shift2End, isPartTime) {
  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch (e) { throw new Error("سیستم مشغول است، لطفا لحظاتی دیگر تلاش کنید"); }

  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  var d = s.getDataRange().getValues();
  var headers = s.getRange(1, 1, 1, s.getLastColumn()).getValues()[0].map(h => String(h).trim());
  var colMap = {};
  headers.forEach((h, i) => colMap[h] = i + 1);

  for (var i = 1; i < d.length; i++) {
    if (String(d[i][0]) == String(id)) {
      const set = (h, v) => s.getRange(i + 1, colMap[h]).setValue(v);
      set("Name", name); set("Username", username); set("Password", password);
      set("DailyHours", dailyHours); set("ThursdayHours", thursdayHours);
      set("TotalMonthlySalary", totalMonthlySalary);
      set("Shift1Start", shift1Start); set("Shift1End", shift1End);
      set("Shift2Start", shift2Start); set("Shift2End", shift2End);
      set("isPartTime", isPartTime);
      SpreadsheetApp.flush();

      var updatedUser = getUserById(id);
      lock.releaseLock();
      return { success: true, user: updatedUser };
    }
  }
  lock.releaseLock();
  return { success: false };
}

function adminSaveUser(name, username, password, dailyHours, thursdayHours, totalMonthlySalary, shift1Start, shift1End, shift2Start, shift2End, isPartTime) {
  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch (e) { throw new Error("سیستم مشغول است"); }

  var s = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
  var existingData = s.getDataRange().getDisplayValues();
  for (var i = 1; i < existingData.length; i++) {
    if (existingData[i][2].trim().toLowerCase() === String(username).trim().toLowerCase()) {
      lock.releaseLock();
      throw new Error("این نام کاربری قبلا ثبت شده است");
    }
  }

  var headers = s.getRange(1, 1, 1, s.getLastColumn()).getValues()[0].map(h => String(h).trim());
  var row = new Array(headers.length).fill("");

  var colMap = {};
  headers.forEach((h, i) => colMap[h] = i);

  row[colMap["ID"]] = new Date().getTime();
  row[colMap["Name"]] = name;
  row[colMap["Username"]] = username;
  row[colMap["Password"]] = password;
  row[colMap["Role"]] = "user";
  row[colMap["DailyHours"]] = dailyHours;
  row[colMap["ThursdayHours"]] = thursdayHours;
  row[colMap["TotalMonthlySalary"]] = totalMonthlySalary;
  row[colMap["Shift1Start"]] = shift1Start;
  row[colMap["Shift1End"]] = shift1End;
  row[colMap["Shift2Start"]] = shift2Start;
  row[colMap["Shift2End"]] = shift2End;
  row[colMap["isPartTime"]] = isPartTime;

  s.appendRow(row);
  SpreadsheetApp.flush();
  lock.releaseLock();
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

function toggleHoliday(date) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(date)) {
      sheet.deleteRow(i + 1);
      return { success: true, status: 'removed' };
    }
  }
  sheet.appendRow([date, "تعطیل رسمی"]);
  return { success: true, status: 'added' };
}

function addHolidayToSheet(date) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays");
  sheet.appendRow([date, ""]);
  return { success: true };
}

function removeHolidayFromSheet(date) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Holidays");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(date)) {
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
    settings[String(data[i][0]).trim()] = String(data[i][1]).trim();
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
  userId = String(userId).trim();
  var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues();

  var mStr = String(month);
  if (mStr.length === 1) mStr = "0" + mStr;
  var targetYM = year + "/" + mStr;

  var grouped = {};

  for (var i = 1; i < logs.length; i++) {
    var d = String(logs[i][4]).split(' ')[0].trim();
    var logUid = String(logs[i][0]).trim();
    if (isSameId(logUid, userId) && d.startsWith(targetYM)) {
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
  // Sort keys numerically based on the day (Ascending: Day 1 at the top)
  var keys = Object.keys(grouped).sort((a, b) => {
    var dayA = parseInt(a.split('/')[2]);
    var dayB = parseInt(b.split('/')[2]);
    return dayA - dayB;
  });

  keys.forEach(k => {
    // Sort items within each day chronologically
    grouped[k].sort((a, b) => (a.time > b.time) ? 1 : -1);
    res.push({ date: k, items: grouped[k] });
  });

  return res;
}

// --- توابع جدید مدیریت مرخصی ---

function submitLeaveRequest(userInfo, startDate, endDate) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("LeaveRequests");
  var newId = new Date().getTime(); // Simple unique ID
  sheet.appendRow([newId, userInfo.id, userInfo.name, startDate, endDate, "در انتظار"]);
  addNotification('admin', "درخواست مرخصی جدید از طرف " + userInfo.name);
  return { success: true, message: "درخواست مرخصی شما ثبت شد و در انتظار تایید مدیر است." };
}

function getLeaveRequests() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("LeaveRequests");
  if (!sheet) return [];
  var data = sheet.getDataRange().getDisplayValues();
  if (data.length < 2) return [];
  var requests = [];
  // Start from the end to get the newest requests first
  for (var i = data.length - 1; i >= 1; i--) {
    requests.push({
      id: data[i][0],
      userId: data[i][1],
      userName: data[i][2],
      startDate: data[i][3],
      endDate: data[i][4],
      status: data[i][5]
    });
  }
  return requests;
}

function updateLeaveStatus(requestId, newStatus) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("LeaveRequests");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(requestId)) {
      sheet.getRange(i + 1, 6).setValue(newStatus); // Column 6 is 'Status'
      var userId = data[i][1];
      addNotification(userId, "درخواست مرخصی شما " + newStatus + " شد.");
      return { success: true };
    }
  }
  throw new Error("درخواست مرخصی یافت نشد.");
}

function getMonthlyBenefits(userId, year, month) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("MonthlyBenefits");
  if (!sheet) return { amount: 0, status: 'نامشخص' };
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(userId) && String(data[i][1]) == String(year) && String(data[i][2]) == String(month)) {
      return { amount: parseFloat(data[i][3]) || 0, status: data[i][4] };
    }
  }
  return { amount: 0, status: 'ثبت نشده' };
}

function saveMonthlyBenefit(userId, year, month, amount) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("MonthlyBenefits");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(userId) && String(data[i][1]) == String(year) && String(data[i][2]) == String(month)) {
      sheet.getRange(i + 1, 4).setValue(amount);
      sheet.getRange(i + 1, 5).setValue('در انتظار تایید');
      return { success: true };
    }
  }
  sheet.appendRow([userId, year, month, amount, 'در انتظار تایید']);
  return { success: true };
}

function approveMonthlyBenefit(userId, year, month) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("MonthlyBenefits");
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (String(data[i][0]) == String(userId) && String(data[i][1]) == String(year) && String(data[i][2]) == String(month)) {
      sheet.getRange(i + 1, 5).setValue('تایید شده');
      addNotification(userId, "مزایای ماه " + month + " برای شما تایید شد.");
      return { success: true };
    }
  }
  return { success: false };
}

function getApprovedLeavesForUser(userId) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("LeaveRequests");
  if (!sheet) return [];
  var data = sheet.getDataRange().getDisplayValues();
  var leaves = [];
  for (var i = 1; i < data.length; i++) {
    var rowUid = String(data[i][1]).trim();
    if (isSameId(rowUid, userId) && data[i][5] === 'تایید شده') {
      leaves.push({
        start: data[i][3], // StartDate
        end: data[i][4]     // EndDate
      });
    }
  }
  return leaves;
}

function addNotification(userId, message) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Notifications");
  if (!sheet) return;
  sheet.appendRow([new Date(), message, "Unread", userId]);
}

function getUnreadNotifications(userId) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Notifications");
  if (!sheet) return [];
  var data = sheet.getDataRange().getDisplayValues();
  var unreadMessages = [];
  for (var i = data.length - 1; i > 0; i--) {
    if (data[i][2] == "Unread" && (isSameId(data[i][3], userId) || (userId === 'admin' && String(data[i][3]) == 'admin'))) {
      unreadMessages.push({
        msg: data[i][1],
        date: data[i][0]
      });
    }
  }
  return unreadMessages;
}

function markNotificationsAsRead(userId) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Notifications");
  if (!sheet) return;
  var data = sheet.getDataRange().getValues();
  for (var i = 1; i < data.length; i++) {
    if (data[i][2] == "Unread" && (isSameId(data[i][3], userId) || (userId === 'admin' && String(data[i][3]) == 'admin'))) {
      sheet.getRange(i + 1, 3).setValue("Read");
    }
  }
}

function autoExitCheck() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var logsSheet = ss.getSheetByName("Logs");
  var logs = logsSheet.getDataRange().getValues();
  var logsDisplay = logsSheet.getDataRange().getDisplayValues();
  var now = new Date();
  var users = getEmployeesList();

  users.forEach(user => {
    var lastAction = null;
    var lastTime = null;
    var lastJalali = null;
    for (var i = logs.length - 1; i > 0; i--) {
      if (isSameId(logs[i][0], user.id)) {
        lastAction = logs[i][2];
        lastTime = new Date(logs[i][3]);
        lastJalali = String(logsDisplay[i][4]).split(' ')[0];
        break;
      }
    }
    if (lastAction === 'Entry' || lastAction === 'LeaveEnd') {
      var diffHours = (now - lastTime) / (1000 * 60 * 60);
      if (diffHours > 15) {
        // Register AutoExit using the SAME Jalali date as the Entry to keep it in the same report day
        var timeStr = Utilities.formatDate(now, "Asia/Tehran", "HH:mm:ss");
        logsSheet.appendRow([user.id, user.name, "AutoExit", now, lastJalali, timeStr, "خروج خودکار سیستم", "System"]);
        addNotification(user.id, "خروج خودکار ثبت شد (بیش از ۱۵ ساعت حضور)");
        addNotification('admin', "خروج خودکار برای " + user.name + " ثبت شد.");
      }
    }
  });
}

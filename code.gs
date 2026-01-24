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
  var logsDisplay = logsSheet.getDataRange().getDisplayValues();
  var logsRaw = logsSheet.getDataRange().getValues();

  var holidays = getHolidaysList();
  var approvedLeaves = getApprovedLeavesForUser(userId);
  var workWeekSettings = getWorkWeekSettings();
  var dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  // Ensure month has leading zero for strict startsWith check
  var mStr = String(month);
  if (mStr.length === 1) mStr = "0" + mStr;
  var targetYM = year + "/" + mStr;
  var daysData = {};

  // Process logs into a daily structure
  for (var i = 1; i < logsRaw.length; i++) {
    var d = String(logsDisplay[i][4]).split(' ')[0].trim();
    var logUid = String(logsDisplay[i][0]).trim();
    if ((logUid == userId || Number(logUid) == Number(userId)) && d.startsWith(targetYM)) {
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
    var isOnLeave = approvedLeaves.some(l => k >= l.start && k <= l.end); // Check if the date is within a leave period
    var currentDate = jalaliToGregorian(year, month, dStr);
    var dayOfWeek = currentDate.getDay(); // 0=Sun, 5=Fri

    var dayTarget = 0;
    if (!isH && !isOnLeave) {
      var dayName = dayNames[dayOfWeek];
      var dayStatus = workWeekSettings[dayName];
      if (dayStatus === "Full Day") {
        dayTarget = (parseFloat(user.dailyHours) || 0) * 60;
      } else if (dayStatus === "Half Day") {
        dayTarget = (parseFloat(user.thursdayHours) || 0) * 60;
      }
    }
    totalMonthTargetMins += dayTarget;

    var items = daysData[k] ? daysData[k].logs : [];
    var workMs = 0, leaveMs = 0, dailyDelayMins = 0, missingExit = false;
    var dayInsideMins = 0, dayOutsideMins = 0;

    if (items.length > 0) {
      items.sort((a, b) => a.ts - b.ts);

      var firstEntry = items.find(x => x.act === 'Entry');
      var sStart = 0;
      if (firstEntry) {
        var entMins = timeToMins(firstEntry.timeStr);
        var s1 = timeToMins(user.shift1Start);
        var s2 = timeToMins(user.shift2Start);
        var baseStart = s1;
        if (s2 > 0 && Math.abs(entMins - s2) < Math.abs(entMins - s1)) baseStart = s2;
        sStart = baseStart;
        if (entMins > baseStart) dailyDelayMins = entMins - baseStart;
      }
      var sEnd = sStart + dayTarget;

      var lastTime = null, lastAct = null, state = 'OUT';
      items.forEach(function(l) {
        var currentTs = l.ts;
        var currentMins = timeToMins(l.timeStr);

        if (l.act == 'Entry') {
          if (state == 'OUT') { lastTime = currentTs; lastAct = 'Entry'; state = 'IN'; }
        }
        else if (state == 'IN') {
          if (l.act == 'Exit' || l.act == 'AutoExit' || l.act == 'LeaveStart') {
            if (lastTime) {
              var duration = (currentTs - lastTime) / 60000;
              if (l.act !== 'AutoExit') {
                workMs += (currentTs - lastTime);
                if (dayTarget > 0) {
                  var intervalStart = timeToMins(items.find(x => x.ts === lastTime).timeStr);
                  var overlapStart = Math.max(intervalStart, sStart);
                  var overlapEnd = Math.min(currentMins, sEnd);
                  var inside = Math.max(0, overlapEnd - overlapStart);
                  dayInsideMins += inside;
                  dayOutsideMins += Math.max(0, duration - inside);
                } else {
                  dayOutsideMins += duration;
                }
              }
            }
            state = (l.act == 'LeaveStart') ? 'LEAVE' : 'OUT';
            lastTime = currentTs; lastAct = l.act;
          }
        }
        else if (state == 'LEAVE') {
          if (l.act == 'LeaveEnd' || l.act == 'Exit' || l.act == 'AutoExit') {
            if (lastTime) {
              var duration = (currentTs - lastTime) / 60000;
              if (l.act !== 'AutoExit') {
                leaveMs += (currentTs - lastTime);
                if (dayTarget > 0) {
                  var intervalStart = timeToMins(items.find(x => x.ts === lastTime).timeStr);
                  var overlapStart = Math.max(intervalStart, sStart);
                  var overlapEnd = Math.min(currentMins, sEnd);
                  var inside = Math.max(0, overlapEnd - overlapStart);
                  dayInsideMins += inside; // Approved leave time during shift counts as 'inside' (no penalty)
                  dayOutsideMins += Math.max(0, duration - inside);
                } else {
                  dayOutsideMins += duration;
                }
              }
            }
            state = (l.act == 'LeaveEnd') ? 'IN' : 'OUT';
            lastTime = (l.act == 'LeaveEnd') ? currentTs : null;
            lastAct = l.act;
          }
        }
      });
      if (state == 'IN' || state == 'LEAVE') missingExit = true;
    }

    var wMin = Math.floor(workMs / 60000);
    totWork += wMin;
    totalDelay += dailyDelayMins;

    var dayMissingMins = Math.max(0, dayTarget - dayInsideMins);
    totalOvertime += dayOutsideMins;
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
      missing: dayMissingMins,
      penalty: fmt((dayMissingMins * 3)),
      status: statusText
    });
  }

  var minuteRate = 0;
  if (totalMonthTargetMins > 0) {
    minuteRate = (parseFloat(user.totalMonthlySalary) || 0) / totalMonthTargetMins;
  }

  // New Strict Logic Implementation (Based on Shift Overlap)
  // totalOvertime now contains all minutes worked outside the target shift window
  // We also need to account for missing minutes from the target shift window
  var totalMissingMins = 0;
  // Recalculate summary to get total missing
  summary.forEach(s => {
    if (s.missing) totalMissingMins += s.missing;
  });

  var netAdjustmentMins = (totalOvertime * 1.4) - (totalMissingMins * 3);
  var salaryFromWork = (parseFloat(user.totalMonthlySalary) || 0) + (netAdjustmentMins * minuteRate);

  var benefitsData = getMonthlyBenefits(userId, year, month);
  var approvedBenefits = (benefitsData.status === 'تایید شده') ? benefitsData.amount : 0;
  var finalPay = salaryFromWork + approvedBenefits;

  var benefitsStatus = "برقرار";
  var benefitsRevocationReason = "";
  if (countDelay > 5) {
    benefitsStatus = "حذف شده";
    benefitsRevocationReason = "تاخیر بیش از 5 بار";
  } else if (countAbsence > 2) {
    benefitsStatus = "حذف شده";
    benefitsRevocationReason = "غیبت بیش از 2 بار";
  }

  return {
    details: summary,
    stats: {
      daysConfig: getMonthDays(year, month),
      totalSalary: Math.round(finalPay).toLocaleString() + " ریال",
      totalWork: totWork,
      totalPenalty: Math.round(totalMissingMins * 3),
      countDelay: countDelay,
      countAbsence: countAbsence,
      netPerformance: Math.round(totalMonthTargetMins + netAdjustmentMins),
      totalDelay: totalDelay,
      totalOvertime: totalOvertime,
      totalTarget: totalMonthTargetMins,
      benefitsStatus: benefitsStatus,
      benefitsRevocationReason: benefitsRevocationReason,
      // Additional breakdown for the user/admin
      breakdown: {
        baseSalary: Math.round(parseFloat(user.totalMonthlySalary) || 0).toLocaleString() + " ریال",
        workSalary: Math.round(salaryFromWork).toLocaleString() + " ریال",
        benefitAmount: Math.round(approvedBenefits).toLocaleString() + " ریال",
        benefitStatus: benefitsData.status,
        benefitRaw: benefitsData.amount,
        totalMissing: totalMissingMins,
        totalOvertime: Math.round(totalOvertime),
        netAdjustment: Math.round(netAdjustmentMins),
        minuteRate: Math.round(minuteRate).toLocaleString()
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
  if (!t || typeof t !== 'string' || !t.includes(':')) return 0;
  var p = t.split(':');
  return parseInt(p[0])*60 + parseInt(p[1]);
}

function getUserById(id) {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Users");
    if (!sheet) return null;
    id = String(id).trim();
    var data = sheet.getDataRange().getDisplayValues();
    if (data.length < 2) return null;

    var headers = data[0].map(h => String(h).trim());
    var headerMap = {};
    headers.forEach((h, i) => { if (h) headerMap[h] = i; });

    const idIndex = headerMap['ID'];
    if (idIndex === undefined) return null;

    for (var i = 1; i < data.length; i++) {
        var row = data[i];
        var rowId = String(row[idIndex]).trim();
        if (rowId == id || Number(rowId) == Number(id)) {
            const get = (key) => headerMap[key] !== undefined ? row[headerMap[key]] : undefined;
            return {
                id: get('ID'),
                name: get('Name'),
                dailyHours: get('DailyHours'),
                thursdayHours: get('ThursdayHours'),
                totalMonthlySalary: get('TotalMonthlySalary'),
                shift1Start: get('Shift1Start'), // This will now be a string 'HH:mm'
                shift2Start: get('Shift2Start'), // This will now be a string 'HH:mm'
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
  userId = String(userId).trim();
  var logs = ss.getSheetByName("Logs").getDataRange().getDisplayValues();
  var now = new Date();
  var jalali = getJalaliDate(now);
  var list = [];
  var lastStatus = "Exit";
  for (var i = 1; i < logs.length; i++) {
    var d = String(logs[i][4]).split(' ')[0].trim();
    var logUid = String(logs[i][0]).trim();
    if (logUid == userId || Number(logUid) == Number(userId)) {
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

function translateAction(a) { if (a == 'Entry') return 'ورود'; if (a == 'Exit') return 'خروج'; if (a == 'LeaveStart') return 'شروع مرخصی'; if (a == 'AutoExit') return 'خروج خودکار'; return 'پایان مرخصی'; }
function fmt(m) { var h = Math.floor(m / 60); var n = m % 60; return h + ":" + (n < 10 ? "0" + n : n); }

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
    const requiredHeaders = ["ID", "Name", "Username", "Password", "Role", "DailyHours", "ThursdayHours", "TotalMonthlySalary", "Shift1Start", "Shift2Start", "isPartTime"];
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
        shift1Start: get('Shift1Start'), // This will now be a string 'HH:mm'
        shift2Start: get('Shift2Start'), // This will now be a string 'HH:mm'
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
    if ((logUid == userId || Number(logUid) == Number(userId)) && d.startsWith(targetYM)) {
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
    // Check if the user ID matches and the status is 'تایید شده'
    if (String(data[i][1]) == String(userId) && data[i][5] === 'تایید شده') {
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
    if (data[i][2] == "Unread" && (String(data[i][3]) == String(userId) || (userId === 'admin' && String(data[i][3]) == 'admin'))) {
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
    if (data[i][2] == "Unread" && (String(data[i][3]) == String(userId) || (userId === 'admin' && String(data[i][3]) == 'admin'))) {
      sheet.getRange(i + 1, 3).setValue("Read");
    }
  }
}

function autoExitCheck() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var logsSheet = ss.getSheetByName("Logs");
  var logs = logsSheet.getDataRange().getValues();
  var now = new Date();
  var users = getEmployeesList();

  users.forEach(user => {
    var lastAction = null;
    var lastTime = null;
    for (var i = logs.length - 1; i > 0; i--) {
      if (String(logs[i][0]) == String(user.id)) {
        lastAction = logs[i][2];
        lastTime = new Date(logs[i][3]);
        break;
      }
    }
    if (lastAction === 'Entry' || lastAction === 'LeaveEnd') {
      var diffHours = (now - lastTime) / (1000 * 60 * 60);
      if (diffHours > 15) {
        var jalali = getJalaliDate(now);
        var timeStr = Utilities.formatDate(now, "Asia/Tehran", "HH:mm:ss");
        logsSheet.appendRow([user.id, user.name, "AutoExit", now, jalali, timeStr, "خروج خودکار سیستم", "System"]);
        addNotification(user.id, "خروج خودکار ثبت شد (بیش از ۱۵ ساعت حضور)");
        addNotification('admin', "خروج خودکار برای " + user.name + " ثبت شد.");
      }
    }
  });
}

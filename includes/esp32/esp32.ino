/*
  Gym Access ESP32‑S3 + Wiegand 125kHz четец + Web Debug

  Хардуер:
  - ESP32-S3
  - Wiegand 26/34 125kHz четец (изходи D0 и D1)

  Свързване:
  - Wiegand D0 -> GPIO 4
  - Wiegand D1 -> GPIO 5
  - Реле       -> GPIO 9
  - LED OK     -> GPIO 10
  - LED ERR    -> GPIO 11
  - Buzzer     -> GPIO 12
*/

#include <WiFi.h>
#include <WiFiMulti.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <Update.h>
#include "wifi_secrets.h"

// ===================== WiFi настройки =====================
// Паролите са в локалния wifi_secrets.h и не се качват в GitHub.
// При нов clone копирай wifi_secrets.h.example -> wifi_secrets.h

WiFiMulti wifiMulti;

// ===================== API настройки ======================
const char* API_BASE_URL   = "https://grace-fitness.eu/wp-json/gym/v1";

// Текуща версия на firmware-a (актуализирай при нов build)
const char* CURRENT_FIRMWARE_VERSION = "1.0.6";

WiFiClientSecure secureClient;
WebServer server(80);

// ===================== Пинове ============================
// Wiegand входове
constexpr uint8_t PIN_WG_D0    = 4;
constexpr uint8_t PIN_WG_D1    = 5;

// Реле + индикация
constexpr uint8_t PIN_RELAY      = 9;
constexpr uint8_t PIN_LED_OK     = 10;
constexpr uint8_t PIN_LED_ERR    = 11;
constexpr uint8_t PIN_BUZZER     = 12;
// Полярност на релето. Преди OTA работеше като active HIGH.
constexpr bool RELAY_ACTIVE_LOW  = false;

// Време на отключване и cooldown
constexpr uint32_t DOOR_OPEN_TIME_MS = 3000;
constexpr uint32_t CARD_COOLDOWN_MS  = 3000;

// ===================== Глобални за Wiegand ===========================
volatile uint32_t wgData        = 0;      // натрупани битове (до 32 бита – достатъчно за Wiegand 26)
volatile uint8_t  wgBitCount    = 0;      // брой приети битове
volatile unsigned long wgLastBitTime = 0; // време на последния бит (millis)

// Последна карта (за cooldown)
String    lastCardId      = "";
uint32_t  lastCardTime    = 0;

// Debug променливи
String        dbg_lastCardId       = "";
unsigned long dbg_lastScanMillis   = 0;

String dbg_lastApiUrl      = "";
int    dbg_lastHttpCode    = 0;
String dbg_lastApiResponse = "";
String dbg_lastReason      = "";
bool   dbg_lastAccessGranted = false;

// ===================== Локален кеш на карти ====================

struct LocalCard {
  String rfid_uid;      // Wiegand код (DEC), това идва от ESP32
  String card_number;   // Номер на картата (както е в сайта)
  String holder_name;   // Име на притежателя (за debug)
  String valid_from;    // Начална дата (за /clients)
  String valid_until;   // Крайна дата (текстов вид от сървъра)
  int    max_visits;    // Лимит на посещения (0 = неограничено)
  int    visits_count;  // Текущ брой посещения
};

constexpr size_t MAX_LOCAL_CARDS = 1024;
LocalCard localCards[MAX_LOCAL_CARDS];
size_t    localCardCount       = 0;

// Информация за последна синхронизация
unsigned long lastCardsSyncMillis = 0;
const unsigned long CARDS_SYNC_INTERVAL_MS = 2UL * 60UL * 1000UL;  // 2 минути

String lastCardsSyncStatus = "няма синхронизация";
String lastCardsSyncTime   = "";
int    lastCardsSyncCount  = 0;

// ===================== OTA статус ====================

String ota_server_version   = "";
bool   ota_update_required  = false;
String ota_firmware_url     = "";
String ota_last_status      = "not_checked";
unsigned long lastOtaCheckMillis  = 0;
unsigned long lastOtaUpdateMillis = 0;
const unsigned long OTA_CHECK_INTERVAL_MS = 30UL * 60UL * 1000UL; // 30 минути

// ===================== Екран за посетители (/clients) ====================

constexpr unsigned long CLIENT_DISPLAY_TIMEOUT_MS = 20000;

bool          clientDisplayActive       = false;
unsigned long clientDisplayScanMillis   = 0;
bool          clientDisplayGranted      = false;
String        clientDisplayHolderName   = "";
String        clientDisplayCardNumber   = "";
String        clientDisplayValidFrom    = "";
String        clientDisplayValidUntil   = "";
int           clientDisplayVisitsCount  = 0;
int           clientDisplayMaxVisits    = 0;
String        clientDisplayReasonText   = "";

// Отложени заявки към WordPress (не блокират таблета)
bool   pendingVisitLog      = false;
String pendingVisitCardId     = "";
bool   pendingDenyLog         = false;
String pendingDenyLogCardId   = "";

// Обслужва HTTP заявки (таблет /clients) докато чакаме
void serveClientsWhileWaiting(unsigned long ms) {
  unsigned long until = millis() + ms;
  while ((long)(until - millis()) > 0) {
    server.handleClient();
    yield();
  }
}

void flushClientRequests() {
  serveClientsWhileWaiting(120);
}

// ===================== Обработка на Wiegand ====================

void IRAM_ATTR handleWiegandD0() {
  // D0 = логическа 0
  wgData <<= 1;
  wgBitCount++;
  wgLastBitTime = millis();
}

void IRAM_ATTR handleWiegandD1() {
  // D1 = логическа 1
  wgData <<= 1;
  wgData |= 1;
  wgBitCount++;
  wgLastBitTime = millis();
}

// Извличане на прочетения Wiegand код като десетично число (String)
// Игнорираме парити битовете, ако форматът е 26 битов:
// [P] [8 бита facility] [16 бита номер] [P]
String getWiegandCodeString() {
  noInterrupts();
  uint32_t data   = wgData;
  uint8_t  bits   = wgBitCount;
  wgData          = 0;
  wgBitCount      = 0;
  interrupts();

  if (bits == 0) {
    return "";
  }

  // Ако е 26 бита – махаме първия и последния (парити)
  if (bits == 26) {
    // Шифтираме надясно 1 бит, за да махнем първия parity бит,
    // после нулираме най-младшия бит (втория parity) и оставяме 24 бита.
    data >>= 1;          // махаме първия parity
    data &= 0x00FFFFFF;  // оставяме само 24 бита
  }

  // Ако е друг брой битове (34, 32 и т.н.), просто връщаме цялото число.
  return String(data);
}

// ===================== Помощни функции ====================

void setupWiFiMulti() {
  // Добавяме всички конфигурирани мрежи
  if (strlen(WIFI_SSID_1) > 0) {
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASSWORD_1);
    Serial.print("Добавена мрежа 1: ");
    Serial.println(WIFI_SSID_1);
  }
  if (strlen(WIFI_SSID_2) > 0) {
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASSWORD_2);
    Serial.print("Добавена мрежа 2: ");
    Serial.println(WIFI_SSID_2);
  }
  if (strlen(WIFI_SSID_3) > 0) {
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASSWORD_3);
    Serial.print("Добавена мрежа 3: ");
    Serial.println(WIFI_SSID_3);
  }
}

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  Serial.print("Свързване към WiFi");

  // Опитваме се да се свържем с най-силната налична мрежа
  while (wifiMulti.run() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.print("Свързан към: ");
  Serial.println(WiFi.SSID());
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("Сила на сигнала: ");
  Serial.print(WiFi.RSSI());
  Serial.println(" dBm");

  secureClient.setInsecure(); // игнорира TLS проверка
}

// Проверява връзката и превключва на друга мрежа ако е нужно
void checkWiFiConnection() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi връзката е прекъсната. Опит за повторно свързване...");
    
    // Опитваме се да се свържем отново (може да е друга мрежа)
    if (wifiMulti.run(5000) == WL_CONNECTED) {
      Serial.print("Свързан към: ");
      Serial.println(WiFi.SSID());
      Serial.print("IP: ");
      Serial.println(WiFi.localIP());
    } else {
      Serial.println("Неуспешно свързване. Ще опитам отново...");
    }
  }
}

void beep(bool ok) {
  serveClientsWhileWaiting(ok ? 100 : 300);
}

// ===================== HTTP Debug UI ======================

String formatMillis(unsigned long ms) {
  if (ms == 0) return "никога";
  unsigned long sec = ms / 1000;
  unsigned long min = sec / 60;
  unsigned long hrs = min / 60;
  char buf[32];
  if (hrs > 0) {
    snprintf(buf, sizeof(buf), "%luh %lum %lus", hrs, min % 60, sec % 60);
  } else if (min > 0) {
    snprintf(buf, sizeof(buf), "%lum %lus", min, sec % 60);
  } else {
    snprintf(buf, sizeof(buf), "%lus", sec);
  }
  return String(buf);
}

String formatDateShort(const String& raw) {
  if (!raw.length() || raw == "0000-00-00 00:00:00") return "—";
  if (raw.length() >= 10 && raw.charAt(4) == '-' && raw.charAt(7) == '-') {
    return raw.substring(8, 10) + "." + raw.substring(5, 7) + "." + raw.substring(0, 4);
  }
  return raw;
}

// На таблета краят на абонамента се показва като предишния календарен ден.
// Пример: 2026-09-24 00:00:00 -> 23.09.2026
String formatPreviousDateShort(const String& raw) {
  if (!raw.length() || raw == "0000-00-00 00:00:00") return "—";
  if (raw.length() < 10 || raw.charAt(4) != '-' || raw.charAt(7) != '-') return raw;

  int year  = raw.substring(0, 4).toInt();
  int month = raw.substring(5, 7).toInt();
  int day   = raw.substring(8, 10).toInt();
  if (year <= 0 || month < 1 || month > 12 || day < 1 || day > 31) return raw;

  day--;
  if (day == 0) {
    month--;
    if (month == 0) {
      month = 12;
      year--;
    }
    const uint8_t daysInMonth[] = {31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31};
    day = daysInMonth[month - 1];
    bool leap = (year % 4 == 0 && year % 100 != 0) || (year % 400 == 0);
    if (month == 2 && leap) day = 29;
  }

  char buf[11];
  snprintf(buf, sizeof(buf), "%02d.%02d.%04d", day, month, year);
  return String(buf);
}

String reasonToBulgarian(const String& code) {
  if (code == "visits_limit_reached") return "Изчерпани са всички посещения по абонамента";
  if (code == "card_not_found")       return "Няма активен абонамент за тази карта";
  if (code == "card_inactive")        return "Картата е неактивна";
  if (code == "expired")              return "Абонаментът е изтекъл";
  if (code == "not_started")          return "Абонаментът все още не е започнал";
  if (code == "daily_limit_reached")  return "Вече сте чекирали днес";
  if (code == "no_wifi")              return "Няма връзка със сървъра";
  if (code == "http_error")           return "Грешка при връзка със сървъра";
  return "Достъпът е отказан";
}

String subscriptionPlanLabel(int maxVisits) {
  if (maxVisits > 0) return String(maxVisits) + " посещения";
  return "Неограничен абонамент";
}

void updateClientDisplay(bool granted, const String& reason, LocalCard* lc) {
  clientDisplayActive     = true;
  clientDisplayScanMillis = millis();
  clientDisplayGranted    = granted;
  clientDisplayReasonText = granted ? "" : reasonToBulgarian(reason);
  if (lc) {
    clientDisplayHolderName  = lc->holder_name.length() ? lc->holder_name : "—";
    clientDisplayCardNumber  = lc->card_number.length() ? lc->card_number : lc->rfid_uid;
    clientDisplayValidFrom   = formatDateShort(lc->valid_from);
    clientDisplayValidUntil  = formatPreviousDateShort(lc->valid_until);
    clientDisplayMaxVisits   = lc->max_visits;
    clientDisplayVisitsCount = lc->visits_count;
  } else {
    clientDisplayHolderName  = "—";
    clientDisplayCardNumber  = dbg_lastCardId.length() ? dbg_lastCardId : "—";
    clientDisplayValidFrom   = "—";
    clientDisplayValidUntil  = "—";
    clientDisplayMaxVisits   = 0;
    clientDisplayVisitsCount = 0;
  }
}

void sendProgmemChunk(const char* html) {
  char chunk[192];
  size_t offset = 0;
  while (html[offset]) {
    size_t len = 0;
    while (html[offset + len] && len < sizeof(chunk) - 1) {
      chunk[len] = html[offset + len];
      len++;
    }
    chunk[len] = '\0';
    server.sendContent(chunk);
    offset += len;
  }
}

void sendProgmemPage(const char* html) {
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, "text/html; charset=utf-8", "");
  sendProgmemChunk(html);
  server.sendContent("");
}

// Намира карта в локалния кеш по rfid_uid (Wiegand DEC)
LocalCard* findLocalCardByRfid(const String& rfid) {
  if (!rfid.length()) return nullptr;
  for (size_t i = 0; i < localCardCount; i++) {
    if (localCards[i].rfid_uid == rfid) {
      return &localCards[i];
    }
  }
  return nullptr;
}

// Зареждане/обновяване на локалния списък с карти от сървъра
void syncCards() {
  Serial.println("Синхронизиране на карти от сървъра...");
  checkWiFiConnection();

  if (WiFi.status() != WL_CONNECTED) {
    lastCardsSyncStatus = "няма WiFi";
    return;
  }

  String url = String(API_BASE_URL) + "/cards";
  HTTPClient https;
  https.begin(secureClient, url);
  https.setTimeout(8000);

  Serial.print("GET ");
  Serial.println(url);

  int httpCode = https.GET();
  if (httpCode <= 0) {
    Serial.print("HTTP грешка при /cards: ");
    Serial.println(https.errorToString(httpCode));
    lastCardsSyncStatus = "http_error";
    https.end();
    return;
  }

  String payload = https.getString();
  https.end();

  StaticJsonDocument<16384> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.print("JSON грешка при /cards: ");
    Serial.println(err.c_str());
    lastCardsSyncStatus = "json_error";
    return;
  }

  // Обновяваме локалния списък
  JsonArray cards = doc["cards"].as<JsonArray>();
  localCardCount = 0;

  for (JsonObject c : cards) {
    if (localCardCount >= MAX_LOCAL_CARDS) break;

    LocalCard &lc = localCards[localCardCount++];
    lc.rfid_uid    = String((const char *)(c["rfid_uid"] | ""));
    lc.card_number = String((const char *)(c["card_number"] | ""));
    lc.holder_name = String((const char *)(c["holder_name"] | ""));
    lc.valid_from  = String((const char *)(c["valid_from"] | ""));
    lc.valid_until = String((const char *)(c["valid_until"] | ""));
    lc.max_visits  = (int)(c["max_visits"] | 0);
    lc.visits_count = (int)(c["visits_count"] | 0);
  }

  lastCardsSyncMillis = millis();
  lastCardsSyncTime   = String((const char *)(doc["server_time"] | ""));
  lastCardsSyncCount  = (int)localCardCount;
  lastCardsSyncStatus = "ok";

  Serial.print("Синхронизирани карти: ");
  Serial.println(localCardCount);
}

void syncCardsIfNeeded() {
  unsigned long now = millis();
  if (lastCardsSyncMillis == 0 || now - lastCardsSyncMillis >= CARDS_SYNC_INTERVAL_MS) {
    syncCards();
  }
}

// Проверка за OTA ъпдейт към WordPress плъгина
void checkForOtaUpdate() {
  unsigned long now = millis();
  if (lastOtaCheckMillis != 0 && (now - lastOtaCheckMillis) < OTA_CHECK_INTERVAL_MS) {
    return;
  }
  lastOtaCheckMillis = now;

  checkWiFiConnection();
  if (WiFi.status() != WL_CONNECTED) {
    ota_last_status = "no_wifi";
    return;
  }

  String url = String(API_BASE_URL) + "/ota";
  HTTPClient https;
  https.begin(secureClient, url);
  https.setTimeout(8000);

  Serial.print("GET ");
  Serial.println(url);

  int httpCode = https.GET();
  if (httpCode <= 0) {
    Serial.print("HTTP грешка при /ota: ");
    Serial.println(https.errorToString(httpCode));
    ota_last_status = "http_error";
    https.end();
    return;
  }

  String payload = https.getString();
  https.end();

  StaticJsonDocument<1024> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.print("JSON грешка при /ota: ");
    Serial.println(err.c_str());
    ota_last_status = "json_error";
    return;
  }

  const char* server_version = doc["version"] | "";
  bool required = doc["required"] | false;
  const char* fw_url = doc["firmware_url"] | "";

  ota_server_version  = String(server_version);
  ota_update_required = required;
  ota_firmware_url    = String(fw_url);

  if (!required || ota_server_version.length() == 0 || ota_firmware_url.length() == 0) {
    ota_last_status = "no_update";
    return;
  }

  // Ако версията съвпада, няма нужда от ъпдейт
  if (ota_server_version == String(CURRENT_FIRMWARE_VERSION)) {
    ota_last_status = "same_version";
    return;
  }

  Serial.print("OTA: намерена нова версия ");
  Serial.println(ota_server_version);

  // Изтегляме firmware-a
  HTTPClient dl;
  dl.begin(secureClient, ota_firmware_url);
  dl.setTimeout(15000);

  int dlCode = dl.GET();
  if (dlCode != HTTP_CODE_OK) {
    Serial.print("OTA download error: ");
    Serial.println(dl.errorToString(dlCode));
    ota_last_status = "download_error";
    dl.end();
    return;
  }

  int contentLength = dl.getSize();
  if (contentLength <= 0) {
    Serial.println("OTA: invalid content length");
    ota_last_status = "invalid_length";
    dl.end();
    return;
  }

  WiFiClient* stream = dl.getStreamPtr();

  if (!Update.begin(contentLength)) {
    Serial.println("OTA: Update.begin failed");
    ota_last_status = "update_begin_failed";
    dl.end();
    return;
  }

  Serial.println("OTA: започва писане...");
  size_t written = Update.writeStream(*stream);
  if (written != (size_t)contentLength) {
    Serial.print("OTA: непълно писане, written=");
    Serial.print(written);
    Serial.print(" expected=");
    Serial.println(contentLength);
    ota_last_status = "write_failed";
    dl.end();
    Update.abort();
    return;
  }

  if (!Update.end()) {
    Serial.print("OTA: завършване с грешка: ");
    Serial.println(Update.getError());
    ota_last_status = "update_end_failed";
    dl.end();
    return;
  }

  dl.end();

  if (Update.isFinished()) {
    Serial.println("OTA: успешно обновяване, рестарт...");
    ota_last_status = "updated_to_" + ota_server_version;
    lastOtaUpdateMillis = millis();
    delay(1000);
    ESP.restart();
  } else {
    Serial.println("OTA: Update не приключи коректно");
    ota_last_status = "not_finished";
  }
}

void handleRoot() {
  String html;
  html.reserve(4096);

  html += F(
    "<!DOCTYPE html><html><head><meta charset='utf-8'>"
    "<meta name='viewport' content='width=device-width,initial-scale=1'>"
    "<meta http-equiv='refresh' content='2'>"
    "<title>Gym Access Debug</title>"
    "<style>"
    "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;"
    "background:#f5f5f5;margin:0;padding:16px;color:#222;}"
    "h1{font-size:22px;margin:0 0 12px;}"
    ".card{background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);"
    "padding:12px 16px;margin-bottom:12px;}"
    "table{width:100%;border-collapse:collapse;font-size:14px;}"
    "th,td{text-align:left;padding:6px 4px;vertical-align:top;}"
    "th{width:140px;color:#555;font-weight:600;}"
    "code{background:#eee;padding:2px 4px;border-radius:4px;font-size:13px;}"
    ".ok{color:#0a0;font-weight:600;}"
    ".err{color:#a00;font-weight:600;}"
    ".mono{font-family:SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;}"
    "</style></head><body>"
  );

  html += F("<h1>Gym Access ESP32 Debug</h1>");

  // WiFi
  html += F("<div class='card'><table>");
  html += F("<tr><th>WiFi статус</th><td>");
  if (WiFi.status() == WL_CONNECTED) {
    html += "<span class='ok'>Свързан</span>";
  } else {
    html += "<span class='err'>Няма връзка</span>";
  }
  html += F("</td></tr>");
  
  if (WiFi.status() == WL_CONNECTED) {
    html += F("<tr><th>Мрежа (SSID)</th><td><code>");
    html += WiFi.SSID();
    html += F("</code></td></tr>");
    
    html += F("<tr><th>IP адрес</th><td><code>");
    html += WiFi.localIP().toString();
    html += F("</code></td></tr>");
    
    html += F("<tr><th>Сила на сигнала</th><td>");
    int rssi = WiFi.RSSI();
    if (rssi > -50) {
      html += "<span class='ok'>";
    } else if (rssi > -70) {
      html += "<span style='color:#fa0;font-weight:600;'>";
    } else {
      html += "<span class='err'>";
    }
    html += String(rssi) + " dBm";
    html += "</span></td></tr>";
  }
  html += F("</table></div>");

  // Локален кеш на карти
  html += F("<div class='card'><h2>Локален списък с карти (ESP32)</h2><table>");
  html += F("<tr><th>Статус</th><td>");
  html += lastCardsSyncStatus.length() ? lastCardsSyncStatus : "—";
  html += F("</td></tr>");

  html += F("<tr><th>Последна синхронизация</th><td>");
  html += lastCardsSyncTime.length() ? lastCardsSyncTime : "—";
  html += F("</td></tr>");

  html += F("<tr><th>Брой карти</th><td>");
  html += String(lastCardsSyncCount);
  html += F("</td></tr>");

  // Показваме до 20 карти за преглед
  int toShow = (int)localCardCount;
  if (toShow > 20) toShow = 20;

  html += F("</table>");

  if (toShow > 0) {
    html += F("<table style='margin-top:8px;'><thead><tr>");
    html += F("<th>Wiegand (DEC)</th><th>Карта №</th><th>Име</th><th>Валидна до</th>");
    html += F("</tr></thead><tbody>");

    for (int i = 0; i < toShow; i++) {
      LocalCard &lc = localCards[i];
      html += F("<tr><td><code>");
      html += lc.rfid_uid.length() ? lc.rfid_uid : "—";
      html += F("</code></td><td><code>");
      html += lc.card_number.length() ? lc.card_number : "—";
      html += F("</code></td><td>");
      html += lc.holder_name.length() ? lc.holder_name : "—";
      html += F("</td><td>");
      html += lc.valid_until.length() ? lc.valid_until : "—";
      html += F("</td></tr>");
    }

    html += F("</tbody></table>");

    if (localCardCount > toShow) {
      html += F("<p style='margin-top:4px;font-size:12px;color:#666;'>Показани са първите ");
      html += String(toShow);
      html += F(" от ");
      html += String(localCardCount);
      html += F(" карти.</p>");
    }
  }

  html += F("</div>");

  // Последна карта
  html += F("<div class='card'><h2>Последна карта</h2><table>");
  html += F("<tr><th>Wiegand код</th><td><code>");
  html += dbg_lastCardId.length() ? dbg_lastCardId : "—";
  html += F("</code></td></tr>");

  html += F("<tr><th>Преди колко</th><td>");
  if (dbg_lastScanMillis == 0) {
    html += "—";
  } else {
    html += formatMillis(millis() - dbg_lastScanMillis);
  }
  html += F("</td></tr>");

  html += F("<tr><th>Резултат</th><td>");
  if (!dbg_lastCardId.length()) {
    html += "—";
  } else if (dbg_lastAccessGranted) {
    html += F("<span class='ok'>Достъп разрешен</span>");
  } else {
    html += F("<span class='err'>Достъп отказан</span>");
  }
  html += F("</td></tr>");

  html += F("<tr><th>Reason</th><td><code>");
  html += dbg_lastReason.length() ? dbg_lastReason : "—";
  html += F("</code></td></tr>");

  html += F("</table></div>");

  // OTA статус
  html += F("<div class='card'><h2>OTA firmware</h2><table>");
  html += F("<tr><th>Текуща версия (устройство)</th><td><code>");
  html += CURRENT_FIRMWARE_VERSION;
  html += F("</code></td></tr>");

  html += F("<tr><th>Версия на сървъра</th><td><code>");
  html += ota_server_version.length() ? ota_server_version : "—";
  html += F("</code></td></tr>");

  html += F("<tr><th>Маркиран ъпдейт</th><td>");
  if (ota_update_required) {
    html += "<span class='ok'>Да</span>";
  } else {
    html += "<span style='color:#999;'>Не</span>";
  }
  html += F("</td></tr>");

  html += F("<tr><th>Последна проверка</th><td>");
  html += lastOtaCheckMillis ? formatMillis(millis() - lastOtaCheckMillis) + " назад" : "никога";
  html += F("</td></tr>");

  html += F("<tr><th>Последен успешен OTA</th><td>");
  html += lastOtaUpdateMillis ? formatMillis(millis() - lastOtaUpdateMillis) + " назад" : "няма";
  html += F("</td></tr>");

  html += F("<tr><th>Статус</th><td><code>");
  html += ota_last_status.length() ? ota_last_status : "—";
  html += F("</code></td></tr>");

  html += F("</table></div>");

  // HTTP
  html += F("<div class='card'><h2>Последна HTTP заявка</h2><table>");
  html += F("<tr><th>URL</th><td class='mono'><code>");
  html += dbg_lastApiUrl.length() ? dbg_lastApiUrl : "—";
  html += F("</code></td></tr>");

  html += F("<tr><th>HTTP код</th><td>");
  if (dbg_lastHttpCode == 0) html += "—";
  else html += String(dbg_lastHttpCode);
  html += F("</td></tr>");

  html += F("<tr><th>Отговор (JSON)</th><td><pre class='mono' style='white-space:pre-wrap;'>");
  html += dbg_lastApiResponse.length() ? dbg_lastApiResponse : "—";
  html += F("</pre></td></tr>");

  html += F("</table></div>");

  html += F("</body></html>");

  server.send(200, "text/html; charset=utf-8", html);
}

void handleClientsStatus() {
  StaticJsonDocument<768> doc;

  bool showCheckin = clientDisplayActive &&
    (millis() - clientDisplayScanMillis < CLIENT_DISPLAY_TIMEOUT_MS);

  doc["state"] = showCheckin ? "checkin" : "idle";
  doc["wifi"]  = (WiFi.status() == WL_CONNECTED);

  if (showCheckin) {
    JsonObject c = doc.createNestedObject("checkin");
    c["holder_name"]    = clientDisplayHolderName;
    c["card_number"]    = clientDisplayCardNumber;
    c["access_granted"] = clientDisplayGranted;
    c["reason_text"]    = clientDisplayReasonText;
    c["valid_from"]     = clientDisplayValidFrom;
    c["valid_until"]    = clientDisplayValidUntil;
    c["visits_count"]   = clientDisplayVisitsCount;
    c["max_visits"]     = clientDisplayMaxVisits;
    c["unlimited"]      = (clientDisplayMaxVisits <= 0);

    if (clientDisplayMaxVisits > 0) {
      int remaining = clientDisplayMaxVisits - clientDisplayVisitsCount;
      if (remaining < 0) remaining = 0;
      c["visits_remaining"] = remaining;
    } else {
      c["visits_remaining"] = -1;
    }

    c["plan_label"] = subscriptionPlanLabel(clientDisplayMaxVisits);
  }

  String out;
  serializeJson(doc, out);
  server.sendHeader("Cache-Control", "no-store, no-cache, must-revalidate");
  server.send(200, "application/json; charset=utf-8", out);
}

void handleClients() {
  static const char CLIENTS_HTML[] PROGMEM = R"rawhtml(
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Fitness Center Grace</title>
<style>
:root{--bg:#070b10;--panel:#121820;--panel2:#1a2330;--text:#f3f6fa;--muted:#9aa8b8;--ok:#22c55e;--ok2:#86efac;--err:#ef4444;--err2:#fca5a5;--accent:var(--ok);--accent2:var(--ok2);--border:rgba(255,255,255,.08)}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
body{display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.screen{display:none;flex:1;flex-direction:column;padding:clamp(12px,2vw,24px);gap:clamp(12px,2vh,20px)}
.screen.active{display:flex}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:12px}
.brand{font-weight:700;font-size:clamp(16px,2.5vw,22px);letter-spacing:.02em;flex-shrink:0}
.meta{display:flex;align-items:center;gap:14px;color:var(--muted);font-size:clamp(12px,1.6vw,15px)}
.wifi{width:10px;height:10px;border-radius:50%;background:var(--ok);box-shadow:0 0 10px var(--ok)}
.wifi.off{background:var(--err);box-shadow:0 0 10px var(--err)}
.hero{text-align:center;padding:clamp(8px,2vh,16px) 0}
.status-icon{width:clamp(72px,14vw,110px);height:clamp(72px,14vw,110px);margin:0 auto 12px;border-radius:50%;display:grid;place-items:center;border:3px solid var(--accent);color:var(--accent);box-shadow:0 0 30px color-mix(in srgb,var(--accent) 45%,transparent)}
.status-icon svg{width:55%;height:55%}
.hero h1{font-size:clamp(28px,6vw,52px);line-height:1.05;color:var(--accent);letter-spacing:.03em}
.hero p{margin-top:8px;color:var(--muted);font-size:clamp(14px,2.2vw,20px)}
.grid-main{display:grid;grid-template-columns:1fr;gap:clamp(12px,2vh,18px);flex:1;min-height:0}
@media (orientation:landscape) and (min-width:700px){.grid-main{grid-template-columns:1.1fr 1fr 1fr;align-items:stretch}.hero{text-align:left;display:flex;align-items:center;gap:18px;padding:0}.status-icon{margin:0;flex:0 0 auto}.hero-copy{min-width:0}}
.card{background:var(--panel);border:1px solid var(--border);border-radius:18px;padding:clamp(14px,2vw,22px);display:flex;flex-direction:column;gap:14px;min-height:0}
.card-title{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:clamp(11px,1.5vw,13px);text-transform:uppercase;letter-spacing:.08em;font-weight:700}
.user-row{display:flex;align-items:center;gap:16px;min-width:0}
.avatar{width:clamp(64px,10vw,92px);height:clamp(64px,10vw,92px);border-radius:50%;background:var(--panel2);border:2px solid color-mix(in srgb,var(--accent) 35%,transparent);display:grid;place-items:center;flex:0 0 auto}
.avatar svg{width:52%;height:52%;color:var(--accent)}
.user-name{font-size:clamp(24px,4.5vw,40px);font-weight:800;line-height:1.1;word-break:break-word}
.card-id{margin-top:8px;color:var(--muted);font-size:clamp(12px,1.8vw,15px)}
.card-id strong{color:var(--accent);font-size:clamp(16px,2.4vw,22px);letter-spacing:.06em}
.dates{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.date-box{background:var(--panel2);border-radius:12px;padding:12px;border:1px solid var(--border)}
.date-box label{display:block;color:var(--muted);font-size:clamp(10px,1.4vw,12px);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em}
.date-box .val{font-size:clamp(18px,3vw,28px);font-weight:700;display:flex;align-items:center;gap:8px}
.dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto}
.dot.ok{background:var(--ok)} .dot.err{background:var(--err)}
.plan{font-size:clamp(14px,2vw,18px);color:var(--accent2);font-weight:600}
.visits{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:10px;text-align:center;flex:1}
.visits .num{font-size:clamp(28px,5vw,44px);font-weight:800;line-height:1}
.visits .lbl{color:var(--muted);font-size:clamp(11px,1.5vw,13px);text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.ring{width:clamp(88px,14vw,120px);height:clamp(88px,14vw,120px);border-radius:50%;display:grid;place-items:center;margin:0 auto;background:conic-gradient(var(--accent) calc(var(--pct)*1%),#243041 0);position:relative}
.ring::after{content:'';position:absolute;inset:10px;border-radius:50%;background:var(--panel)}
.ring span{position:relative;z-index:1;font-size:clamp(12px,2vw,16px);font-weight:700;color:var(--muted);line-height:1.2}
.unlimited{font-size:clamp(22px,3.5vw,32px);font-weight:800;color:var(--accent);padding:18px 0}
.footer{margin-top:auto;background:color-mix(in srgb,var(--accent) 18%,var(--panel));border:1px solid color-mix(in srgb,var(--accent) 35%,transparent);border-radius:16px;padding:clamp(12px,2vh,18px) clamp(14px,2vw,22px);display:flex;align-items:center;gap:12px}
.footer svg{width:clamp(28px,4vw,36px);height:clamp(28px,4vw,36px);color:var(--accent);flex:0 0 auto}
.footer strong{display:block;font-size:clamp(14px,2.2vw,20px);color:var(--accent);letter-spacing:.04em}
.footer small{color:var(--muted);font-size:clamp(12px,1.8vw,15px)}
.idle-center{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:18px;padding:20px}
.idle-icon{width:clamp(90px,16vw,130px);height:clamp(90px,16vw,130px);border-radius:50%;border:2px dashed rgba(255,255,255,.18);display:grid;place-items:center;color:var(--muted);animation:pulse 2.4s ease-in-out infinite}
.idle-icon svg{width:46%;height:46%}
.idle-center h2{font-size:clamp(24px,4.5vw,40px);font-weight:800}
.idle-center p{color:var(--muted);font-size:clamp(14px,2.2vw,20px);max-width:520px}
@keyframes pulse{0%,100%{transform:scale(1);opacity:.85}50%{transform:scale(1.04);opacity:1}}
body.denied{--accent:var(--err);--accent2:var(--err2)}
</style>
</head>
<body>
<div id="idle" class="screen active">
  <div class="topbar">
    <div class="brand">Fitness Center Grace</div>
    <div class="meta"><span id="idleClock">--:--:--</span><span id="idleDate">--.--.----</span><span id="idleWifi" class="wifi off"></span></div>
  </div>
  <div class="idle-center">
    <div class="idle-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M7 9h4M7 13h7"/></svg></div>
    <h2>Приближете картата си до четеца</h2>
    <p>Дръжте RFID картата до четеца, за да видите информация за достъпа и абонамента си.</p>
  </div>
</div>
<div id="checkin" class="screen">
  <div class="topbar">
    <div class="brand">Fitness Center Grace</div>
    <div class="meta"><span id="clock">--:--:--</span><span id="date">--.--.----</span><span id="wifiDot" class="wifi off"></span></div>
  </div>
  <div class="hero">
    <div class="status-icon" id="statusIcon"></div>
    <div class="hero-copy">
      <h1 id="statusTitle">ДОСТЪП РАЗРЕШЕН</h1>
      <p id="statusSubtitle">Добре дошли във фитнес център Grace!</p>
    </div>
  </div>
  <div class="grid-main">
    <section class="card">
      <div class="card-title">Информация за потребителя</div>
      <div class="user-row">
        <div class="avatar"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg></div>
        <div>
          <div class="user-name" id="holderName">—</div>
          <div class="card-id">Карта № <strong id="cardNumber">—</strong></div>
        </div>
      </div>
    </section>
    <section class="card">
      <div class="card-title">Абонамент</div>
      <div class="plan" id="planLabel">—</div>
      <div class="dates">
        <div class="date-box"><label>Начало</label><div class="val"><span class="dot ok"></span><span id="validFrom">—</span></div></div>
        <div class="date-box"><label>Край</label><div class="val"><span class="dot err"></span><span id="validUntil">—</span></div></div>
      </div>
    </section>
    <section class="card">
      <div class="card-title">Тренировки / Чекирания</div>
      <div id="visitsLimited" class="visits">
        <div><div class="num" id="visitsDone">0</div><div class="lbl">Извършени</div></div>
        <div><div class="ring" id="visitRing" style="--pct:0"><span>ОТ<br><strong id="visitsMax">0</strong></span></div></div>
        <div><div class="num" id="visitsLeft">0</div><div class="lbl">Оставащи</div></div>
      </div>
      <div id="visitsUnlimited" class="unlimited" style="display:none">Неограничени посещения</div>
    </section>
  </div>
  <div class="footer">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v6c0 5 3.5 8.7 8 9 4.5-.3 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
    <div><strong id="footerStatus">СТАТУС: ДОСТЪП РАЗРЕШЕН</strong><small id="footerHint">Приятна тренировка!</small></div>
  </div>
</div>
<script>
const idleEl=document.getElementById('idle');
const checkinEl=document.getElementById('checkin');
const okIcon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>';
const errIcon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>';
function pad(n){return String(n).padStart(2,'0')}
function updateClock(){const d=new Date();const t=pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds());const ds=pad(d.getDate())+'.'+pad(d.getMonth()+1)+'.'+d.getFullYear();['clock','idleClock'].forEach(id=>{const el=document.getElementById(id);if(el)el.textContent=t});['date','idleDate'].forEach(id=>{const el=document.getElementById(id);if(el)el.textContent=ds})}
setInterval(updateClock,1000);updateClock();
function setWifi(on){['wifiDot','idleWifi'].forEach(id=>{const el=document.getElementById(id);if(el)el.classList.toggle('off',!on)})}
function showIdle(){document.body.classList.remove('denied');idleEl.classList.add('active');checkinEl.classList.remove('active')}
function showCheckin(data,granted){idleEl.classList.remove('active');checkinEl.classList.add('active');document.body.classList.toggle('denied',!granted);document.getElementById('statusIcon').innerHTML=granted?okIcon:errIcon;document.getElementById('statusTitle').textContent=granted?'ДОСТЪП РАЗРЕШЕН':'ДОСТЪП ОТКАЗАН';document.getElementById('statusSubtitle').textContent=granted?'Добре дошли във фитнес център Grace!':(data.reason_text||'За повече информация, моля обадете се на телефон 0887 62 20 67');document.getElementById('holderName').textContent=data.holder_name||'—';document.getElementById('cardNumber').textContent=data.card_number||'—';document.getElementById('planLabel').textContent=data.plan_label||'—';document.getElementById('validFrom').textContent=data.valid_from||'—';document.getElementById('validUntil').textContent=data.valid_until||'—';const limited=document.getElementById('visitsLimited');const unlimited=document.getElementById('visitsUnlimited');if(data.unlimited){limited.style.display='none';unlimited.style.display='block'}else{unlimited.style.display='none';limited.style.display='grid';const done=Number(data.visits_count||0);const max=Number(data.max_visits||0);const left=Number(data.visits_remaining||0);const pct=max>0?Math.min(100,Math.round((done/max)*100)):0;document.getElementById('visitsDone').textContent=done;document.getElementById('visitsMax').textContent=max;document.getElementById('visitsLeft').textContent=left;document.getElementById('visitRing').style.setProperty('--pct',pct)}document.getElementById('footerStatus').textContent=granted?'СТАТУС: ДОСТЪП РАЗРЕШЕН':'СТАТУС: ДОСТЪП ОТКАЗАН';document.getElementById('footerHint').textContent=granted?'Приятна тренировка!':(data.reason_text||'')}
async function refresh(){try{const res=await fetch('/clients/status',{cache:'no-store'});const data=await res.json();setWifi(!!data.wifi);if(data.state==='checkin'&&data.checkin){showCheckin(data.checkin,!!data.checkin.access_granted)}else{showIdle()}}catch(e){}}
setInterval(refresh,50);refresh();
</script>
</body>
</html>
)rawhtml";

  sendProgmemPage(CLIENTS_HTML);
}

void handleNotFound() {
  server.send(404, "text/plain", "Not found");
}

// ===================== API към WP =========================

// Локална проверка по кеширани карти (без remote /check)
bool checkAccessFast(const String& cardId, String &reason) {
  // 1) Опитваме локална проверка
  LocalCard* lc = findLocalCardByRfid(cardId);
  if (lc) {
    // Проверка за лимит на посещенията (ако има зададен лимит)
    if (lc->max_visits > 0 && lc->visits_count >= lc->max_visits) {
      reason = "visits_limit_reached";
      dbg_lastAccessGranted = false;
      dbg_lastReason        = reason;
      return false;
    }

    // /cards връща само активни и валидни карти (по дати),
    // затова тук приемаме, че останалите условия са изпълнени.
    reason = "ok_local";
    dbg_lastAccessGranted = true;
    dbg_lastReason        = reason;
    return true;
  }
  
  // Няма такава карта в кеша
  reason = "card_not_found";
  dbg_lastAccessGranted = false;
  dbg_lastReason        = reason;
  return false;
}

// Регистрира посещение в WordPress (ползваме /visit за сървърно решение)
// Връща true само ако сървърът разреши посещението.
bool registerVisitRemote(const String& cardId, String &serverReason, int *serverVisitsCount = nullptr) {
  serverReason = "";
  checkWiFiConnection();
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi не е свързан – пропускаме логването на посещението");
    serverReason = "no_wifi";
    return false;
  }

  String url = String(API_BASE_URL) + "/visit";
  HTTPClient https;
  https.begin(secureClient, url);
  https.setTimeout(5000);
  https.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "card=" + cardId;

  Serial.print("POST ");
  Serial.println(url);

  int httpCode = https.POST(body);
  if (httpCode <= 0) {
    Serial.print("HTTP грешка при /visit: ");
    Serial.println(https.errorToString(httpCode));
    https.end();
    serverReason = "http_error";
    return false;
  }

  String payload = https.getString();
  https.end();

  Serial.print("Отговор от /visit: ");
  Serial.println(payload);
  
  StaticJsonDocument<1024> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    serverReason = "json_error";
  } else {
    if (httpCode >= 200 && httpCode < 300) {
      serverReason = "ok";
      if (serverVisitsCount) {
        *serverVisitsCount = (int)(doc["visits_count"] | -1);
      }
    } else {
      // При WP_Error форматът е { code: "...", message: "...", data: {...} }
      serverReason = String((const char *)(doc["code"] | "denied"));
    }
  }
  
  dbg_lastApiUrl      = url;
  dbg_lastHttpCode    = httpCode;
  dbg_lastApiResponse = payload;
  dbg_lastReason      = serverReason;

  return (httpCode >= 200 && httpCode < 300);
}

// Записва отказан достъп в хронологията (без регистриране на посещение)
void logAccessCheckRemote(const String& cardId) {
  checkWiFiConnection();
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi не е свързан – пропускаме логването на отказа");
    return;
  }

  String url = String(API_BASE_URL) + "/check?card=" + cardId + "&register=false";
  HTTPClient https;
  https.begin(secureClient, url);
  https.setTimeout(5000);

  Serial.print("GET (log) ");
  Serial.println(url);

  int httpCode = https.GET();
  if (httpCode <= 0) {
    Serial.print("HTTP грешка при /check (log): ");
    Serial.println(https.errorToString(httpCode));
  } else {
    Serial.print("Отговор от /check (log): ");
    Serial.println(https.getString());
  }
  https.end();
}

void processPendingRemoteOps() {
  if (pendingVisitLog) {
    pendingVisitLog = false;
    String cardId = pendingVisitCardId;
    pendingVisitCardId = "";

    String serverReason;
    int serverVisits = -1;
    if (registerVisitRemote(cardId, serverReason, &serverVisits)) {
      LocalCard* lc = findLocalCardByRfid(cardId);
      if (lc && lc->max_visits > 0 && serverVisits >= 0) {
        lc->visits_count = serverVisits;
        updateClientDisplay(true, "ok_local", lc);
        flushClientRequests();
      }
    }
  }

  if (pendingDenyLog) {
    pendingDenyLog = false;
    String cardId = pendingDenyLogCardId;
    pendingDenyLogCardId = "";
    logAccessCheckRemote(cardId);
  }
}

// ===================== Локални действия ===================

void allowAccess() {
  Serial.println("Достъп РАЗРЕШЕН");
  digitalWrite(PIN_LED_OK, HIGH);
  digitalWrite(PIN_RELAY, RELAY_ACTIVE_LOW ? LOW : HIGH);
  beep(true);
  serveClientsWhileWaiting(DOOR_OPEN_TIME_MS);
  digitalWrite(PIN_RELAY, RELAY_ACTIVE_LOW ? HIGH : LOW);
  digitalWrite(PIN_LED_OK, LOW);
}

void denyAccess(const String& reason) {
  Serial.print("Достъп ОТКАЗАН: ");
  Serial.println(reason);
  digitalWrite(PIN_LED_ERR, HIGH);
  beep(false);
  serveClientsWhileWaiting(500);
  digitalWrite(PIN_LED_ERR, LOW);
}

// ===================== setup / loop =======================

void setup() {
  Serial.begin(115200);
  delay(1000);

  pinMode(PIN_RELAY,   OUTPUT);
  pinMode(PIN_LED_OK,  OUTPUT);
  pinMode(PIN_LED_ERR, OUTPUT);
  pinMode(PIN_BUZZER,  OUTPUT);

  // Държим релето изключено в idle
  digitalWrite(PIN_RELAY,   RELAY_ACTIVE_LOW ? HIGH : LOW);
  digitalWrite(PIN_LED_OK,  LOW);
  digitalWrite(PIN_LED_ERR, LOW);

  // Wiegand входове
  pinMode(PIN_WG_D0, INPUT_PULLUP);
  pinMode(PIN_WG_D1, INPUT_PULLUP);

  attachInterrupt(digitalPinToInterrupt(PIN_WG_D0), handleWiegandD0, FALLING);
  attachInterrupt(digitalPinToInterrupt(PIN_WG_D1), handleWiegandD1, FALLING);

  setupWiFiMulti();
  connectWiFi();

  // Първоначална синхронизация на картите (ако е възможно)
  syncCards();

  server.on("/", handleRoot);
  server.on("/clients", handleClients);
  server.on("/clients/status", handleClientsStatus);
  server.onNotFound(handleNotFound);
  server.begin();

  Serial.println("HTTP сървърът е стартиран.");
  Serial.println("Debug: http://<IP>/");
  Serial.println("Таблет:  http://<IP>/clients");
}

void loop() {
  server.handleClient();

  // Периодична синхронизация на локалния списък с карти
  syncCardsIfNeeded();

  // Периодична проверка за OTA ъпдейт
  checkForOtaUpdate();

  // Проверяваме дали има завършен Wiegand пакет
  // Ако от последния бит са минали > 25ms, приемаме че пакетът е завършен
  static unsigned long lastCheck = 0;
  unsigned long now = millis();

  if (wgBitCount > 0 && (now - wgLastBitTime) > 25) {
    String cardId = getWiegandCodeString();

    if (cardId.length() > 0) {
      Serial.print("Прочетен Wiegand код: ");
      Serial.println(cardId);

      // Cooldown – игнорираме ако е същата карта в рамките на CARD_COOLDOWN_MS
      if (cardId == lastCardId && (now - lastCardTime) < CARD_COOLDOWN_MS) {
        Serial.println("Игнориране (скорошно сканиране на същата карта)");
      } else {
        lastCardId   = cardId;
        lastCardTime = now;

        dbg_lastCardId     = cardId;
        dbg_lastScanMillis = millis();

        String reason;
        bool access = checkAccessFast(cardId, reason);
        LocalCard* lc = findLocalCardByRfid(cardId);

        // Веднага обновяваме таблета – преди блокиращите allowAccess/denyAccess
        updateClientDisplay(access, reason, lc);
        flushClientRequests();

        if (access) {
          allowAccess();
          pendingVisitLog = true;
          pendingVisitCardId = cardId;
        } else {
          denyAccess(reason);
          pendingDenyLog = true;
          pendingDenyLogCardId = cardId;
        }
      }
    }
  }

  processPendingRemoteOps();

  // Малко забавяне за стабилизиране
  delay(5);
}


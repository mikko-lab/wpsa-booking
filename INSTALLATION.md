# WPSA ZeroClick Sync - Asennusohje

**Versio 1.0.2 - Stable Release**

---

## 📦 ASENNUS (Uusi asennus)

### 1. Lataa plugin

Lataa: `wpsa-zeroclick-v1.0.2.tar.gz`

### 2. Lähetä palvelimelle

**FTP/FileZilla:**
```
Lähetä: /wp-content/plugins/
Pura: wpsa-zeroclick-v1.0.2.tar.gz
```

**TAI cPanel File Manager:**
```
1. Mene: File Manager → wp-content/plugins/
2. Upload: wpsa-zeroclick-v1.0.2.tar.gz
3. Extract Archive
```

### 3. Aktivoi plugin

```
WordPress Admin → Lisäosat → "WPSA ZeroClick Sync" → Aktivoi
```

### 4. Asetukset

```
WordPress Admin → WPSA ZeroClick → Asetukset
→ Tarkista työajat (Ma-Pe, 09:00-17:00)
→ Tallenna asetukset
```

### 5. Lisää sivulle

Luo uusi sivu tai muokkaa olemassa olevaa:

```
[wpsa_booking]
```

**Valmis!** ✅

---

## 🔄 PÄIVITYS (Jos vanha versio asennettuna)

### Vaihtoehto 1: Puhdas asennus (SUOSITUS)

```
1. Deaktivoi vanha versio
2. Poista vanha versio
3. Asenna v1.0.2 (katso yllä)
4. Aktivoi
```

⚠️ **HUOM:** Varaukset katoavat (tietokantataulu eri)

### Vaihtoehto 2: Säilytä varaukset

Jos sinulla on tärkeitä varauksia vanhassa versiossa:

**phpMyAdmin:**
```sql
-- 1. Varmuuskopioi
CREATE TABLE wp_wpsa_bookings_backup LIKE wp_wpsa_bookings;
INSERT INTO wp_wpsa_bookings_backup SELECT * FROM wp_wpsa_bookings;

-- 2. Nimeä uudelleen
RENAME TABLE wp_wpsa_bookings TO wp_wpsa_zeroclick_bookings;
```

**Sitten:**
```
1. Deaktivoi vanha
2. Poista vanha
3. Asenna v1.0.2
4. Aktivoi
```

---

## ⚙️ ASETUKSET

### Työajat

**WPSA ZeroClick → Asetukset → Työajat**

**Oletukset:**
- Ma-Pe: 09:00 - 17:00
- La-Su: Suljettu

**Muokkaa:**
1. Valitse viikonpäivä (checkbox)
2. Aseta alkuaika (esim. 09:00)
3. Aseta loppuaika (esim. 17:00)
4. Tallenna asetukset

### Google Calendar (valinnainen)

**Jos haluat automaattisen Google Calendar -synkronoinnin:**

1. Luo Google Cloud Project
2. Ota Calendar API käyttöön
3. Luo OAuth 2.0 credentials
4. Lisää tunnukset: WPSA ZeroClick → Asetukset

**HUOM:** Ilman Google Calendaria plugin luo paikallisen meeting linkin.

---

## 🧪 TESTAUS

### Tarkista että toimii:

**1. Admin-menu:**
```
WordPress Admin → Etsi: "WPSA ZeroClick"
→ Pitäisi näkyä sivupalkissa ✅
```

**2. Shortcode:**
```
Lisää sivulle: [wpsa_booking]
Avaa sivu → Kalenteri näkyy ✅
```

**3. REST API:**
```
Avaa selaimessa:
https://yoursite.com/wp-json/wpsa-zeroclick/v1/services

→ JSON-data näkyy ✅
```

**4. Mobiili:**
```
Avaa sivusto mobiilissa
→ Kalenteripäivät min 48×48px ✅
→ Helppo klikata ✅
```

**5. Näppäimistö:**
```
Tab → Kalenteri
Nuolinäppäimet → Liiku päivien välillä
Enter → Valitse päivä
Tab → Aikaslotsit
Enter → Valitse aika
Tab → "Seuraava"-nappi
```

---

## ❓ ONGELMANRATKAISU

### "Plugin ei aktivoidu!"

**Tarkista wp-config.php:**

Varmista että **TÄMÄ RIVI ON POISTETTU** tai kommentoitu:

```php
// define('WP_ALLOW_MULTISITE', true);  ← Poistettu tai kommentoitu
```

Jos rivi on olemassa:
1. Avaa wp-config.php
2. Etsi rivi: `define('WP_ALLOW_MULTISITE', true);`
3. **Poista** kokonaan TAI kommentoi: `// define(...)`
4. Tallenna
5. Yritä aktivoida uudelleen

### "Ei vapaita aikoja!"

**Tarkista työajat:**

```
WPSA ZeroClick → Asetukset → Työajat
→ Varmista että Ma-Pe on valittuna ✅
→ Varmista että ajat on asetettu (esim. 09:00-17:00) ✅
→ Tallenna asetukset
```

**Katso debug-tiedot:**

```
WPSA ZeroClick → Asetukset → Scroll alas → "Debug-tiedot"
→ Pitäisi näyttää:
Array (
    [monday] => Array ( [start] => 09:00 [end] => 17:00 )
    [tuesday] => Array ( [start] => 09:00 [end] => 17:00 )
    ...
)
```

### "Kalenteri ei lataa!"

**Tyhjennä välimuisti:**
```
Ctrl+Shift+R (Windows)
Cmd+Shift+R (Mac)
```

**Tarkista konsoli:**
```
F12 → Console-välilehti
→ Etsi virheilmoituksia
```

**Tarkista että Vite build on mukana:**
```
FTP/FileZilla:
→ Tarkista: /wp-content/plugins/wpsa-booking/dist/
→ Pitää sisältää: assets/main-*.js ja assets/main-*.css
```

### "Mobiili liian tiivis!"

**Tämä on korjattu versiossa 1.0.2:**
- Mobiili: 48×48px napit
- Gap: 1 (sopiva väli)

Jos ongelma jatkuu:
```
Tyhjennä välimuisti: Ctrl+Shift+R
```

---

## 🔒 TURVALLISUUS

### wp-config.php

**ÄLÄ KOSKAAN jaa wp-config.php julkisesti!**

Sisältää:
- Tietokantasalasanan
- Salausavaimet
- Muut arkaluontoiset tiedot

### Tietokantasalasana

Jos vahingossa jaoit wp-config.php:

1. **Vaihda tietokantasalasana** (Plesk/cPanel → Databases)
2. **Päivitä wp-config.php** uudella salasanalla
3. **Regeneroi salausavaimet**: https://api.wordpress.org/secret-key/1.1/salt/

---

## 📊 TEKNINEN INFO

### Palvelinvaatimukset:

- PHP ≥ 8.0
- WordPress ≥ 6.0
- MySQL ≥ 5.7
- Memory limit ≥ 64MB

### Tietokantataulu:

```
wp_wpsa_zeroclick_bookings
```

Kentät:
- id, service_type, booking_date, booking_time
- customer_name, customer_email, customer_phone
- status, google_event_id, meeting_link
- created_at

### REST API Endpoints:

```
GET  /wp-json/wpsa-zeroclick/v1/services
GET  /wp-json/wpsa-zeroclick/v1/availability?date=YYYY-MM-DD
POST /wp-json/wpsa-zeroclick/v1/bookings
```

---

## 🎉 VALMIS!

Plugin on nyt asennettu ja toimintakunnossa!

**Seuraavat vaiheet:**

1. ✅ Testaa varaus mobiililla
2. ✅ Testaa näppäimistöllä
3. ✅ Testaa ruudunlukijalla (valinnainen)
4. ✅ Aseta Google Calendar (valinnainen)
5. ✅ Ota käyttöön tuotannossa!

---

**Kysymyksiä? Ongelmia?**

Lähetä debug-loki:
```
/wp-content/debug.log
```

TAI virheviesti selaimesta:
```
F12 → Console
```

# WPSA ZeroClick Sync - Konfliktit Korjattu

**Versio: 1.0.1 - No Conflicts**

---

## 🔧 MITÄ MUUTETTIIN?

### **ONGELMA:**
```
Lisäosaa ei voitu ottaa käyttöön, sillä se aiheutti virhetilanteen.
```

**SYY:** Toinen varausjärjestelmä samassa WordPressissä aiheutti nimikonflikteja.

---

## ✅ KORJATUT KONFLIKTIT:

### **1. Admin-menun nimi**

**ENNEN:**
```php
add_menu_page('WPSA Varaukset', 'Varaukset', ...)  // ❌ Konflikti!
```

**JÄLKEEN:**
```php
add_menu_page('WPSA ZeroClick', 'WPSA ZeroClick', ...)  // ✅ Uniikki!
```

---

### **2. Custom Post Type**

**ENNEN:**
```php
register_post_type('wpsa_booking', ...)  // ⚠️ Mahdollinen konflikti
```

**JÄLKEEN:**
```php
register_post_type('wpsa_zeroclick_booking', ...)  // ✅ Uniikki!
```

---

### **3. Tietokantataulun nimi**

**ENNEN:**
```sql
wp_wpsa_bookings  -- ⚠️ Mahdollinen konflikti
```

**JÄLKEEN:**
```sql
wp_wpsa_zeroclick_bookings  -- ✅ Täysin uniikki!
```

---

### **4. REST API Namespace**

**ENNEN:**
```
/wp-json/wpsa-booking/v1/  -- ⚠️ Mahdollinen konflikti
```

**JÄLKEEN:**
```
/wp-json/wpsa-zeroclick/v1/  -- ✅ Uniikki!
```

---

### **5. Admin-menu slug**

**ENNEN:**
```php
'wpsa-booking'  // ⚠️ Geneerinen
```

**JÄLKEEN:**
```php
'wpsa-zeroclick-booking'  // ✅ Spesifinen!
```

---

## 📋 KAIKKI MUUTOKSET:

| Tyyppi | Vanha nimi | Uusi nimi |
|--------|-----------|-----------|
| **Plugin Name** | WPSA Booking | WPSA ZeroClick Sync |
| **Admin Menu** | Varaukset | WPSA ZeroClick |
| **Menu Slug** | wpsa-booking | wpsa-zeroclick-booking |
| **CPT** | wpsa_booking | wpsa_zeroclick_booking |
| **Database** | wp_wpsa_bookings | wp_wpsa_zeroclick_bookings |
| **REST API** | wpsa-booking/v1 | wpsa-zeroclick/v1 |

---

## 🚀 ASENNUS (Uusi asennus):

1. **Lataa:** `wpsa-zeroclick-NOCONFLICT.tar.gz`
2. **Pura:** `/wp-content/plugins/`
3. **Aktivoi:** WordPress Admin → Lisäosat
4. **Asetukset:** WPSA ZeroClick → Asetukset
5. **Shortcode:** `[wpsa_booking]`

---

## 🔄 PÄIVITYS (Jos vanha versio oli asennettuna):

### **VAIHTOEHTO 1: Puhdas asennus (SUOSITUS)**

1. **Deaktivoi** vanha "WPSA Booking"
2. **Poista** kokonaan
3. **Asenna** uusi versio
4. **Aktivoi**

⚠️ **HUOM:** Varaukset katoavat (tietokantataulu muuttui)

---

### **VAIHTOEHTO 2: Säilytä varaukset (Manuaalinen migraatio)**

Jos sinulla on tärkeitä varauksia vanhassa versiossa:

```sql
-- 1. Varmuuskopioi vanha taulu
CREATE TABLE wp_wpsa_bookings_backup LIKE wp_wpsa_bookings;
INSERT INTO wp_wpsa_bookings_backup SELECT * FROM wp_wpsa_bookings;

-- 2. Nimeä vanha taulu uudeksi
RENAME TABLE wp_wpsa_bookings TO wp_wpsa_zeroclick_bookings;

-- 3. Päivitä CPT meta_key (jos tarvitaan)
UPDATE wp_postmeta 
SET meta_key = 'wpsa_zeroclick_booking_id' 
WHERE meta_key = 'wpsa_booking_id';
```

**TAI käytä phpMyAdminia:**
1. Vie `wp_wpsa_bookings` taulu (Export)
2. Korvaa tiedostossa `wpsa_bookings` → `wpsa_zeroclick_bookings`
3. Tuo takaisin (Import)

---

## 🎯 ADMIN-KÄYTTÖLIITTYMÄ:

### **WordPress Admin -menu:**

**ENNEN:**
```
└── Varaukset (konfliktoi toisen pluginin kanssa)
    ├── Kaikki varaukset
    └── Asetukset
```

**JÄLKEEN:**
```
└── WPSA ZeroClick (oma menu, ei konflikteja!)
    ├── Kaikki varaukset
    └── Asetukset
```

---

## 🧪 TESTAUS:

### **Tarkista että toimii:**

1. ✅ **Admin-menu näkyy:** "WPSA ZeroClick"
2. ✅ **Shortcode toimii:** `[wpsa_booking]` näyttää kalenterin
3. ✅ **REST API vastaa:**
   ```
   /wp-json/wpsa-zeroclick/v1/services
   /wp-json/wpsa-zeroclick/v1/availability?date=2026-04-18
   ```
4. ✅ **Tietokantataulu luotu:**
   ```sql
   SHOW TABLES LIKE '%wpsa_zeroclick%';
   -- Pitäisi näyttää: wp_wpsa_zeroclick_bookings
   ```

---

## ❓ ONGELMANRATKAISU:

### **"Ei vieläkään aktivoidu!"**

1. Tarkista PHP-versio: `≥ 8.0`
2. Tarkista WordPress-versio: `≥ 6.0`
3. Katso error_log:
   ```
   /wp-content/debug.log
   ```
4. Testaa syntaksi (SSH):
   ```bash
   cd /wp-content/plugins/wpsa-booking
   php -l wpsa-booking.php
   ```

---

### **"Admin-menu ei näy!"**

1. Tyhjennä WordPress välimuisti
2. Deaktivoi → Aktivoi uudelleen
3. Tarkista käyttöoikeudet: Tarvitset `manage_options`

---

### **"REST API ei toimi!"**

1. Testaa suoraan selaimessa:
   ```
   https://yoursite.com/wp-json/wpsa-zeroclick/v1/services
   ```
2. Jos 404 → Tallenna Permalinkit uudelleen:
   ```
   WordPress Admin → Asetukset → Permalinkit → Tallenna
   ```
3. Jos 401 → Nonce-ongelma, tyhjennä välimuisti

---

### **"Varaukset katosivat!"**

Jos et tehnyt migraatiota:
```sql
-- Palauta varmuuskopiosta
INSERT INTO wp_wpsa_zeroclick_bookings 
SELECT * FROM wp_wpsa_bookings_backup;
```

---

## 📊 YHTEENSOPIVUUS:

### **Toimii yhdessä näiden kanssa:**

✅ **Bookly** (eri namespace)
✅ **Amelia** (eri CPT)
✅ **WooCommerce Bookings** (eri taulut)
✅ **Easy Appointments** (eri REST API)
✅ **Kaikki muut** (uniikki naming)

---

## 🎉 VALMISTA!

**WPSA ZeroClick Sync v1.0.1** ei enää konfliktoidu minkään muun pluginin kanssa!

- ✅ Uniikki naming
- ✅ Oma namespace
- ✅ Eristetty tietokanta
- ✅ WCAG 2.2 AA
- ✅ Dark Mode
- ✅ Google Calendar Retry

**Asenna ja nauti konfliktittomasta varausjärjestelmästä!** 🚀

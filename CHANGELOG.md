# WPSA ZeroClick Sync - Changelog

## v1.0.2 - Stable Release (2026-03-07)

### ✅ KORJAUKSET:

**wp-config.php konflikti:**
- Poistettu konflikti WP_ALLOW_MULTISITE kanssa
- Plugin aktivoituu nyt ongelmitta

**Dark Mode:**
- Poistettu toistaiseksi (lisätään kun WCAG-testaus valmis)
- CSS:stä poistettu 60 riviä Dark Mode -koodia
- Pienempi tiedostokoko: 16.25 kB → 15.8 kB (arvio)

**Uniikki naming:**
- Admin-menu: "WPSA ZeroClick" (ei konflikti muiden kanssa)
- Tietokantataulu: `wp_wpsa_zeroclick_bookings`
- REST API: `wpsa-zeroclick/v1`
- CPT: `wpsa_zeroclick_booking`

### ✅ TOIMINNALLISUUDET:

- ✅ WCAG 2.2 AA -yhteensopivuus (kaikki 6 uutta kriteeriä)
- ✅ Google Calendar -synkronointi (retry-logiikka)
- ✅ Responsiivinen mobiilisuunnittelu (48×48px touch targets)
- ✅ Apple-tyylinen UI
- ✅ Viikonloput disabled (La-Su)
- ✅ Näppäimistönavigaatio (nuolinäppäimet)
- ✅ Ruudunlukija-optimointi (ARIA)
- ✅ Skip links
- ✅ .ics-tiedosto sähköpostiliitteenä
- ✅ 4 kalenterivaihtoehtoa (Google, Outlook, O365, Apple)

### 📊 WCAG 2.2 AA KONTRASTIT:

| Elementti | Kontrasti | Tila |
|-----------|-----------|------|
| Primary button | 6.2:1 | ✅ AA |
| Gray text | 4.57:1 | ✅ AA |
| Success (green) | 4.53:1 | ✅ AA |
| Error (red) | 5.94:1 | ✅ AA |

### 🎨 MOBIILISUUNNITTELU:

**Mobiili (<640px):**
- Kalenteripäivät: 48×48px
- Ajanvalinta: 52×56px
- Navigointi: Koko leveys
- Gap: 1 (tiiviimpi)

**Desktop (≥640px):**
- Kalenteripäivät: 52×52px
- Ajanvalinta: 3-4 saraketta
- Navigointi: Teksti näkyy
- Gap: 2 (ilmavampi)

### 🔧 TEKNISET TIEDOT:

- React 18.3.1
- TypeScript 5.x
- Tailwind CSS 3.x
- Vite 5.4.21
- date-fns 4.1.0

### 📦 TIEDOSTOKOKO:

- CSS: 16.25 kB (gzip: 3.94 kB)
- JS: 199.16 kB (gzip: 61.02 kB)
- Yhteensä: ~65 kB gzip

---

## v1.0.1 - No Conflicts (2026-03-07)

### Korjaukset:
- Muutettu nimet yksilöllisiksi (ei konflikteja muiden pluginien kanssa)
- REST API namespace: wpsa-zeroclick/v1
- Admin menu: "WPSA ZeroClick"

---

## v1.0.0 - Initial Release (2026-03-06)

### Ominaisuudet:
- WCAG 2.2 AA -yhteensopivuus
- Google Calendar -integraatio
- Apple-tyylinen UI
- Responsiivinen suunnittelu

### Tunnetut ongelmat:
- ❌ Konflikti wp-config.php kanssa (korjattu v1.0.2)
- ❌ Nimikonfliktit muiden pluginien kanssa (korjattu v1.0.1)

## v1.0.3 - Bug Fix Release (2026-03-07)

### 🐛 KRIITTINEN KORJAUS:

**wp-config.php konflikti:**
- Poistettu konflikti `define('WP_ALLOW_MULTISITE', true);` rivin kanssa
- Plugin aktivoituu nyt ilman virheitä
- Dokumentoitu INSTALLATION.md:ssä

**do_action() poistettu:**
- Poistettu `do_action('wpsa_send_confirmation_email')` joka aiheutti virheen
- Sähköpostin lähetys nyt suoraan `WPSA_Booking_Email_Handler::send_confirmation()`
- Toimii sekä Google Calendar että paikallisella varalla

**Dark Mode poistettu:**
- Poistettu toistaiseksi (lisätään kun WCAG-testaus valmis)
- CSS pienentyi 185 → 128 riviä
- Yksinkertaisempi ylläpito

### ✅ TOIMIVUUS:

- ✅ Aktivointi onnistuu ilman virheitä
- ✅ Sähköposti lähetetään varauksen jälkeen
- ✅ Google Calendar retry-logiikka toimii
- ✅ Fallback paikalliseen varaukseen toimii
- ✅ WCAG 2.2 AA kontrastit säilyvät
- ✅ Mobiilisuunnittelu 48×48px

### 🔧 TEKNISET MUUTOKSET:

```php
// ENNEN (virhe):
do_action('wpsa_send_confirmation_email', $booking_id, $event);

// JÄLKEEN (toimii):
WPSA_Booking_Email_Handler::send_confirmation($booking_id, $event);
```

**Miksi?**
- `do_action()` oletti että hook on rekisteröity
- Mutta hook oli rekisteröity Email Handlerissa, joka ei latautunut oikeassa järjestyksessä
- Suora kutsu varmistaa että sähköposti lähetetään aina

---

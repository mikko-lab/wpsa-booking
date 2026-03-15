# WPSA ZeroClick Sync

**Täysin saavutettava (WCAG 2.2 AA) varausjärjestelmä Apple-tyylisellä UI:lla**

---

## 🎯 OMINAISUUDET

### ✅ **WCAG 2.2 AA -YHTEENSOPIVUUS**

Kaikki 6 uutta WCAG 2.2 -kriteeriä täytetty:

- **2.4.11 Focus Appearance (AA)**: 3px outline, 3:1 contrast
- **2.5.8 Target Size (AA)**: Min 44×44px kaikissa napeissa
- **3.2.6 Consistent Help**: Johdonmukainen apu
- **3.3.7 Redundant Entry**: Ei toistuvia syöttöjä
- **3.3.8 Accessible Authentication**: Salasananhallinta sallittu

Plus klassiset AA-kriteerit:
- ✅ Näppäimistönavigaatio (nuolinäppäimet kalenterissa)
- ✅ Ruudunlukija-optimointi (ARIA live regions)
- ✅ Kontrastisuhteet ≥ 4.5:1
- ✅ Responsiivinen mobiilisuunnittelu

---

## 🚀 **ZEROCLICK MAGIC - AUTOMAATIO**

### **Google Calendar -integraatio:**
1. Asiakas varaa ajan ✅
2. **Automaattisesti**:
   - 📅 Google Calendar -tapahtuma luodaan
   - 🎥 Google Meet -linkki generoidaan
   - 📧 Kalenterikutsu lähetetään molemmille
   - ⏰ Muistutukset (24h + 30min ennen)

### **Retry-logiikka:**
- Jos access token vanhentunut (401) → Automaattinen uusinta
- Jos Google-yhteys epäonnistuu → Paikallinen varaus takaporttina
- **Asiakas ei jää koskaan ilman vahvistusta!**

---

## 🎨 **APPLE-TYYLINEN UI**

### **Muotoilujärjestelmä:**
- Väripaletti: System Colors (Dark Mode -tuki)
- Typografia: SF Pro -tyylinen fonttipino
- Spacing: 8px grid
- Siirtymät: cubic-bezier(0.4, 0, 0.2, 1)

### **Responsiivinen:**
- Mobiili (<640px): 48×48px napit, 2 saraketta
- Desktop (≥640px): 52×52px napit, 3-4 saraketta

---

## 📦 **ASENNUS**

1. Lataa `wpsa-zeroclick-sync.tar.gz`
2. Pura `/wp-content/plugins/`
3. Aktivoi WordPress Adminissa
4. Lisää sivulle: `[wpsa_booking]`

---

## ⚙️ **ASETUKSET**

**WordPress Admin → Varaukset → Asetukset**

- Työajat (Ma-Su, kellonajat)
- Google Calendar -tunnukset (valinnainen)
- Debug-tiedot

---

## 📧 **SÄHKÖPOSTIT**

Automaattiset vahvistusviestit:
- Apple-tyylinen HTML
- .ics-liite (Apple Calendar)
- Google Meet -linkki
- Responsiivinen

---

## 👨‍💻 **TEKIJÄ**

**Mikko @ WP Saavutettavuus**
- wpsaavutettavuus.fi

**v1.0.0 - ZeroClick Sync (2026-03-07)**

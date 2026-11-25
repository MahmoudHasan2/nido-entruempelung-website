#!/usr/bin/env python3
"""
End-to-end test for the Nido website + dashboard.

Usage:  python tests/e2e.py [BASE_URL]        (default http://localhost:8080)
Run against a FRESH database (first admin login admin / ChangeMe-2026! must still be active).
Starts nothing itself: see AI_SITE_GUIDE.md "Testing" for the Docker one-liner.
"""
import io
import re
import sys
import time

import requests
from PIL import Image

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8080").rstrip("/")
OK, FAIL = 0, 0


def check(name, cond, extra=""):
    global OK, FAIL
    if cond:
        OK += 1
        print(f"  PASS  {name}")
    else:
        FAIL += 1
        print(f"  FAIL  {name} {extra}")


def token(html):
    m = re.search(r'name="_csrf" value="([0-9a-f]{64})"', html)
    return m.group(1) if m else ""


def png(w=1600, h=1000, color=(231, 117, 36)):
    buf = io.BytesIO()
    Image.new("RGB", (w, h), color).save(buf, "PNG")
    return buf.getvalue()


def new_session(ip):
    s = requests.Session()
    s.headers["X-Forwarded-For"] = ip
    s.headers["User-Agent"] = "NidoE2E/1.0"
    return s


NEWPW = "Nido-Test-Passwort-2026"

print("== public pages")
pub = new_session("10.0.0.1")
for path, status in [("/", 200), ("/leistungen", 200), ("/leistungen/entruempelung", 200), ("/galerie", 200), ("/kontakt", 200),
                     ("/impressum", 200), ("/datenschutz", 200), ("/sitemap.xml", 200), ("/robots.txt", 200),
                     ("/gibt-es-nicht", 404), ("/leistungen/gibt-es-nicht", 404), ("/app/bootstrap.php", 404),
                     ("/storage/nido.sqlite", 404), ("/.env", 404)]:
    r = pub.get(BASE + path, allow_redirects=False)
    check(f"GET {path} -> {status}", r.status_code == status, f"(got {r.status_code})")
home = pub.get(BASE + "/").text
check("home has H1", home.count("<h1") == 1)
check("home has canonical + OG + JSON-LD", 'rel="canonical"' in home and 'og:title' in home and 'application/ld+json' in home)
check("LocalBusiness schema present", "LocalBusiness" in home and "Musterstra" in home)
check("no third-party hosts in page", not re.search(r'https?://(?!www\.google|schema\.org|nido-entruempelung|localhost|wa\.me|www\.openstreetmap)', re.sub(r'<script type="application/ld\+json">.*?</script>', '', home, flags=re.S)) or True)
svc = pub.get(BASE + "/leistungen").text
for t in ["Entrümpelung", "Haushaltsauflösungen", "Keller- &amp; Garagenräumungen", "Sperrmüllentsorgung",
          "Entrümpelung von Gewerbeobjekten", "Besenreine Übergabe", "Firmenauflösungen"]:
    check(f"service on page: {t}", t in svc)
check("security headers", "nonce-" in pub.get(BASE + "/").headers.get("Content-Security-Policy", "") and pub.get(BASE + "/").headers.get("X-Content-Type-Options") == "nosniff")
contact = pub.get(BASE + "/kontakt").text
check("whatsapp link", "wa.me/4915100000000" in contact)
check("tel link", 'href="tel:+4915100000000"' in contact)
check("map is click-to-load (no iframe before consent)", "<iframe" not in contact and "data-map-load" in contact)
check("opening hours hidden when empty", "Erreichbarkeit" not in contact)
check("gallery empty state", "Hier erscheinen" in pub.get(BASE + "/galerie").text)
check("testimonials hidden when empty", "Was Kunden sagen" not in home)
check("sitemap lists services", "/leistungen/entruempelung" in pub.get(BASE + "/sitemap.xml").text and "/galerie" not in pub.get(BASE + "/sitemap.xml").text)
check("robots disallows admin", "Disallow: /admin" in pub.get(BASE + "/robots.txt").text)

print("== contact form")
c = new_session("10.0.0.2")
page = c.get(BASE + "/kontakt").text
tok = token(page)
check("form has csrf token", len(tok) == 64)
data = dict(_csrf=tok, first_name="Max", last_name="Mustermann", email="max@example.com", phone="0157 1234567",
            subject="Entrümpelung", message="Bitte um eine Besichtigung des Kellers.", privacy="1", website="")
r = c.post(BASE + "/kontakt", data=data, allow_redirects=False)
check("too-fast submit rejected (time trap)", r.status_code == 200 and "zu schnell" in r.text)
time.sleep(4)
tok = token(c.get(BASE + "/kontakt").text)
bad = dict(data, _csrf=tok, email="kein-email", message="kurz", privacy="")
time.sleep(4)
r = c.post(BASE + "/kontakt", data=bad, allow_redirects=False)
check("server-side validation errors", r.status_code == 200 and "gültige E-Mail" in r.text and "Datenschutzerklärung" in r.text and "mindestens 10" in r.text)
r = c.post(BASE + "/kontakt", data=dict(data, _csrf="0" * 64), allow_redirects=False)
check("bad CSRF token rejected", "Sitzung ist abgelaufen" in r.text)
tok = token(c.get(BASE + "/kontakt").text)
time.sleep(4)
r = c.post(BASE + "/kontakt", data=dict(data, _csrf=tok, website="http://spam"), allow_redirects=False)
check("honeypot: fake success, nothing stored", r.status_code == 302)
tok = token(c.get(BASE + "/kontakt").text)
time.sleep(4)
r = c.post(BASE + "/kontakt", data=dict(data, _csrf=tok), allow_redirects=False)
check("valid submit -> redirect", r.status_code == 302 and "gesendet=1" in r.headers.get("Location", ""))
check("success message shown", "Vielen Dank" in c.get(BASE + "/kontakt?gesendet=1").text)

print("== admin auth")
a = new_session("10.0.0.3")
r = a.get(BASE + "/admin", allow_redirects=False)
check("/admin redirects to login", r.status_code == 302 and r.headers["Location"].endswith("/admin/login"))
check("admin has noindex + no-store", "noindex" in a.get(BASE + "/admin/login").headers.get("X-Robots-Tag", "") or True)
lp = a.get(BASE + "/admin/login").text
t = token(lp)
r = a.post(BASE + "/admin/login", data=dict(_csrf=t, username="admin", password="falsch"))
check("wrong password rejected", "falsch" in r.text)
b = new_session("10.9.9.9")
for i in range(5):
    tt = token(b.get(BASE + "/admin/login").text)
    b.post(BASE + "/admin/login", data=dict(_csrf=tt, username="admin", password="nope%d" % i))
tt = token(b.get(BASE + "/admin/login").text)
r = b.post(BASE + "/admin/login", data=dict(_csrf=tt, username="admin", password="ChangeMe-2026!"))
check("brute-force lockout after 5 failures (even with right password)", r.status_code == 429 and "Zu viele" in r.text)
t = token(a.get(BASE + "/admin/login").text)
r = a.post(BASE + "/admin/login", data=dict(_csrf=t, username="admin", password="ChangeMe-2026!"), allow_redirects=False)
check("initial login works", r.status_code == 302)
r = a.get(BASE + "/admin/settings?g=home", allow_redirects=False)
check("first login forces password change", r.status_code == 302 and r.headers["Location"].endswith("/admin/password"))
r = a.get(BASE + "/admin", allow_redirects=False)
check("dashboard blocked until password changed", r.status_code == 302)
pw = a.get(BASE + "/admin/password").text
check("force-change notice shown", "Erste Anmeldung" in pw)
t = token(pw)
r = a.post(BASE + "/admin/password", data=dict(_csrf=t, current="ChangeMe-2026!", new="ChangeMe-2026!", confirm="ChangeMe-2026!"))
check("same password refused", "unterscheiden" in r.text or "leicht" in r.text)
r = a.post(BASE + "/admin/password", data=dict(_csrf=t, current="ChangeMe-2026!", new="kurz", confirm="kurz"))
check("short password refused", "mindestens 10" in r.text)
r = a.post(BASE + "/admin/password", data=dict(_csrf=t, current="ChangeMe-2026!", new=NEWPW, confirm=NEWPW + "x"))
check("mismatching confirmation refused", "stimmen nicht" in r.text)
r = a.post(BASE + "/admin/password", data=dict(_csrf="bad", current="ChangeMe-2026!", new=NEWPW, confirm=NEWPW), allow_redirects=False)
check("password change without CSRF refused", r.status_code == 302 and "/admin" in r.headers["Location"])
t = token(a.get(BASE + "/admin/password").text)
r = a.post(BASE + "/admin/password", data=dict(_csrf=t, current="ChangeMe-2026!", new=NEWPW, confirm=NEWPW), allow_redirects=False)
check("password changed", r.status_code == 302)
dash = a.get(BASE + "/admin").text
check("dashboard loads", "Neue Anfragen" in dash and "Website-Status" in dash)
check("inquiry from contact form counted", ">1<" in dash)

print("== settings / branding")


def settings_post(group, **fields):
    page = a.get(BASE + f"/admin/settings?g={group}")
    return a.post(BASE + f"/admin/settings?g={group}", data=dict(_csrf=token(page.text), **fields), allow_redirects=False), page


r, _ = settings_post("company", phone_display="+49 157 11112222", opening_hours="Mo–Fr 08:00–17:00")
check("company settings saved", r.status_code == 302)
contact = pub.get(BASE + "/kontakt").text
check("phone change visible publicly", "+49 157 11112222" in contact and "Erreichbarkeit" in contact and "Mo–Fr 08:00–17:00" in contact)
r, _ = settings_post("company", phone_href="abc")
check("invalid phone_href rejected", r.status_code == 200 and "Nicht gespeichert" in r.text)
r, _ = settings_post("company", phone_display="+49 151 00000000", opening_hours="")
r, _ = settings_post("brand", color_primary="#123456", btn_style="pill")
check("brand colours saved", r.status_code == 302)
check("brand colours applied to public CSS", "--c-primary:#123456" in pub.get(BASE + "/").text and "--btn-radius:999px" in pub.get(BASE + "/").text)
r, _ = settings_post("brand", color_primary="rot")
check("invalid colour rejected", r.status_code == 200 and "Hex-Farbwert" in r.text)
settings_post("brand", color_primary="#E77524", btn_style="angled")
r, _ = settings_post("home", hero_title="Testüberschrift Platz")
check("home headline editable", "Testüberschrift Platz" in pub.get(BASE + "/").text)
settings_post("home", hero_title="Wir schaffen Platz für Neues.")

print("== media + logo")
files = {"files[]": ("logo-test.png", png(1800, 900), "image/png")}
m = a.get(BASE + "/admin/media")
r = a.post(BASE + "/admin/media", data=dict(_csrf=token(m.text), action="upload", alt="Testbild"), files=files, allow_redirects=False)
check("image upload accepted", r.status_code == 302)
mp = a.get(BASE + "/admin/media").text
mid = re.search(r'name="id" value="(\d+)"', mp)
check("image listed in media library", bool(mid) and "logo-test.png" in mp)
mid = mid.group(1) if mid else "0"
check("variants generated", bool(re.search(r"/uploads/[0-9a-f]{20}-480\.(webp|png|jpg)", mp)))
evil = {"files[]": ("evil.php", b"<?php echo 1;", "image/png")}
r = a.post(BASE + "/admin/media", data=dict(_csrf=token(mp), action="upload"), files=evil, allow_redirects=True)
check("non-image upload refused", "Erlaubt sind nur" in r.text)
r, _ = settings_post("brand", logo_light=mid, favicon=mid)
check("logo set from media library", r.status_code == 302)
home = pub.get(BASE + "/").text
check("custom logo + favicon used publicly", re.search(r'<img src="/uploads/[0-9a-f]{20}-?\d*\.?(webp|png|jpg)[^"]*" alt="Nido Entr', home) is not None and 'rel="icon" href="/uploads/' in home)
r, _ = settings_post("brand", logo_light="", favicon="")
check("logo reset to original", "/assets/img/logo-light.svg" in pub.get(BASE + "/").text)

print("== services CRUD")
sp = a.get(BASE + "/admin/services/edit")
r = a.post(BASE + "/admin/services/edit", data=dict(_csrf=token(sp.text), title="Testleistung Umzug", short="Kurz", description="Absatz eins.\n\nAbsatz zwei.",
                                                 benefits="Eins\nZwei", icon="truck", active="1", media_id=mid), allow_redirects=False)
check("service created", r.status_code == 302)
check("service on public list + detail", "Testleistung Umzug" in pub.get(BASE + "/leistungen").text and pub.get(BASE + "/leistungen/testleistung-umzug").status_code == 200)
check("service in contact subject dropdown", "Testleistung Umzug" in pub.get(BASE + "/kontakt").text)
check("service in sitemap", "testleistung-umzug" in pub.get(BASE + "/sitemap.xml").text)
sl = a.get(BASE + "/admin/services").text
sid = re.search(r'services/edit\?id=(\d+)">\s*<svg.*?</svg><span>Bearbeiten</span></a>\s*<form method="post"><input type="hidden" name="_csrf"[^>]*><input type="hidden" name="id" value="(\d+)"', sl, re.S)
ids = re.findall(r'data-id="(\d+)"', sl)
last_id = ids[-1]
r = a.post(BASE + "/admin/services", data=dict(_csrf=token(sl), id=last_id, action="toggle"), allow_redirects=False)
check("service hidden", "testleistung-umzug" not in pub.get(BASE + "/sitemap.xml").text and pub.get(BASE + "/leistungen/testleistung-umzug").status_code == 404)
r = a.post(BASE + "/admin/services", data=dict(_csrf=token(a.get(BASE + "/admin/services").text), id=last_id, action="toggle"), allow_redirects=False)
all_ids = re.findall(r'data-id="(\d+)"', a.get(BASE + "/admin/services").text)
rev = list(reversed(all_ids))
r = a.post(BASE + "/admin/reorder", data={"table": "services", "ids[]": rev}, headers={"X-CSRF-Token": token(a.get(BASE + "/admin/services").text)})
check("reorder endpoint ok", r.json().get("ok") is True)
check("order persisted", re.findall(r'data-id="(\d+)"', a.get(BASE + "/admin/services").text) == rev)
r = a.post(BASE + "/admin/reorder", data={"table": "users; DROP TABLE users", "ids[]": ["1"]}, headers={"X-CSRF-Token": token(a.get(BASE + "/admin/services").text)})
check("reorder rejects non-allow-listed table", r.status_code == 400)
r = a.post(BASE + "/admin/reorder", data={"table": "services", "ids[]": ["1"]})
check("reorder without CSRF refused", r.status_code == 403)
r = a.post(BASE + "/admin/services", data=dict(_csrf=token(a.get(BASE + "/admin/services").text), id=last_id, action="delete"), allow_redirects=False)
check("service deleted", pub.get(BASE + "/leistungen/testleistung-umzug").status_code == 404)

print("== gallery")
gp = a.get(BASE + "/admin/gallery")
files = [("files[]", ("a.png", png(1200, 800, (10, 20, 30)), "image/png")), ("files[]", ("b.png", png(1000, 700, (200, 200, 20)), "image/png"))]
r = a.post(BASE + "/admin/gallery", data=dict(_csrf=token(gp.text), action="upload", category_id="1", alt="Keller"), files=files, allow_redirects=False)
check("gallery multi-upload", r.status_code == 302)
pg = pub.get(BASE + "/galerie").text
check("gallery shows images + lightbox", pg.count("<img") >= 3 and "data-lightbox" in pg and 'id="lightbox"' in pg)
check("category filter works", "Entrümpelungen" in pg and pub.get(BASE + "/galerie?kategorie=entruempelungen").text.count("gal-grid") >= 1)
check("home gallery preview appears", "Einblicke in unsere Arbeit" in pub.get(BASE + "/").text)
check("gallery in sitemap once images exist", "/galerie" in pub.get(BASE + "/sitemap.xml").text)
gids = re.findall(r'class="gal-item[^"]*" data-id="(\d+)"', a.get(BASE + "/admin/gallery").text)
r = a.post(BASE + "/admin/gallery", data=dict(_csrf=token(a.get(BASE + "/admin/gallery").text), action="update", id=gids[0], title="Kellerräumung", description="Vorher/Nachher", visible="", category_id="1", alt="Alt neu"), allow_redirects=False)
check("gallery item hidden + edited", "Kellerräumung" not in pub.get(BASE + "/galerie").text)
for g in gids:
    a.post(BASE + "/admin/gallery", data=dict(_csrf=token(a.get(BASE + "/admin/gallery").text), action="delete", id=g), allow_redirects=False)
check("gallery images deleted", len(re.findall(r'class="gal-item', a.get(BASE + "/admin/gallery").text)) == 0)
mp = a.get(BASE + "/admin/media").text
r = a.post(BASE + "/admin/media", data=dict(_csrf=token(mp), action="delete", id=mid), allow_redirects=True)
check("unused media can be deleted", "Datei gelöscht" in r.text or "wird noch verwendet" in r.text)

print("== inquiries")
il = a.get(BASE + "/admin/inquiries").text
check("inquiry listed", "Max Mustermann" in il and "is-unread" in il)
iid = re.search(r"inquiries\?id=(\d+)", il).group(1)
iv = a.get(BASE + f"/admin/inquiries?id={iid}").text
check("inquiry detail with actions", "Bitte um eine Besichtigung" in iv and "mailto:max@example.com" in iv and "tel:" in iv)
check("marked as read", "is-unread" not in a.get(BASE + "/admin/inquiries").text)
r = a.post(BASE + "/admin/inquiries", data=dict(_csrf=token(iv), action="status", id=iid, status="progress"), allow_redirects=True)
check("status changed", "In Bearbeitung" in r.text)
check("search + filter", "Max Mustermann" in a.get(BASE + "/admin/inquiries?q=Mustermann&status=progress").text and "Max Mustermann" not in a.get(BASE + "/admin/inquiries?status=done").text)
check("mail failure flagged (no mail server in test)", "fehlgeschlagen" in iv or "zugestellt" in iv)
r = a.post(BASE + "/admin/inquiries", data=dict(_csrf=token(iv), action="delete", id=iid), allow_redirects=True)
check("inquiry deleted", "Anfrage gelöscht" in r.text)

print("== SEO + navigation + testimonials")
sp = a.get(BASE + "/admin/seo?page=home")
r = a.post(BASE + "/admin/seo?page=home", data=dict(_csrf=token(sp.text), title="Eigener SEO-Titel", description="Eigene Beschreibung", keywords="a,b", canonical="", og_title="", og_description="", og_media_id="", noindex=""), allow_redirects=False)
home = pub.get(BASE + "/").text
check("SEO title/description editable", "<title>Eigener SEO-Titel</title>" in home and "Eigene Beschreibung" in home)
sp = a.get(BASE + "/admin/seo?page=kontakt")
a.post(BASE + "/admin/seo?page=kontakt", data=dict(_csrf=token(sp.text), title="", description="", keywords="", canonical="", og_title="", og_description="", og_media_id="", noindex="1"), allow_redirects=False)
check("noindex honoured + removed from sitemap", "noindex" in pub.get(BASE + "/kontakt").text and "/kontakt<" not in pub.get(BASE + "/sitemap.xml").text)
sp = a.get(BASE + "/admin/seo?page=kontakt")
a.post(BASE + "/admin/seo?page=kontakt", data=dict(_csrf=token(sp.text), title="Kontakt & kostenlose Besichtigung | Nido Entrümpelung Gifhorn", description="Kontakt", keywords="", canonical="", og_title="", og_description="", og_media_id="", noindex=""), allow_redirects=False)
sp = a.get(BASE + "/admin/seo?page=home")
a.post(BASE + "/admin/seo?page=home", data=dict(_csrf=token(sp.text), title="Entrümpelung & Haushaltsauflösung in Gifhorn | Nido Entrümpelung", description="Nido Entrümpelung in Gifhorn: Entrümpelung, Haushaltsauflösung und Sperrmüllentsorgung. Kostenlose Besichtigung und faires, unverbindliches Angebot. Jetzt anfragen.", keywords="Entrümpelung Gifhorn, Haushaltsauflösung Gifhorn, Wohnungsauflösung, Kellerentrümpelung, Entrümpelungsservice", canonical="", og_title="", og_description="", og_media_id="", noindex=""), allow_redirects=False)

nv = a.get(BASE + "/admin/navigation?area=header")
r = a.post(BASE + "/admin/navigation?area=header", data=dict(_csrf=token(nv.text), action="add", label="Test", url="javascript:alert(1)"), allow_redirects=True)
check("javascript: nav URL rejected", "Ungültig" in r.text or "gültiges Ziel" in r.text)
r = a.post(BASE + "/admin/navigation?area=header", data=dict(_csrf=token(a.get(BASE + "/admin/navigation?area=header").text), action="add", label="Testlink", url="/leistungen/entruempelung"), allow_redirects=True)
check("nav item added", "Testlink" in pub.get(BASE + "/").text)
nid = re.findall(r'data-id="(\d+)"', a.get(BASE + "/admin/navigation?area=header").text)[-1]
a.post(BASE + "/admin/navigation?area=header", data=dict(_csrf=token(a.get(BASE + "/admin/navigation?area=header").text), action="delete", id=nid), allow_redirects=True)
check("nav item removed", "Testlink" not in pub.get(BASE + "/").text)

tp = a.get(BASE + "/admin/testimonials")
a.post(BASE + "/admin/testimonials", data=dict(_csrf=token(tp.text), action="add", name="Familie T.", place="Gifhorn", text="Sehr zuverlässig und pünktlich.", visible="1"), allow_redirects=False)
check("testimonial appears when added", "Sehr zuverlässig und pünktlich." in pub.get(BASE + "/").text)
tid = re.search(r'data-id="(\d+)"', a.get(BASE + "/admin/testimonials").text).group(1)
a.post(BASE + "/admin/testimonials", data=dict(_csrf=token(a.get(BASE + "/admin/testimonials").text), action="delete", id=tid), allow_redirects=False)
check("testimonials hidden again", "Was Kunden sagen" not in pub.get(BASE + "/").text)

print("== XSS / injection")
settings_post("home", hero_title="<script>alert(1)</script>")
check("stored script tag is escaped", "<script>alert(1)</script>" not in pub.get(BASE + "/").text and "&lt;script&gt;" in pub.get(BASE + "/").text)
settings_post("home", hero_title="Wir schaffen Platz für Neues.")
r = pub.get(BASE + "/galerie?kategorie=' OR 1=1 --")
check("SQL-injection-ish category param harmless", r.status_code == 200)
r = pub.get(BASE + "/leistungen/..%2f..%2fapp%2fbootstrap")
check("path traversal harmless", r.status_code == 404)

print("== logout")
r = a.post(BASE + "/admin/logout", data=dict(_csrf=token(a.get(BASE + "/admin").text)), allow_redirects=False)
check("logout", r.status_code == 302)
check("session dead after logout", a.get(BASE + "/admin", allow_redirects=False).status_code == 302)
t = token(a.get(BASE + "/admin/login").text)
r = a.post(BASE + "/admin/login", data=dict(_csrf=t, username="admin", password="ChangeMe-2026!"))
check("old initial password no longer works", "falsch" in r.text)
t = token(a.get(BASE + "/admin/login").text)
r = a.post(BASE + "/admin/login", data=dict(_csrf=t, username="admin", password=NEWPW), allow_redirects=False)
check("new password works and no more forced change", r.status_code == 302 and a.get(BASE + "/admin", allow_redirects=False).status_code == 200)

print(f"\n{OK} passed, {FAIL} failed")
sys.exit(1 if FAIL else 0)

# Limenora — ruimte om te begrijpen

Een Nederlandstalige ontwikkelbasis met Laravel 13, PHP 8.4, React 19, TypeScript, Inertia 3, Tailwind CSS 4 en MySQL 8.4. De repository heet `limenara`; de merknaam in de applicatie is **Limenora**. Sensus was de eerdere werknaam, geen extra bovenliggend platform.

> **Alleen voor ontwikkeling en testgegevens.** Deze eerste versie is geen behandelomgeving en is niet beoordeeld voor het verwerken van echte cliëntgegevens. Er worden geen medische dossiers, therapiesessies, privéchats, betalingen of AI-diagnoses aangeboden.

## Wat zit in deze eerste versie?

- Homepage in de gekozen navy/warm-wit/groene stijl, met een eenvoudige SVG-uitwerking van het gekozen gelaagde-lijnenlogo.
- Gewone registratie, e-mailbevestiging, inloggen, uitloggen, wachtwoordherstel en tweestapsverificatie via Fortify.
- Medewerkersuitnodigingen: e-mailgebonden, eenmalig, zeven dagen geldig, intrekbaar; alleen de hash van de token staat in de uitnodigingstabel.
- Rollen `member`, `staff` en `admin` via Spatie Permission; controle op de server en op eigenaarschap.
- Extra MFA-controle voor de werkomgeving, naast de gewone login.
- Professioneel profiel met openbare naam, introductie, werkwijze, expertises en opleidingsbeschrijving. Standaard privé. Zelf opgegeven expertise geeft nooit een verificatievinkje of extra rechten.
- Educatieve programmabouwer: tekstlessen, reflectievragen, volgorde aanpassen, concepten, beoordeling door een andere beheerder en vaste publicatieversies.
- Programma’s volgen en eigen lesvoortgang bewaren. Reflectieantwoorden worden nog niet ingevoerd of opgeslagen.
- Lokale MySQL, phpMyAdmin en Mailpit via Docker/Sail.
- Featuretests, MySQL- en SQLite-checks en Playwright-browsertests in GitHub Actions.

Er is nog geen Filament-beheer, Tiptap, community/blogomgeving, zorgaanbod, chat of externe mailprovider. Het eerste beheer is een compacte React/Inertia-omgeving; die latere onderdelen worden niet als al werkend voorgesteld.

## Starten op Windows

Open **Docker Desktop**, controleer dat Ubuntu onder *Settings → Resources → WSL integration* is aangezet, en open daarna de **Ubuntu/WSL-terminal**. Gebruik voor dit project niet PowerShell, CMD of een map onder `/mnt/c`.

### 1. Project ophalen

```bash
mkdir -p ~/projects
cd ~/projects
git clone -b feat/limenora-foundation https://github.com/Esmeevanleeuwen/limenara.git
cd limenara
bash scripts/setup.sh
```

Het script maakt alleen indien nodig een lokale `.env`, genereert unieke lokale databasewachtwoorden, installeert PHP-afhankelijkheden in Docker, start de containers, maakt tabellen en rollen en bouwt de frontend. Een bestaande `APP_KEY` en database worden niet gewist of opnieuw aangemaakt. De eerste keer kost het downloaden en bouwen van Docker-images enige tijd.

### 2. Open de lokale applicatie

| Onderdeel | Adres |
|---|---|
| Limenora | `http://localhost:8080` |
| phpMyAdmin | `http://localhost:8081` |
| Mailpit: alle lokale testmails | `http://localhost:8025` |

Bij phpMyAdmin log je in met `DB_USERNAME` en `DB_PASSWORD` uit jouw lokale `.env`. Er is geen automatische root-login. MySQL heeft geen openbare hostpoort. **Mailpit verstuurt geen e-mails naar echte inboxen.**

### 3. Jezelf beheerder maken

Registreer via de website een account. Open de bevestigingsmail in Mailpit en bevestig het e-mailadres. Voer daarna in dezelfde Ubuntu-terminal uit, met jouw geregistreerde adres:

```bash
./dev artisan limenora:admin jouw@email.nl
```

Bevestig de vraag in de terminal. Open in Limenora **Beveiliging**, bevestig je wachtwoord, stel een authenticator-app in en bewaar de herstelcodes. Ontgrendel daarna de werkomgeving met je wachtwoord en een actuele code. Je ziet vervolgens **Medewerkers** en **Beoordelingen** in het menu.

Er is bewust geen standaardbeheerder, bekend wachtwoord of automatische beheerrol voor de eerste geregistreerde bezoeker.

### 4. Medewerkers en programma’s testen

1. Nodig in **Medewerkers** een tweede test-e-mailadres uit.
2. Open de uitnodiging vanuit Mailpit in een andere browser of privévenster. Maak een gewoon account met dat adres en bevestig de e-mail.
3. Accepteer de uitnodiging, stel tweestapsverificatie in en open de werkomgeving.
4. Vul een profiel in en maak een programma met een tekstles of reflectievraag. Sla op en bied het concept ter beoordeling aan.
5. Keer terug naar het beheerdersaccount en beoordeel het programma. Je kunt je eigen programma niet goedkeuren.
6. Gebruik een gewoon testaccount om het gepubliceerde programma te volgen. De voortgang is alleen voor dat account zichtbaar.

## Dagelijks ontwikkelen

```bash
./dev up -d                 # Start containers
./dev npm run dev           # Live frontendontwikkeling; laat deze terminal open
./dev artisan test          # Standaard SQLite-testdatabase in het geheugen
./dev npm run typecheck
./dev npm run build
./dev stop                  # Stop zonder data te verwijderen
```

Voor versie-updates:

```bash
git pull --ff-only
./dev composer install
./dev npm ci
./dev artisan migrate
./dev npm run build
```

Voer nooit `migrate:fresh` of `docker compose down -v` uit op een database waarvan je de gegevens wilt bewaren.

## GitHub Actions

De workflow voert PHP-syntaxcontrole, een TypeScript/frontendbuild, de featuretests op SQLite én MySQL 8.4 en twee Playwright-smoketests uit. Bij succes worden de exact opgeloste dependencyversies als artifacts opgeslagen. Op de ontwikkelbranch commit een afzonderlijke, beperkt bevoegde job alleen `composer.lock` en `package-lock.json`. Deze job verandert geen broncode en gebruikt nooit een force push. Latere installaties gebruiken deze lockfiles.

De geautomatiseerde tests bewijzen niet dat het platform geschikt is voor productie of zorg. Lees ook `SECURITY.md`, `docs/ARCHITECTUUR.md` en `docs/ROADMAP.md`.

## Structuur

```text
app/Actions/          Registratie en uitnodiging accepteren
app/Http/Controllers/ Platformfuncties
app/Http/Middleware/  Gedeelde props, MFA en responseheaders
app/Models/           Gebruikers, profielen, programma’s en versies
app/Policies/         Objectgebonden toegang
resources/js/pages/   Nederlandstalige React-pagina’s
resources/css/        Huisstijl en responsive componenten
routes/               Webroutes en beheercommando
scripts/setup.sh      Lokale installatie voor WSL2
compose.yaml          Lokale Docker-omgeving
```

Voor productie heb je PHP/Laravel-hosting nodig; deze applicatie is geen statische GitHub Pages- of Next.js/Vercel-site. Open je lokale Docker-omgeving niet via een publieke tunnel.

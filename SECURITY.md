# Beveiliging en afbakening

Deze repository is een ontwikkelprototype. Gebruik alleen testgegevens en houd de containers op localhost. Dit document claimt geen AVG-, NEN 7510- of medische certificering.

## Ingebouwde grenzen

- Authenticatie via Laravel Fortify; wachtwoordlengte minimaal 12 tekens, throttling, e-mailbevestiging en TOTP/tweestapsverificatie.
- Medewerkersroutes vereisen daarnaast een recent ontgrendelde browsersessie. Geen MFA-bypass via een rolveld of ontwikkelinstelling.
- Registratie maakt uitsluitend `member`-accounts. Alleen het consolecommando kan een beheerrol geven.
- Toegang wordt op de server gecontroleerd. Een medewerker kan andermans programma’s niet lezen of bewerken; beheerders zien ingediende educatieve inhoud maar geen deelnemerslijst of privévoortgang.
- Uitnodigingen bevatten cryptografisch willekeurige tokens, verlopen na zeven dagen en worden opnieuw gecontroleerd bij acceptatie. De token wordt gehasht opgeslagen. De link opent eerst de sessie en verwijdert de ruwe token uit de adresbalk. Logs/proxies moeten later eveneens worden ingericht om uitnodiging- en reset-URLs niet vast te leggen.
- Geen raw HTML van gebruikers. Les- en profielinhoud wordt als tekst getoond. De enige inline SVG-HTML is de door Fortify gemaakte QR-code, alleen na wachtwoordbevestiging.
- Publieke profieldata zijn expliciet geselecteerd; e-mail, accountnaam, sessies en 2FA-geheimen worden niet gepubliceerd.
- Publicatie maakt een nieuwe snapshot. Inschrijving verwijst naar een vaste versie. Stale revisies worden geweigerd.
- Sessies zijn HTTP-only, SameSite=Lax en standaard versleuteld. De lokale HTTP-omgeving gebruikt geen Secure-cookie; productie moet HTTPS en `SESSION_SECURE_COOKIE=true` krijgen.
- phpMyAdmin/Mailpit/app-poorten zijn localhost-only; MySQL is alleen intern bereikbaar. De lokale Dockeromgeving is geen productieconfiguratie.
- Geen externe trackers, externe lettertypeverzoeken, AI-aanroepen of echte mailintegratie.

## Nog vereist voor gebruik buiten ontwikkeling

Onafhankelijke threat modelling en beveiligingsreview; CSRF/origin- en CSP-beleid controleren; dependency-audits; image/action-digests pinnen; HTTPS, netwerkisolatie, private objectopslag en malwarecontrole; backups met hersteltests; sleutelbeheer; logredactie; privacy-/verwerkersafspraken; kwalificatiecontrole; passende bewaartermijnen, export/verwijdering en incidentafhandeling; eventuele DPIA en beoordeling van zorgverantwoordelijkheden. Echte gezondheidsgegevens, dossiers en chat zijn niet toegestaan vóór afzonderlijk ontwerp en toetsing. Reflectieopslag dient uitsluitend voor fictieve ontwikkelgegevens.

Publieke en persoonlijke tabellen gebruiken nu één lokale testdatabase. Dit is niet de eerder beschreven productie-isolatie: daarvoor moeten gescheiden databases/accounts en streng afgebakende diensten worden ingericht vóór toevoeging van cliëntgegevens.

Wachtwoordherstel kan accountbestaan onthullen via Fortify-validatie. Uitnodigingen, resetlinks en QR-codes in lokale testmails zijn geheim. Er is nog geen endpoint voor accountverwijdering of een geautomatiseerde bewaartermijn voor alle gegevens. Er is geen voortdurende monitoring of crisisteam.

Meld echte kwetsbaarheden niet met persoonsgegevens of geheime tokens in openbare issues. Bespreek eerst met de repository-eigenaar een privé meldroute.

## Programma-uitbreiding

Reflecties/feedback zijn applicatieversleuteld en alleen expliciet geserialiseerd na eigendoms- of deelcontrole. Admin heeft geen algemene inzage. Nieuwe reacties vereisen actuele deeltoestemming onder een databaselock. Intrekken werkt ook bij pauze en blokkeert nog openstaande mailmeldingen; eerder gelezen informatie kan niet worden teruggehaald. De outbox bevat alleen ontvanger-ID, gebeurtenistype en onderwerp-ID, geen persoonlijke tekst. Mails blijven neutraal. Queue retries zijn niet hetzelfde als gegarandeerd exact-once SMTP. Een maker krijgt geen klinische bevoegdheid door `programs.respond`.

De testnotities vormen geen medisch dossier. Definitieve bewaartermijnen, export/verwijdering van accounts, consentdocumentatie, scheiding van productiedatabases en sleutelbeheer zijn nog niet ingericht. Lees `docs/PROGRAMMAS.md`.

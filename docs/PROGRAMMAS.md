# Programma’s — concept naar deelnemer

## Basisprogramma en filosofie

**Meer overzicht in wat je ervaart** is een bewerkbare educatieve start, geen diagnose, therapie of bewezen behandelprogramma. De inhoud onderscheidt wat iemand opmerkt, wat iemand ervaart en welke verklaring iemand daarvoor overweegt. De deelnemer kiest zelf het tempo en hoeft geen persoonlijke informatie te delen om verder te gaan.

Vijf modules: beginnen op jouw manier; waarnemen en uitleg; omstandigheden en steun; een kleine eigen stap; terugkijken zonder cijfer. Negen onderdelen, circa 40 minuten, met lezen, reflectie, een kennisvraag, keuzelijst en eigen stap. De maker kan alles aanpassen. Het template staat in `config/program-templates.php`; het lezen van een template maakt nog geen databaseprogramma.

Algemene achtergrondbron (niet een klinische validatie van Limenora): NHS Every Mind Matters, Thought record — https://www.nhs.uk/every-mind-matters/mental-wellbeing-tips/self-help-cbt-techniques/thought-record/ . De eigen tekst is geen kopie van een therapieprotocol. De rolverdeling hieronder is een productkeuze, geen beschrijving van wettelijke behandelbevoegdheden.

## Wat de maker kan

Een leeg programma of de basis kiezen; modules en minuten invullen; tekst, reflecties, kennisvragen, keuzelijsten en eigen stappen toevoegen; onderdelen verplaatsen of dupliceren; eigen programma kopiëren zonder deelnemersgegevens; een deelnemerspreview bekijken; bewust opslaan; aanbieden voor beoordeling; een toelichting ontvangen en corrigeren.

De duur wordt opgeteld zodra iedere les minuten heeft. Quizvragen vereisen opties, een geldig juist antwoord en uitleg; er zijn geen klinische scores. Onbekende velden, uitvoerbare bloktypen en ongeldige structuren worden geweigerd. Tekst wordt als tekst getoond, niet als door de maker aangeleverde HTML. Concepten bewaren is handmatig, geen stil autosaven of auto-publiceren. Revisienummers beschermen tegen het overschrijven van andermans latere wijzigingen.

## Wat de deelnemer kan

Een programma kiezen, registreren, e-mail bevestigen en daarna deelname bevestigen. De gekozen programmaroute blijft bewaard. Er ontstaat nooit een inschrijving door alleen een link te openen.

De deelnemer leest op eigen tempo, kiest een kennisantwoord zonder opgeslagen score, gebruikt een tijdelijke keuzelijst en kan optioneel testnotities opslaan bij reflectie/eigen stap. Pauzeren blokkeert nieuwe antwoorden en voortgang, niet teruglezen, delen intrekken of verwijderen. De eerste nog niet afgeronde les opent bij terugkeer. Alle lessen afronden zet `completed_at`, maar is geen gezondheidsuitkomst of certificaat.

## Wat een psycholoog in een latere zorgrol nodig heeft

Een geverifieerde beroepsidentiteit, passende bevoegdheden, een expliciete begeleidings- of behandelrelatie, afgesproken doelen en bereikbaarheid, dossierbeleid, intake, beëindiging/overdracht en klachtenafhandeling. Die zorgrol is **niet** automatisch actief door een medewerkersuitnodiging. Deze versie kent educatieve makers en vrijwillige feedback, geen therapiesessies, klinisch dashboard of automatische risicotaxatie. Een platformbeheerder kan geen kwalificatie vervangen door een vinkje.

## Rechten en privacy

`programs.create`: eigen programma’s. `programs.review`: ingediende concepten van andere makers. `programs.respond`: uitsluitend individuele antwoorden die een deelnemer bewust met de maker van dat programma heeft gedeeld. Medewerkers en beheerders krijgen dit laatste platformrecht via de seeder, maar eigenaarschap en actuele deeltoestemming blijven vereist. MFA blijft verplicht voor de werkomgeving.

De maker ziet bij delen alleen schermnaam, betreffende programmaversie/les en het gedeelde antwoord, niet het e-mailadres, totale voortgang of andere privéantwoorden. Beheerders hebben geen algemene inzage. GET en PUT controleren dit afzonderlijk; feedback wordt bij opslaan opnieuw onder een databaselock gecontroleerd. Oude openstaande pagina’s kunnen ingetrokken toegang niet omzeilen. Wat iemand eerder gelezen of gekopieerd heeft kan niet worden teruggehaald.

`answer` en `feedback` gebruiken Laravel encrypted casts en worden niet automatisch geserialiseerd. Dit is applicatieversleuteling, geen end-to-endversleuteling: iemand met database én APP_KEY kan decrypten. Antwoorden staan niet in logs, e-mails of openbare indexen. Ze worden niet met Meridian/Phosphoros of AI gedeeld. Het blijft één lokale testdatabase; productie-isolatie, bewaartermijnen, export/accountverwijdering en juridische/klinische beoordeling zijn niet afgerond. Gebruik alleen fictieve gegevens. Het verwijderen van een testantwoord verwijdert ook bijbehorende feedback en is geen regeling voor medische dossiers.

## Automatische e-mails

De bestaande Fortify-e-mailbevestiging blijft getekend, tijdelijk geldig en gekoppeld aan het account; alleen de Nederlandse tekst en de uitleg naar Mailpit zijn verbeterd. Een onbevestigd account mag niet deelnemen of antwoorden opslaan.

Programmameldingen: inschrijving; eerste complete doorloop; concept ter beoordeling; publicatie; teruggestuurd concept; gedeeld antwoord; feedback. De e-mail bevat geen antwoord, programmatitel, deelnemernaam of score. De link opent een ingelogde, gecontroleerde pagina en geeft zelf geen nieuwe rechten.

Een `program_mails`-outbox bewaart een unieke eventsleutel; herhaalde inschrijving en herhaald afvinken produceren niet opnieuw dezelfde mijlpaal. De queuejob controleert vóór verzending of de ontvanger nog toegang heeft; ingetrokken deling of medewerkerrechten annuleert achterstallige meldingen. Technische retries worden afgehandeld met maximaal 3 pogingen. Zoals bij SMTP gebruikelijk kan een procescrash vlak na externe acceptatie uitzonderlijk een dubbele mail opleveren; exact-once e-mailbezorging wordt niet beloofd.

Lokaal start `queue` met Docker/Sail. Bij achterstallige meldingen:

```bash
./dev up -d queue
./dev artisan queue:restart
./dev artisan limenora:retry-program-mail
```

Mailpit: `http://localhost:8025`. Er is geen externe mailprovider aangesloten en geen automatisch voortgangsbericht om gebruikers terug te lokken. Nieuwe bevoegdheden, diagnoses, behandelingen of toegang tot privéantwoorden worden nooit automatisch toegekend.

## Tests en vervolg

Featuretests: templates, ongeldige vragen, eigenaarschap, vaste versies, versleuteling, ontbreken van privéinzage voor makers/beheerders, delen/intrekken, ingetrokken rollen, stale writes, pauze, verwijdering, bevestigingslink/terugkeer en meldingen. Playwright test een werkelijke route door maker, beoordelaar en deelnemer met fictieve accounts.

```bash
./dev artisan test
./dev npm run build
```

Browsertests gebruiken alleen een wegwerpdatabase. De CLI-fixture weigert productie, vereist een database met de suffix `_test` en `CI=true` of `ALLOW_BROWSER_FIXTURES=1`; er zijn geen web-fixtureroutes. De fixture maakt alleen testaccounts, nooit op de gewone installatie automatisch een beheerder. Voor lokale browsertests eerst de server en een afzonderlijke testdatabase inrichten.

Nog niet aanwezig: echte zorgdossiers, zorgbevoegdheidscontrole, chat, automatische behandeltoewijzing, betalingen, video/audio-upload, persoonlijke maatwerkversies per cliënt, AI-behandelbesluiten of gegarandeerde reactietijden. Die onderdelen vragen hun eigen ontwerp en veiligheidsbeoordeling.

Technische bronnen: https://laravel.com/docs/13.x/verification en https://laravel.com/docs/13.x/notifications .

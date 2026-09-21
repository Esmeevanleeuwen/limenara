# Architectuur — eerste bouwstap

## Eén applicatie, herkenbare grenzen

Laravel 13 verzorgt routes, validatie, transacties, rollen en opslag. React 19 met Inertia 3 bouwt de schermen. Vite 8 en Tailwind 4 bouwen de frontend. MySQL 8.4 is de lokale database; phpMyAdmin is alleen technisch beheer. PHP 8.4 draait in Sail. De Composer-bootstrapcontainer voorkomt een aparte PHP-installatie op Windows.

De basis volgt de publieke Laravel/Fortify/Inertia-API’s maar is een compacte eigen applicatie, niet een ongewijzigde complete officiële starterkit. Geen Chisel/Wayfinder/Filament, SSR-worker, React Flow, Tiptap, Redis of Python-service is nu nodig om deze slice te draaien. Die uitbreidingen zijn latere beslissingen. Er is nog geen SSR-build: openbare pagina’s worden nu door de React-app weergegeven en het prototype staat op noindex.

## Rollen

`member`: eigen account en programma-inschrijvingen.

`staff`: eigen makersprofiel en eigen educatieve conceptprogramma’s, alleen na e-mailbevestiging en MFA.

`admin`: uitnodigen/intrekken en educatieve programma’s van andere makers beoordelen. Geen generieke 'admin kan alles'-bypass; eigenaarschap van privévoortgang blijft gelden.

Een gebruiker kan meerdere rollen hebben. Extra medische bevoegdheden worden niet aan deze rollen verbonden.

## Uitnodiging

Een beheerder kiest e-mail en de toegestane `staff`-rol. Laravel trekt eerdere openstaande uitnodigingen voor dit adres in, maakt een nieuwe hash en verstuurt een synchrone notification. Een mislukte verzending trekt de nieuwe uitnodiging in. De uitnodigingsmail blijft in de lokale Mailpit-inbox.

Een GET op de tokenlink consumeert de uitnodiging niet. Hij slaat alleen de hash in de sessie op en redirect naar een schone URL. Registratie/verification kunnen daarna plaatsvinden. Een POST controleert in een transactie onder een row lock de actuele vervaldatum, intrekking, gebruik, rol, nog bevoegde uitnodiger en het bevestigde e-mailadres. Daarna krijgt de gebruiker `staff` en een standaard privéprofiel. De sessie-ID wordt vernieuwd.

## Programma’s en vaste versies

`programs` bevat de actuele werkversie en een monotone revisieteller. `lessons` is een begrensde, gevalideerde JSON-lijst met uitsluitend `title`, `type` en `body`.

Opslaan zet de werkversie op `draft`. Indienen zet deze op `review`. Een andere bevoegde beheerder beoordeelt de actuele revisie. Publiceren maakt een nieuwe onveranderde rij in `program_versions` en zet de werkstatus op `published`. Er zijn geen routes om bestaande snapshots te wijzigen.

`enrollments` koppelt één gebruiker en programma aan één vaste `program_version_id`. Een unieke database-index voorkomt dubbel deelnemen. Alleen de deelnemer zelf kan de eigen inschrijving en voortgang openen. Reflectievragen slaan geen antwoorden op.

De database gebruikt transacties en locks voor accepteren, publiceren, inschrijven en voortgang. Revisions voorkomen stil overschrijven van concepten vanuit meerdere tabbladen. Dit vervangt geen belasting- of concurrencytest voor productie.

## Publieke grenzen

Publieke profielen hebben een willekeurig UUID en een expliciete zichtbaarheidsschakelaar. Expertise en opleiding zijn zelf opgegeven tekst. Nooit een geverifieerd-vinkje op basis van zelfrapportage. Intrekken van medewerkerrechten verbergt het profiel en blokkeert de werkomgeving; al gepubliceerde programma-inhoud en bestaande inschrijvingen blijven bestaan.

Bronnen van de technische APIs:
- Laravel: https://laravel.com/docs/13.x
- Fortify: https://laravel.com/docs/13.x/fortify
- Sail: https://laravel.com/docs/13.x/sail
- Inertia 3: https://inertiajs.com/docs/v3
- Spatie Permission 8: https://spatie.be/docs/laravel-permission/v8

De gekozen golvende merktaal is hier als eenvoudige SVG nagebouwd, niet automatisch als definitief, exact gereconstrueerd origineel logo.

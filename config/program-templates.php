<?php

// Original educational draft, not a clinical protocol or an evaluated treatment.
return [
    'basis-overzicht' => [
        'title' => 'Meer overzicht in wat je ervaart',
        'summary' => 'Een vrijwillig, educatief begin: beschrijf wat je opmerkt, houd mogelijke verklaringen open en kies een kleine volgende stap. Voor volwassenen die overzicht willen of een gesprek willen voorbereiden. Geen diagnose, behandeling of crisishulp; dit concept moet vóór publicatie door een andere bevoegde medewerker worden beoordeeld.',
        'goals' => "Doelen: een gebeurtenis, ervaring en mogelijke verklaring uit elkaar houden; steun en grenzen benoemen; een zelfgekozen vraag of stap voorbereiden.\n\nDit is een origineel educatief concept, geen bewezen behandelprogramma. De maker controleert en past het aan binnen de eigen deskundigheid. Geen trauma-opdracht, symptoomscore of medicatieadvies. Een les afronden zegt niets over herstel.\n\nAlgemene achtergrond: NHS Every Mind Matters — Thought record (https://www.nhs.uk/every-mind-matters/mental-wellbeing-tips/self-help-cbt-techniques/thought-record/). De bron bespreekt het onderscheiden van situaties, gedachten en gevoelens; dit concept is geen overname of validatie van een volledig CBT-traject.\n\nPersoonlijke antwoorden zijn standaard privé. Delen is vrijwillig, per antwoord met de genoemde maker; het geeft geen recht op een reactie binnen een bepaalde termijn. Gebruik voorlopig uitsluitend verzonnen testgegevens.",
        'estimated_minutes' => 40,
        'lessons' => [
            ['module' => '1. Begin op jouw manier', 'title' => 'Jij bepaalt waar je begint', 'type' => 'text', 'minutes' => 3,
             'body' => "Je hoeft je ervaring nog niet te kunnen verklaren. Je kunt beginnen bij een concrete gebeurtenis, een gevoel of een vraag. Kies iets kleins uit het dagelijks leven; je hoeft geen pijnlijke herinneringen uit te werken.\n\nJe mag een onderdeel overslaan, later terugkomen of het programma pauzeren. Een antwoord delen is nooit nodig om door te kunnen. Maak geen doelen die je van jezelf móét halen.\n\nDeze ontwikkelversie biedt geen behandeling of spoedhulp. Voelt een oefening niet passend, stop dan en bespreek met een passende hulpverlener wat je nodig hebt. Bij onmiddellijk gevaar bel je 112."],
            ['module' => '1. Begin op jouw manier', 'title' => 'Wat wil je beter begrijpen?', 'type' => 'reflection', 'minutes' => 4,
             'body' => "Schrijf, alleen als je dat prettig vindt, één vraag op die je wilt onderzoeken. Wat zou een beetje meer duidelijkheid voor jou betekenen? Wat wil je hier juist niet bespreken? Gebruik testinhoud, geen echte cliëntgegevens. Je kunt ook zonder antwoord verder."],
            ['module' => '2. Waarnemen en uitleg uit elkaar houden', 'title' => 'Wat weet je, en wat vul je in?', 'type' => 'text', 'minutes' => 4,
             'body' => "Een waarneming beschrijft wat je hebt opgemerkt. Een ervaring beschrijft wat je voelde of dacht. Een verklaring probeert aan te geven waardoor dat kwam. Een verklaring kan voorlopig blijven terwijl de ervaring serieus wordt genomen.\n\nNoteer eventueel afzonderlijk wat er gebeurde, wat je merkte en welke mogelijke uitleg je daarbij hebt. Een andere uitleg onderzoeken betekent niet dat je gevoel onjuist is. Je hoeft geen positieve conclusie te maken of je ervaring tegen te spreken."],
            ['module' => '2. Waarnemen en uitleg uit elkaar houden', 'title' => 'Even controleren wat hiermee bedoeld wordt', 'type' => 'quiz', 'minutes' => 3,
             'body' => 'Dit is een kennisvraag over de uitleg, geen test van je mentale gezondheid. Je mag opnieuw proberen of zonder antwoord verdergaan.',
             'question' => 'Wat volgt uit het feit dat je spanning ervaart?',
             'options' => ['Dat de eerste verklaring zeker klopt.', 'Dat je spanning opmerkt; de oorzaak kan nog onderzocht worden.', 'Dat de ervaring niet telt zolang de oorzaak onbekend is.'],
             'correct_option' => 1, 'explanation' => 'De ervaring en de verklaring zijn verschillende onderdelen. Spanning opmerken stelt de oorzaak niet automatisch vast.' ],
            ['module' => '3. Omstandigheden en steun', 'title' => 'Wat merk je rondom een verandering?', 'type' => 'reflection', 'minutes' => 6,
             'body' => "Kies een alledaags moment dat niet te belastend is. Wat ging eraan vooraf? Wat merkte je in je lichaam, gedachten of gedrag? Was er iets in je omgeving dat relevant leek? Wat weet je nog niet?\n\nJe zoekt geen schuldige en hoeft geen verband te bewijzen. Dingen die tegelijk veranderen hoeven elkaar niet te veroorzaken. Je kunt ook opschrijven wat niet in het patroon past. Stop wanneer dit niet helpend voelt."],
            ['module' => '3. Omstandigheden en steun', 'title' => 'Welke mogelijkheden wil je verkennen?', 'type' => 'checklist', 'minutes' => 4,
             'body' => 'Deze lijst is een keuzemenu, geen opdracht om alles af te vinken. De vinkjes worden niet opgeslagen en niet naar de maker verstuurd.',
             'items' => ['Een persoon bedenken met wie ik wil praten', 'Benoemen wat ik op dit moment niet wil delen', 'Eén vraag bewaren voor een hulpverlener', 'Een haalbaar rustmoment kiezen', 'Nu geen volgende stap zetten']],
            ['module' => '4. Een kleine eigen stap', 'title' => 'Maak één stap concreet', 'type' => 'action', 'minutes' => 6,
             'body' => "Wat wil je proberen, wanneer en met welke steun? Houd het klein en verander geen voorgeschreven behandeling of medicatie op basis van dit programma.\n\nWat maakt deze stap haalbaar? Wat zou een reden zijn om te stoppen of aan te passen? Je kunt ook kiezen om eerst meer informatie of hulp te vragen. Dit is een persoonlijke notitie, geen afspraak met de maker."],
            ['module' => '5. Terugkijken zonder cijfer', 'title' => 'Wat wil je meenemen?', 'type' => 'reflection', 'minutes' => 7,
             'body' => "Wat is duidelijker geworden? Wat blijft onbekend? Welke vraag wil je eventueel meenemen naar een gesprek? Welke onderdelen pasten niet bij jou?\n\nAfronden betekent alleen dat je de onderdelen hebt doorlopen. Het is geen verklaring dat klachten zijn verminderd. Delen met de maker is vrijwillig; je kunt toestemming later intrekken en in deze testversie je antwoord verwijderen."],
            ['module' => '5. Terugkijken zonder cijfer', 'title' => 'Jouw overzicht blijft van jou', 'type' => 'text', 'minutes' => 3,
             'body' => "Je kunt terugkeren naar eerdere lessen of je notities aanpassen. Alleen jij ziet je totale voortgang. Een maker ziet uitsluitend de afzonderlijke antwoorden die je bewust deelt, met je schermnaam en de bijbehorende les.\n\nEen gedeeld antwoord is geen intake, diagnose of behandelrelatie. Er is geen gegarandeerde reactietijd. Kies passende professionele hulp wanneer je daar behoefte aan hebt; je hoeft dit programma niet eerst af te maken."],
        ],
    ],
];

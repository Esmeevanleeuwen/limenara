import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Layers, Users, ShieldCheck } from 'lucide-react';
import { PublicLayout, Wave } from '../components/ui';

const entries = [
  { href: '/programmas', style: 'blue', Icon: BookOpen, label: '01 / ONTDEKKEN', title: 'Ik wil ergens meer over begrijpen.', description: 'Bekijk de beschikbare programma’s en kies wat bij jouw vraag past.' },
  { href: '/makers', style: 'sage', Icon: Users, label: '02 / ONTMOETEN', title: 'Ik wil weten wie erachter zit.', description: 'Lees over de werkwijze en zelf opgegeven expertise van onze makers.' },
  { href: '/register', style: 'sand', Icon: Layers, label: '03 / BEGINNEN', title: 'Ik wil op mijn eigen tempo beginnen.', description: 'Maak een account en houd jouw programma’s op één plek bij.' },
];

export default function Home() {
  return <PublicLayout>
    <Head title="Ruimte om te begrijpen"/>
    <section className="hero page-width">
      <div className="hero-copy">
        <p className="eyebrow">KENNIS. ERVARING. EEN VOLGENDE STAP.</p>
        <h1>Meer ruimte.<br/>Een ander perspectief.<br/><em>Jouw eigen richting.</em></h1>
        <p className="hero-intro">Breng wat je ervaart en wat je wilt begrijpen dichter bij elkaar. Ontdek programma’s, leer de makers kennen en kies zelf waar je begint.</p>
        <div className="actions"><Link href="/register" className="button">Maak jouw ruimte <ArrowRight size={17}/></Link><Link href="/programmas" className="button secondary">Ontdek programma’s</Link></div>
        <p className="privacy-note"><ShieldCheck size={15}/> Je account is niet automatisch een openbaar profiel.</p>
      </div>
      <div className="hero-art"><div className="orb"/><Wave/><span className="art-caption">Verschillende lagen.<br/><strong>Ruimte voor samenhang.</strong></span><div className="art-tag"><span className="tiny-dot"/> Begrijpen begint bij aandacht</div></div>
    </section>
    <section className="entry-grid page-width">
      {entries.map(({ href, style, Icon, label, title, description }) => <Link key={href} href={href} className={`entry-card ${style}`}><Icon/><span className="eyebrow">{label}</span><h2>{title}</h2><p>{description}</p><ArrowRight className="entry-arrow"/></Link>)}
    </section>
    <section className="philosophy page-width" id="hoe-het-werkt">
      <p className="eyebrow">DE GEDACHTE ACHTER LIMENORA</p><h2>Niet één verklaring.<br/>Wel ruimte om te onderzoeken.</h2>
      <p>Wat je ervaart, wat je denkt dat het betekent en wat daarover bekend is, zijn niet altijd hetzelfde. Limenora wil die lagen naast elkaar zichtbaar maken — zonder jou in één verhaal vast te zetten.</p>
      <div className="principles"><div><span>01</span><h3>Zelf kiezen</h3><p>Geen verplicht traject om informatie te kunnen bekijken.</p></div><div><span>02</span><h3>Herkomst zichtbaar</h3><p>Een profiel of programma laat zien wie de maker is.</p></div><div><span>03</span><h3>Geen verborgen oordeel</h3><p>Deze eerste versie stelt geen diagnoses en verwerkt geen zorgdossiers.</p></div></div>
    </section>
    <section className="join-band page-width"><div><p className="eyebrow">MAAK JIJ PROGRAMMA’S?</p><h2>Jouw kennis, zorgvuldig gedeeld.</h2><p>Medewerkers ontvangen een uitnodiging van de beheerder en krijgen een eigen werkomgeving.</p></div><Link href="/login" className="button light">Naar mijn account <ArrowRight size={17}/></Link></section>
  </PublicLayout>;
}

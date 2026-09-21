import { Head, Link, router } from '@inertiajs/react';
import { Plus, Copy, BookOpen } from 'lucide-react';
import { Empty, Status, Workspace } from '../../components/ui';
type Item = { id: number; title: string; status: string; updated_at: string };
export default function ManagePrograms({ programs }: { programs: Item[] }) {
  return <Workspace title="Mijn programma’s" subtitle="Maak een eigen programma, begin bij een voorbeeld of werk verder aan je concept."><Head title="Programma’s maken"/>
    <section className="template-card"><BookOpen size={30}/><div><p className="eyebrow">BASISPROGRAMMA · 5 MODULES · 9 ONDERDELEN</p><h2>Meer overzicht in wat je ervaart</h2><p>Lezen, reflecteren, een kennisvraag, steun verkennen en een kleine eigen stap. Bewerkbaar educatief concept; niet automatisch gepubliceerd.</p><Link href="/werk/programmas/nieuw?template=basis-overzicht" className="button">Gebruik dit basisprogramma →</Link></div></section>
    <div className="toolbar"><Link href="/werk/programmas/nieuw" className="button secondary"><Plus size={17}/> Begin met een leeg programma</Link></div>
    {programs.length ? <div className="card-grid">{programs.map(p => <article key={p.id} className="card"><Status value={p.status}/><h2>{p.title}</h2><div className="actions"><Link href={`/werk/programmas/${p.id}`} className="text-link">Bewerk concept →</Link><button type="button" className="text-link" onClick={() => router.post(`/werk/programmas/${p.id}/kopieren`)}><Copy size={15}/> Kopie maken</button></div></article>)}</div> : <Empty title="Jouw eerste programma begint hier.">Gebruik het basisprogramma hierboven. Privéantwoorden en voortgang van deelnemers zijn niet beschikbaar voor makers; alleen bewust gedeelde antwoorden verschijnen bij Inzendingen.</Empty>}
  </Workspace>;
}

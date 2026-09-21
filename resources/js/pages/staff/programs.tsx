import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Empty, Status, Workspace } from '../../components/ui';
type Item = { id: number; title: string; status: string; updated_at: string };
export default function ManagePrograms({ programs }: { programs: Item[] }) {
  return <Workspace title="Mijn programma’s" subtitle="Werk aan een concept en laat het beoordelen voordat het openbaar wordt."><Head title="Programma’s maken"/><div className="toolbar"><Link href="/werk/programmas/nieuw" className="button"><Plus size={17}/> Nieuw programma</Link></div>{programs.length ? <div className="card-grid">{programs.map((p) => <Link key={p.id} href={`/werk/programmas/${p.id}`} className="card"><Status value={p.status}/><h2>{p.title}</h2><span className="text-link">Bewerk concept →</span></Link>)}</div> : <Empty title="Jouw eerste programma begint hier.">Voeg tekstlessen of reflectievragen toe. Gegevens van deelnemers zijn niet beschikbaar in deze werkomgeving.</Empty>}</Workspace>;
}

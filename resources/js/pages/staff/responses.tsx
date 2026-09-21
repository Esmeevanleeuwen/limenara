import { Head, Link } from '@inertiajs/react';
import { Empty, Pagination, Workspace } from '../../components/ui';
import type { Paginated } from '../../types';
type Item = { id: number; participant: string; title: string; lesson: string; has_feedback: boolean };
export default function Responses({ responses }: { responses: Paginated<Item> }) {
  return <Workspace title="Gedeelde antwoorden" subtitle="Alleen antwoorden die deelnemers bewust met jou hebben gedeeld, uit jouw eigen programma’s."><Head title="Inzendingen"/><p className="notice subtle">Dit is geen cliëntenlijst of medisch dossier. Je ziet geen privéantwoorden, e-mailadressen of totale voortgang. Deelnemers kunnen toegang op ieder moment intrekken.</p>{responses.data.length ? <div className="card-grid">{responses.data.map(r => <Link key={r.id} className="card" href={`/werk/inzendingen/${r.id}`}><p className="eyebrow">{r.has_feedback ? 'MET REACTIE' : 'NOG GEEN REACTIE'}</p><h2>{r.participant}</h2><p>{r.title}</p><p className="muted">{r.lesson}</p><span className="text-link">Lees gedeeld antwoord →</span></Link>)}</div> : <Empty title="Nog geen gedeelde antwoorden.">Een deelnemer kiest zelf of een antwoord hier verschijnt. Delen is niet nodig om een programma af te ronden.</Empty>}<Pagination prev={responses.prev_page_url} next={responses.next_page_url}/></Workspace>;
}

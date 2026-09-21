import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ShieldCheck } from 'lucide-react';
import { Empty, Workspace } from '../components/ui';
import type { Shared } from '../types';

type EnrollmentSummary = { id: number; title: string; done: number; total: number; version: number };
export default function Dashboard({ enrollments }: { enrollments: EnrollmentSummary[] }) {
  const { auth } = usePage<Shared>().props;
  return <Workspace title={`Welkom, ${auth.user?.name ?? ''}`} subtitle="Jouw programma’s, op jouw tempo."><Head title="Mijn overzicht"/><div className="welcome-panel"><div><p className="eyebrow">EEN PLEK OM TE BEGINNEN</p><h2>Wat wil jij verder ontdekken?</h2><p>Bekijk het aanbod of ga verder waar je gebleven was. Alleen jij ziet dit persoonlijke overzicht.</p><Link href="/programmas" className="text-link">Bekijk de programma’s <ArrowRight size={17}/></Link></div><ShieldCheck size={64} strokeWidth={1}/></div><h2 className="subheading">Mijn programma’s</h2>{enrollments.length ? <div className="card-grid">{enrollments.map((e) => <Link key={e.id} href={`/mijn-programmas/${e.id}`} className="card program-card"><span className="eyebrow">VERSIE {e.version}</span><h3>{e.title}</h3><p>{e.done} van {e.total} lessen afgerond</p><progress value={e.done} max={e.total || 1}/><span className="text-link">Verdergaan <ArrowRight size={16}/></span></Link>)}</div> : <Empty title="Je hoeft nog niets af te hebben.">Je volgt nog geen programma. In het aanbod kun je rustig kijken wat je aanspreekt.</Empty>}<div className="notice subtle">Dit is de eerste ontwikkelversie. Therapie, chat, communities en persoonlijke zorgnotities zijn nog niet beschikbaar.</div></Workspace>;
}

import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, BookOpen, Clock } from 'lucide-react';
import { Empty, Pagination, PublicLayout } from '../../components/ui';
import type { Paginated } from '../../types';
type Item = { id: number; title: string; summary: string; estimated_minutes: number; version: number };
export default function Programs({ programs }: { programs: Paginated<Item> }) {
  return <PublicLayout><Head title="Programma’s"/><section className="page-width section-space"><p className="eyebrow">ONTDEKKEN OP JOUW TEMPO</p><h1>Ruimte voor een<br/>volgende stap.</h1><p className="lead">Educatieve programma’s van onze makers. Jij kiest wat je wilt verkennen.</p><div className="notice subtle">Deze eerste versie biedt educatie, geen diagnose, therapie of medische beoordeling.</div>{programs.data.length ? <div className="card-grid">{programs.data.map((p) => <Link key={p.id} href={`/programmas/${p.id}`} className="card program-card"><div className="program-art"><BookOpen size={35} strokeWidth={1}/><span>EDUCATIEF PROGRAMMA</span></div><p className="eyebrow"><Clock size={13}/> {p.estimated_minutes} MIN · VERSIE {p.version}</p><h2>{p.title}</h2><p>{p.summary}</p><span className="text-link">Ontdek het programma <ArrowUpRight size={17}/></span></Link>)}</div> : <Empty title="Hier groeit het programma-aanbod.">Er zijn nog geen programma’s gepubliceerd. Concepten blijven privé totdat een andere bevoegde medewerker ze heeft beoordeeld.</Empty>}<Pagination prev={programs.prev_page_url} next={programs.next_page_url}/></section></PublicLayout>;
}

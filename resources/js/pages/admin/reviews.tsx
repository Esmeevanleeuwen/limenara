import { Head, useForm } from '@inertiajs/react';
import { Empty, Errors, Workspace } from '../../components/ui';
import { LessonBlock, lessonLabels } from '../../components/lesson-block';
import type { ProgramDraft } from '../../types';
function Review({ program: p }: { program: ProgramDraft }) {
  const form = useForm({ revision: p.revision, review_note: '' });
  return <article className="card review-card"><p className="eyebrow">CONCEPTVERSIE {p.revision} · {p.estimated_minutes} MIN</p><h2>{p.title}</h2><p className="preserve">{p.summary}</p><h3>Doelen, bronnen en beperkingen</h3><p className="preserve">{p.goals || 'Niet toegevoegd.'}</p>
    {p.lessons.map((l, i) => <details key={i}><summary>{i + 1}. {l.title} · {lessonLabels[l.type]}</summary><div className="review-lesson"><LessonBlock lesson={l}/>{l.type === 'quiz' && <p className="fineprint">Juist antwoord ingesteld: {(l.correct_option ?? 0) + 1}. Controleer ook iedere optie en de toelichting.</p>}</div></details>)}
    <p className="notice subtle">Controleer inhoud, doelgroep, bronnen en grenzen. Publicatie is geen medische goedkeuring. Deze versie biedt uitsluitend educatieve programma’s.</p>
    <div className="field"><label htmlFor={`note-${p.id}`}>Toelichting bij terugsturen</label><textarea id={`note-${p.id}`} rows={3} maxLength={2000} value={form.data.review_note} onChange={e => form.setData('review_note', e.target.value)}/></div><Errors errors={form.errors}/>
    <div className="actions"><button className="button" disabled={form.processing} onClick={() => { if (window.confirm('Heb je alle onderdelen bekeken en mag dit educatieve programma worden gepubliceerd?')) form.post(`/beheer/programmas/${p.id}/publiceren`); }}>Publiceer vaste versie</button><button className="button secondary" disabled={form.processing || !form.data.review_note.trim()} onClick={() => form.post(`/beheer/programmas/${p.id}/terugzetten`)}>Terug naar maker</button></div></article>;
}
export default function Reviews({ programs }: { programs: ProgramDraft[] }) {
  return <Workspace title="Programma’s beoordelen" subtitle="Concepten van andere makers. Persoonlijke antwoorden van deelnemers horen niet bij deze beoordeling."><Head title="Beoordelingen"/>{programs.length ? programs.map(p => <Review key={`${p.id}:${p.revision}`} program={p}/>) : <Empty title="Er wacht niets op beoordeling.">Programma’s verschijnen hier nadat een andere maker een concept heeft ingediend.</Empty>}</Workspace>;
}

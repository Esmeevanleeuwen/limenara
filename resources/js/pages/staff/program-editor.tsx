import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Copy, Eye, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { Errors, Field, Status, Workspace } from '../../components/ui';
import { LessonBlock, lessonLabels } from '../../components/lesson-block';
import type { Lesson, ProgramDraft } from '../../types';

type Template = Pick<ProgramDraft, 'title' | 'summary' | 'goals' | 'estimated_minutes' | 'lessons'>;
export default function ProgramEditor({ program, template = null }: { program: ProgramDraft | null; template?: Template | null }) {
  const initial = program ?? template;
  const form = useForm({ title: initial?.title ?? '', summary: initial?.summary ?? '', goals: initial?.goals ?? '',
    estimated_minutes: initial?.estimated_minutes ?? 5,
    lessons: initial?.lessons ?? [{ title: '', type: 'text' as const, body: '', module: '1. Begin', minutes: 5 }], revision: program?.revision ?? 1 });
  const [preview, setPreview] = useState(false);
  const [previewLesson, setPreviewLesson] = useState(0);
  const minutes = form.data.lessons.every(l => l.minutes) ? form.data.lessons.reduce((n, l) => n + (l.minutes ?? 0), 0) : form.data.estimated_minutes;
  useEffect(() => {
    const warn = (e: BeforeUnloadEvent) => { if (form.isDirty) { e.preventDefault(); e.returnValue = ''; } };
    window.addEventListener('beforeunload', warn); return () => window.removeEventListener('beforeunload', warn);
  }, [form.isDirty]);
  function change(index: number, patch: Partial<Lesson>) { form.setData('lessons', form.data.lessons.map((l, i) => i === index ? { ...l, ...patch } : l)); }
  function changeType(index: number, type: Lesson['type']) {
    const old = form.data.lessons[index];
    const next: Lesson = { title: old.title, body: old.body, module: old.module, minutes: old.minutes ?? 5, type };
    if (type === 'quiz') Object.assign(next, { question: '', options: ['', ''], correct_option: 0, explanation: '' });
    if (type === 'checklist') next.items = [''];
    form.setData('lessons', form.data.lessons.map((l, i) => i === index ? next : l));
  }
  function move(index: number, step: number) {
    const next = [...form.data.lessons]; [next[index], next[index + step]] = [next[index + step], next[index]]; form.setData('lessons', next);
  }
  function syncSaved(page: { props: Record<string, unknown> }) {
    const saved = page.props.program as ProgramDraft;
    if (!saved) return;
    const data = { title: saved.title, summary: saved.summary, goals: saved.goals ?? '', estimated_minutes: saved.estimated_minutes, lessons: saved.lessons, revision: saved.revision };
    form.setData(data); form.setDefaults(data); form.clearErrors();
  }
  function save(e: FormEvent) {
    e.preventDefault();
    if (!program) form.post('/werk/programmas', { onSuccess: syncSaved });
    else form.put(`/werk/programmas/${program.id}`, { onSuccess: syncSaved });
  }
  return <Workspace title={program ? 'Werk aan je programma' : 'Een nieuw programma'} subtitle="Van een helder doel naar een programma dat iemand op eigen tempo kan volgen.">
    <Head title="Programmabouwer"/>
    <div className="toolbar wrap"><Link href="/werk/programmas" className="text-link">← Mijn programma’s</Link><button type="button" className="button secondary" onClick={() => { setPreview(!preview); setPreviewLesson(0); }}><Eye size={16}/>{preview ? 'Verder bewerken' : 'Bekijk als deelnemer'}</button></div>
    {template && !program && <div className="notice subtle"><strong>Basisprogramma geladen.</strong> Pas inhoud en grenzen aan binnen je deskundigheid. Dit is een educatief concept, geen behandelprotocol. Er is nog niets gepubliceerd.</div>}
    {program?.review_note && <section className="notice"><strong>Toelichting van de beoordelaar</strong><p className="preserve">{program.review_note}</p></section>}
    {preview ? <section className="preview-panel"><p className="notice subtle">Voorbeeld van je huidige concept. Antwoorden en voortgang worden hier niet opgeslagen.</p><div className="field"><label htmlFor="preview-lesson">Bekijk een onderdeel</label><select id="preview-lesson" value={previewLesson} onChange={e => setPreviewLesson(Number(e.target.value))}>{form.data.lessons.map((l, i) => <option value={i} key={i}>{i + 1}. {l.title || 'Zonder titel'}</option>)}</select></div><article className="card lesson-content"><LessonBlock key={previewLesson} lesson={form.data.lessons[previewLesson]}/>{['reflection', 'action'].includes(form.data.lessons[previewLesson].type) && <p className="notice subtle">De deelnemer kan vrijwillig een privéantwoord opslaan en afzonderlijk kiezen of jij dat antwoord mag lezen. Geen toegang tot andere antwoorden of totale voortgang.</p>}</article></section> : <form className="editor" onSubmit={save}>
      <div className="card form-card">{program && <Status value={program.status}/>}
        <Field name="title" label="Titel" maxLength={160} value={form.data.title} onChange={e => form.setData('title', e.target.value)} required/>
        <div className="field"><label htmlFor="summary">Voor wie is dit, en wat kunnen zij verwachten?</label><textarea id="summary" rows={4} maxLength={2000} value={form.data.summary} onChange={e => form.setData('summary', e.target.value)} required/></div>
        <div className="field"><label htmlFor="goals">Leerdoelen, bronnen en beperkingen</label><textarea id="goals" rows={6} maxLength={6000} value={form.data.goals} onChange={e => form.setData('goals', e.target.value)}/></div>
        <p className="builder-stat"><strong>{form.data.lessons.length} onderdelen · circa {minutes} minuten</strong><span>De duur wordt bij opslaan opgeteld wanneer ieder onderdeel minuten heeft.</span></p>
      </div>
      <h2 className="subheading">Modules en onderdelen</h2>
      {form.data.lessons.map((lesson, i) => <section className="card lesson-editor" key={i}>
        <div className="toolbar wrap"><p className="eyebrow">ONDERDEEL {i + 1}</p><div className="actions">
          <button type="button" className="icon-button" aria-label={`Onderdeel ${i + 1} omhoog`} disabled={i === 0} onClick={() => move(i, -1)}><ArrowUp size={16}/></button>
          <button type="button" className="icon-button" aria-label={`Onderdeel ${i + 1} omlaag`} disabled={i === form.data.lessons.length - 1} onClick={() => move(i, 1)}><ArrowDown size={16}/></button>
          <button type="button" className="icon-button" aria-label={`Onderdeel ${i + 1} dupliceren`} disabled={form.data.lessons.length >= 60} onClick={() => { const next = [...form.data.lessons]; next.splice(i + 1, 0, structuredClone(lesson)); form.setData('lessons', next); }}><Copy size={16}/></button>
          <button type="button" className="icon-button danger" aria-label={`Onderdeel ${i + 1} verwijderen`} disabled={form.data.lessons.length <= 1} onClick={() => { if (window.confirm('Dit onderdeel uit het concept verwijderen?')) form.setData('lessons', form.data.lessons.filter((_, n) => n !== i)); }}><Trash2 size={16}/></button>
        </div></div>
        <div className="editor-columns"><Field name={`module-${i}`} label="Module / hoofdstuk" maxLength={120} value={lesson.module ?? ''} onChange={e => change(i, { module: e.target.value })}/><Field name={`minutes-${i}`} label="Minuten" type="number" min={1} max={120} value={lesson.minutes ?? 5} onChange={e => change(i, { minutes: Number(e.target.value) })}/></div>
        <Field name={`title-${i}`} label="Titel van het onderdeel" maxLength={160} value={lesson.title} onChange={e => change(i, { title: e.target.value })} required/>
        <div className="field"><label htmlFor={`type-${i}`}>Type onderdeel</label><select id={`type-${i}`} value={lesson.type} onChange={e => changeType(i, e.target.value as Lesson['type'])}>{Object.entries(lessonLabels).map(([type, name]) => <option value={type} key={type}>{name}</option>)}</select></div>
        <div className="field"><label htmlFor={`body-${i}`}>Inhoud en instructie</label><textarea id={`body-${i}`} rows={6} maxLength={10000} value={lesson.body} onChange={e => change(i, { body: e.target.value })} required/></div>
        {lesson.type === 'quiz' && <div className="sub-editor"><Field name={`question-${i}`} label="Kennisvraag" maxLength={1000} value={lesson.question ?? ''} onChange={e => change(i, { question: e.target.value })} required/>
          {(lesson.options ?? []).map((option, n) => <Field key={n} name={`option-${i}-${n}`} label={`Antwoord ${n + 1}`} maxLength={500} value={option} onChange={e => change(i, { options: lesson.options!.map((o, k) => k === n ? e.target.value : o) })} required/>)}
          <div className="actions"><button type="button" className="button secondary" disabled={(lesson.options?.length ?? 0) >= 6} onClick={() => change(i, { options: [...(lesson.options ?? []), ''] })}>Optie toevoegen</button><button type="button" className="text-link" disabled={(lesson.options?.length ?? 0) <= 2} onClick={() => change(i, { options: lesson.options!.slice(0, -1), correct_option: 0 })}>Laatste optie verwijderen</button></div>
          <div className="field"><label htmlFor={`correct-${i}`}>Juist antwoord</label><select id={`correct-${i}`} value={lesson.correct_option ?? 0} onChange={e => change(i, { correct_option: Number(e.target.value) })}>{(lesson.options ?? []).map((_, n) => <option key={n} value={n}>Antwoord {n + 1}</option>)}</select></div>
          <div className="field"><label htmlFor={`explanation-${i}`}>Toelichting na het kiezen</label><textarea id={`explanation-${i}`} rows={3} maxLength={2000} value={lesson.explanation ?? ''} onChange={e => change(i, { explanation: e.target.value })} required/></div><p className="fineprint">Alleen kennisvragen. Geen diagnostische test, symptoomscore of automatische behandelbeslissing.</p>
        </div>}
        {lesson.type === 'checklist' && <div className="sub-editor">{(lesson.items ?? []).map((item, n) => <div key={n} className="checklist-editor-row"><Field name={`item-${i}-${n}`} label={`Keuze ${n + 1}`} maxLength={500} value={item} onChange={e => change(i, { items: lesson.items!.map((v, k) => k === n ? e.target.value : v) })} required/><button type="button" className="icon-button" aria-label={`Keuze ${n + 1} verwijderen`} disabled={(lesson.items?.length ?? 0) <= 1} onClick={() => change(i, { items: lesson.items!.filter((_, k) => k !== n) })}><Trash2 size={16}/></button></div>)}<button type="button" className="button secondary" disabled={(lesson.items?.length ?? 0) >= 15} onClick={() => change(i, { items: [...(lesson.items ?? []), ''] })}>Keuze toevoegen</button></div>}
        {['reflection', 'action'].includes(lesson.type) && <p className="fineprint">Antwoorden zijn optioneel en standaard privé. Een deelnemer kiest per antwoord of het met jou gedeeld wordt.</p>}
      </section>)}
      <button type="button" className="button secondary" disabled={form.data.lessons.length >= 60} onClick={() => form.setData('lessons', [...form.data.lessons, { title: '', type: 'text', body: '', module: form.data.lessons.at(-1)?.module ?? '', minutes: 5 }])}><Plus size={17}/> Onderdeel toevoegen</button>
      <Errors errors={form.errors}/><div className="editor-actions wrap"><button className="button" disabled={form.processing}>Concept opslaan</button>{program?.status === 'draft' && <button type="button" className="button secondary" disabled={form.processing || form.isDirty} onClick={() => router.post(`/werk/programmas/${program.id}/indienen`, { revision: program.revision }, { onSuccess: syncSaved })}>Ter beoordeling aanbieden</button>}<span role="status" className="fineprint">{form.processing ? 'Bezig…' : form.isDirty ? 'Niet-opgeslagen wijzigingen' : program ? 'Opgeslagen concept' : 'Nog niet opgeslagen'}</span></div>
      <p className="fineprint">Geen automatische publicatie. Een andere beoordelaar controleert de inhoud. Bestaande deelnemers behouden hun versie.</p>
    </form>}
  </Workspace>;
}

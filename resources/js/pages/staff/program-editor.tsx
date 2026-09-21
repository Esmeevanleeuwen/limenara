import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { Errors, Field, Status, Workspace } from '../../components/ui';
import type { Lesson, ProgramDraft } from '../../types';

export default function ProgramEditor({ program }: { program: ProgramDraft | null }) {
  const form = useForm({
    title: program?.title ?? '', summary: program?.summary ?? '', goals: program?.goals ?? '',
    estimated_minutes: program?.estimated_minutes ?? 15,
    lessons: program?.lessons ?? [{ title: '', type: 'text' as const, body: '' }],
    revision: program?.revision ?? 1,
  });
  function changeLesson(index: number, patch: Partial<Lesson>) {
    form.setData('lessons', form.data.lessons.map((lesson, i) => i === index ? { ...lesson, ...patch } : lesson));
  }
  function move(index: number, step: number) {
    const next = [...form.data.lessons];
    [next[index], next[index + step]] = [next[index + step], next[index]];
    form.setData('lessons', next);
  }
  function save(event: FormEvent) {
    event.preventDefault();
    if (!program) { form.post('/werk/programmas'); return; }
    form.put(`/werk/programmas/${program.id}`, {
      onSuccess: (page) => {
        const saved = page.props.program as ProgramDraft;
        const data = { ...form.data, revision: saved.revision };
        form.setData(data);
        form.setDefaults(data);
      },
    });
  }
  return (
    <Workspace title={program ? 'Werk aan je programma' : 'Een nieuw programma'} subtitle="Begin klein. De inhoud is educatief en wordt niet als behandeling aangeboden.">
      <Head title="Programmabouwer"/>
      <Link href="/werk/programmas" className="text-link">← Mijn programma’s</Link>
      <form className="editor" onSubmit={save}>
        <div className="card form-card">
          {program && <Status value={program.status}/>}
          <Field name="title" label="Titel" maxLength={160} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required/>
          <div className="field"><label htmlFor="summary">Voor wie is dit, en wat kunnen zij verwachten?</label><textarea id="summary" maxLength={2000} rows={3} value={form.data.summary} onChange={(e) => form.setData('summary', e.target.value)} required/></div>
          <div className="field"><label htmlFor="goals">Leerdoelen, bronnen en beperkingen</label><textarea id="goals" maxLength={2000} rows={4} value={form.data.goals} onChange={(e) => form.setData('goals', e.target.value)}/></div>
          <Field name="estimated_minutes" label="Geschatte duur in minuten" type="number" min={1} max={10000} value={form.data.estimated_minutes} onChange={(e) => form.setData('estimated_minutes', Number(e.target.value))} required/>
        </div>
        <h2 className="subheading">Lessen en reflecties</h2>
        {form.data.lessons.map((lesson, index) => (
          <section className="card lesson-editor" key={index}>
            <div className="toolbar"><p className="eyebrow">ONDERDEEL {index + 1}</p><div className="actions">
              <button type="button" className="icon-button" aria-label={`Les ${index + 1} omhoog`} disabled={index === 0} onClick={() => move(index, -1)}><ArrowUp size={16}/></button>
              <button type="button" className="icon-button" aria-label={`Les ${index + 1} omlaag`} disabled={index === form.data.lessons.length - 1} onClick={() => move(index, 1)}><ArrowDown size={16}/></button>
              <button type="button" className="icon-button danger" aria-label={`Les ${index + 1} verwijderen`} disabled={form.data.lessons.length <= 1} onClick={() => form.setData('lessons', form.data.lessons.filter((_, i) => i !== index))}><Trash2 size={16}/></button>
            </div></div>
            <Field name={`lesson-${index}-title`} label="Lestitel" maxLength={160} value={lesson.title} onChange={(e) => changeLesson(index, { title: e.target.value })} required/>
            <div className="field"><label htmlFor={`type-${index}`}>Type onderdeel</label><select id={`type-${index}`} value={lesson.type} onChange={(e) => changeLesson(index, { type: e.target.value as Lesson['type'] })}><option value="text">Tekstles</option><option value="reflection">Reflectievraag · geen antwoordopslag</option></select></div>
            <div className="field"><label htmlFor={`body-${index}`}>Inhoud</label><textarea id={`body-${index}`} rows={7} maxLength={10000} value={lesson.body} onChange={(e) => changeLesson(index, { body: e.target.value })} required/></div>
          </section>
        ))}
        <button type="button" className="button secondary" disabled={form.data.lessons.length >= 30} onClick={() => form.setData('lessons', [...form.data.lessons, { title: '', type: 'text', body: '' }])}><Plus size={17}/> Onderdeel toevoegen</button>
        <Errors errors={form.errors}/>
        <div className="editor-actions">
          <button className="button" disabled={form.processing}>Concept opslaan</button>
          {program?.status === 'draft' && <button type="button" className="button secondary" disabled={form.processing || form.isDirty} onClick={() => router.post(`/werk/programmas/${program.id}/indienen`, { revision: program.revision })}>Ter beoordeling aanbieden</button>}
        </div>
        <p className="fineprint">Sla wijzigingen eerst op. Publicatie vereist een andere bevoegde beoordelaar. Een nieuwe publicatie wijzigt geen bestaande inschrijvingen.</p>
      </form>
    </Workspace>
  );
}
